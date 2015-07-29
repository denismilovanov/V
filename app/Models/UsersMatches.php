<?php namespace App\Models;

class UsersMatches
{
    // get connection to one from the 10 working databases
    private static function getConnection($user_id) {
        $num = $user_id % 10;
        return \DB::connection('matches' . $num);
    }

    // disconnect
    private static function disconnect($user_id) {
        self::getConnection($user_id)->disconnect();
    }

    // create tables for "search index" ("current" and "fresh")
    public static function createMatchesTables($user_id) {
        self::getConnection($user_id)->select("
            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            CREATE UNLOGGED TABLE IF NOT EXISTS public.matching_levels_$user_id (LIKE public.matching_levels INCLUDING ALL);

            CREATE UNLOGGED TABLE IF NOT EXISTS public.matching_levels_fresh_$user_id
                (LIKE public.matching_levels INCLUDING ALL)
                WITH (autovacuum_enabled = false, toast.autovacuum_enabled = false);
        ");
    }

    // delete match user from both indexes
    public static function deleteMatch($user_id, $match_user_id, $weight_level) {
        self::createMatchesTables($user_id);

        $weight_level = intval($weight_level);

        $deleted_matching_levels = [];

        if ($weight_level !== null) {
            $deleted_matching_levels = self::getConnection($user_id)->select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'))
                )
                UPDATE public.matching_levels_$user_id
                    SET users_ids = users_ids - intset(:match_user_id)
                    WHERE level_id = :level_id AND
                          users_ids @> intset(:match_user_id)
                    RETURNING level_id
            ", [
                'match_user_id' => $match_user_id,
                'level_id' => $weight_level,
            ]);
        }

        if (! sizeof($deleted_matching_levels)) {
            // no hit, remove from all levels
            self::getConnection($user_id)->select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'))
                )
                UPDATE public.matching_levels_$user_id
                    SET users_ids = users_ids - intset(:match_user_id);
            ", [
                'match_user_id' => $match_user_id,
            ]);
        }

        $deleted_matching_levels = [];

        if ($weight_level !== null) {
            $deleted_matching_levels = self::getConnection($user_id)->select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'))
                )
                UPDATE public.matching_levels_fresh_$user_id
                    SET users_ids = users_ids - intset(:match_user_id)
                    WHERE level_id = :level_id AND
                          users_ids @> intset(:match_user_id)
                    RETURNING level_id;
            ", [
                'match_user_id' => $match_user_id,
                'level_id' => $weight_level,
            ]);
        }

        if (! sizeof($deleted_matching_levels)) {
            // no hit, remove from all levels
            self::getConnection($user_id)->select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'))
                )
                UPDATE public.matching_levels_fresh_$user_id
                    SET users_ids = users_ids - intset(:match_user_id);
            ", [
                'match_user_id' => $match_user_id,
            ]);
        }

        self::disconnect($user_id);
    }

    // enqueue job for rebuilding (filling up)
    public static function enqueueFillMatchesJob($user_id, $priority = 10) {
        \Queue::push('fill_matches', ['user_id' => $user_id, 'ts' => microtime(true)], 'fill_matches', ['priority' => $priority]);

        // update ts before daemon really takes and performes task
        // it prevents creating the second job in getMatches > checkIfNeedRebuilding
        \DB::select("
            UPDATE public.users_matches
                SET last_reindexed_at = now()
                WHERE user_id = ?
        ", [$user_id]);
    }

    // the heart of the system - the formula for ranging users
    public static function weightFormula($groups_vk_ids, $friends_vk_ids, $audio_vk_ids, $likes_users) {
        return "
        (
            " . env('WEIGHT_GROUPS_VK') . " * icount(groups_vk_ids & array[$groups_vk_ids]::int[]) +
            " . env('WEIGHT_FRIENDS_VK') . " * icount(friends_vk_ids & array[$friends_vk_ids]::int[]) +
            " . env('WEIGHT_AUDIO_VK') . " * icount(audio_vk_ids & array[$audio_vk_ids]::int[]) +
            " . env('WEIGHT_DISTANCE') . " * (:radius - st_distance(geography, (:geography)::geography)::decimal / 1000.0) / :radius +
            " . env('WEIGHT_POPULARITY') . " * popularity +
            " . env('WEIGHT_FRIENDLINESS') . " * friendliness +
            " . env('WEIGHT_LIKED_ME') . " * (ui.user_id IN ($likes_users))::integer
        )
        ::integer ";
    }

    // helping function to take is_show_male/is_show_female settings and join them by comma
    private static function aggregateSexIds($settings) {
        $sex = [];
        if ($settings->is_show_male) {
            $sex []= 2;
        }
        if ($settings->is_show_female) {
            $sex []= 1;
        }

        return implode(", ", $sex);
    }

    // is matching user looking for me?
    private static function additionalGenderCondition($sex) {
        $additional_gender_condition = '';

        // i am female, is matching user looking for females?
        if ($sex == 1) {
            $additional_gender_condition = 'is_show_female AND ';
        }
        // i am male, is matching user looking for males?
        else if ($sex == 2) {
            $additional_gender_condition = 'is_show_male AND ';
        }

        return $additional_gender_condition;
    }

    // have we detected region by coordinates?
    private static function additionalRegionCondition($region_id) {
        if ($region_id) {
            // yes, it will help us to speed up the search
            $additional_region_condition = 'region_id = ' . intval($region_id) . ' AND ';
        } else {
            // no, we will relay on coordinates only
            $additional_region_condition = '';
        }

        return $additional_region_condition;
    }

    // rebuilding algorithm (fill up fresh index and rotate)
    public static function fillMatchesInUsersMatches($user_id, $enqueued_at) {
        TIMER('fillMatches.awaiting', microtime(true) - $enqueued_at);

        self::createMatchesTables($user_id);

        $settings = Users::getMySettings($user_id);
        $geography = Users::getMyGeography($user_id);
        $user = Users::findById($user_id);

        $sex = self::aggregateSexIds($settings);
        if (! $sex) {
            return true;
        }

        $additional_gender_condition = self::additionalGenderCondition($user->sex);
        $additional_region_condition = self::additionalRegionCondition($geography['region_id']);

        START('fillMatches.main');

        // filling up "fresh" index with initial data - WEIGHTS_LEVELS empty levels
        self::getConnection($user_id)->select("
            SET synchronous_commit TO off;

            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            TRUNCATE public.matching_levels_fresh_$user_id;
            INSERT INTO public.matching_levels_fresh_$user_id
                SELECT generate_series(0, :weights_levels) AS level_id;
        ", [
            'weights_levels' => env('WEIGHTS_LEVELS'),
        ]);

        $limit = env('WEIGHTS_PROCESSING_BATCH_SIZE');
        $users_max_id = Users::getMaxId();

        // users with id < $users_min_id are for tests
        // they do not match to real users, skip them
        $users_min_id = Users::getMinId($user_id);

        $search_weights_params = Users::getMySearchWeightParams($user_id);
        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;
        $audio_vk_ids = $search_weights_params->audio_vk_ids;

        // take WEIGHTS_PROCESSING_BATCH_SIZE users from foreign table public.users_index
        // filter by region, age, sex, geography
        // calculate weights, group by them
        // do it in cycle
        for ($i = (int)ceil($users_min_id / $limit); $i < ceil($users_max_id / $limit); $i ++) {

            // users I liked - remove them from index
            $liked_users = Likes::getLikedUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);

            // exclude me
            $liked_users .= ', ' . $user_id;

            // users I was liked by - increase weights for them
            $likes_users = Likes::getLikesUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);

            // main query
            self::getConnection($user_id)->select("
                SET synchronous_commit TO off;

                SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

                WITH all_users AS (
                    SELECT  ui.user_id AS match_user_id,

                            " . self::weightFormula($groups_vk_ids, $friends_vk_ids, $audio_vk_ids, $likes_users) . "
                            AS matching_level

                        FROM public.users_index AS ui

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                user_id NOT IN (" . $liked_users . ") AND
                                $additional_region_condition
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex) AND
                                $additional_gender_condition
                                ST_DWithin(geography, (:geography)::geography, :radius * 1000)
                ),
                levels AS (
                    SELECT  CASE WHEN matching_level < :weights_levels
                                THEN matching_level
                                ELSE :weights_levels
                            END AS matching_level,
                            array_agg(match_user_id) AS users_ids
                        FROM all_users
                        GROUP BY CASE WHEN matching_level < :weights_levels
                                        THEN matching_level
                                        ELSE :weights_levels
                                 END
                )

                UPDATE public.matching_levels_fresh_$user_id AS l
                    SET users_ids = l.users_ids + levels.users_ids
                    FROM levels
                    WHERE level_id = levels.matching_level;

            ", [
                'user_id' => $user_id,
                'age_from' => $settings->age_from ? : env('MIN_AGE'),
                'age_to' => $settings->age_to ? : env('MAX_AGE'),
                'radius' => $settings->radius,
                'geography' =>  $geography['geography'],
                'weights_levels' => env('WEIGHTS_LEVELS'),
            ]);

        }

        START('fillMatches.aggregation');

        // replace present index for fresh index
        // leave only given amount of the most relevant users
        self::getConnection($user_id)->select("
            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            DROP TABLE IF EXISTS public.matching_levels_$user_id;
            CREATE UNLOGGED TABLE public.matching_levels_$user_id (LIKE public.matching_levels INCLUDING ALL);

            WITH levels AS (
                SELECT level_id, sum(icount(users_ids)) OVER (ORDER BY level_id DESC) AS cumulative_sum
                    FROM public.matching_levels_fresh_$user_id
            ),
            low_level AS (
                SELECT COALESCE((
                    SELECT level_id
                        FROM levels
                        WHERE cumulative_sum > :MAX_MAINTAINED_MATCHES_COUNT
                        ORDER BY level_id DESC
                        LIMIT 1
                    ), 0) AS level_id
            )
            INSERT INTO public.matching_levels_$user_id
                SELECT fresh.*
                    FROM public.matching_levels_fresh_$user_id AS fresh, low_level
                    WHERE   fresh.level_id >= low_level.level_id AND
                            icount(fresh.users_ids) > 0;

            DROP TABLE public.matching_levels_fresh_$user_id;
            CREATE UNLOGGED TABLE public.matching_levels_fresh_$user_id
                (LIKE public.matching_levels INCLUDING ALL)
                WITH (autovacuum_enabled = false, toast.autovacuum_enabled = false);
        ", [
            'MAX_MAINTAINED_MATCHES_COUNT' => env('MAX_MAINTAINED_MATCHES_COUNT')
        ]);

        FINISH();
        FINISH();

        self::disconnect($user_id);

        return true;
    }

    //
    public static function getMatches($user_id, $limit) {
        self::checkIfNeedRebuilding($user_id);

        self::createMatchesTables($user_id);

        START('getMatches.readFromIndex');

        // read top users
        $users_ids_at_levels = self::getConnection($user_id)->select("
            SELECT * FROM public.get_matching_users_ids(:user_id, :limit) AS t(level_id integer, user_id integer);
        ", [
            'user_id' => $user_id,
            'limit' => $limit,
        ]);

        FINISH();

        // indexes are empty, we need to find matching users right now!
        if (! sizeof($users_ids_at_levels)) {
            START('getMatches.reserveAlgorithm');

            $settings = Users::getMySettings($user_id);
            $geography = Users::getMyGeography($user_id);
            $user = Users::findById($user_id);
            $sex = self::aggregateSexIds($settings);

            $additional_gender_condition = self::additionalGenderCondition($user->sex);
            $additional_region_condition = self::additionalRegionCondition($geography['region_id']);

            if ($sex) {
                $search_weights_params = Users::getMySearchWeightParams($user_id);
                $friends_vk_ids = $search_weights_params->friends_vk_ids;
                $groups_vk_ids = $search_weights_params->groups_vk_ids;
                $audio_vk_ids = $search_weights_params->audio_vk_ids;

                $liked_users = Likes::getAllLikedUsers($user_id);
                $likes_users = Likes::getAllLikesUsers($user_id);

                foreach ([$additional_region_condition, ''] as $current_additional_region_condition) {
                    // heavy query without any guarantee to get the most relative users
                    // they will be simply matching by geo, age and sex
                    // or only by geo and sex at the second iteration
                    $users_ids_at_levels = \DB::select("
                        WITH matches AS (
                            SELECT  ui.user_id AS user_id,

                                    " . self::weightFormula($groups_vk_ids, $friends_vk_ids, $audio_vk_ids, $likes_users) . " AS level_id

                                FROM public.users_index AS ui
                                WHERE   $current_additional_region_condition
                                        age BETWEEN :age_from AND :age_to AND
                                        sex IN ($sex) AND
                                        $additional_gender_condition
                                        ST_DWithin(geography, (:geography)::geography, :radius * 1000) AND
                                        user_id NOT IN (" . $liked_users . ") AND
                                        user_id >= :min_user_id AND
                                        user_id != :user_id
                                ORDER BY ui.last_activity_at DESC
                                LIMIT :limit
                        ),
                        matches_ordered AS (
                            SELECT user_id, level_id
                                FROM matches
                                ORDER BY level_id DESC
                        )
                        SELECT level_id, user_id
                            FROM matches_ordered AS m;
                    ", [
                        'user_id' => $user_id,
                        'age_from' => $settings->age_from ? : env('MIN_AGE'),
                        'age_to' => $settings->age_to ? : env('MAX_AGE'),
                        'radius' => $settings->radius,
                        'geography' =>  $geography['geography'],
                        'limit' => min($limit, env('RESERVE_ALGORITHM_USERS_COUNT')),
                        // will be 0 for user == developer
                        // will be min real user id for others
                        'min_user_id' => Users::getMinId($user_id),
                    ]);

                    if ($users_ids_at_levels) {
                        break;
                    }
                }
            }

            FINISH();
        }

        self::disconnect($user_id);

        $users_ids_at_levels_reindexed = [];
        foreach ($users_ids_at_levels as $user) {
            // prevent overwriting (user may come from both indexes)
            if (! isset($users_ids_at_levels_reindexed[$user->user_id])) {
                $users_ids_at_levels_reindexed[$user->user_id] = $user;
            }
        }

        return $users_ids_at_levels_reindexed;
    }

    // take users with old current index and enqueue rebuild job
    public static function checkIfNeedRebuilding($user_id) {
        $check = \DB::select("
            SELECT user_id
                FROM public.users_matches
                WHERE   user_id = ? AND
                        last_reindexed_at < now() - interval '1 day';
        ", [$user_id]);

        if (sizeof($check)) {
            // enqueue with priority = 0
            self::enqueueFillMatchesJob($user_id, 0);
        }
    }

    // enqueue special task for removing one user from another's user index
    public static function enqueueRemoveFromIndex($user_id, $match_user_id) {
        try {
            \Queue::push('remove_from_index', [
                'user_id' => $user_id,
                'match_user_id' => $match_user_id,
            ], 'remove_from_index');
        } catch (\Exception $e) {
            // we can survive without this job done
            ;
        }
    }

}


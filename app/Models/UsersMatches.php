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
    private static function weightFormula($groups_vk_ids, $friends_vk_ids, $likes_users) {
        return "
        (
            " . env('WEIGHT_GROUPS_VK') . " * icount(groups_vk_ids & array[$groups_vk_ids]::int[]) +
            " . env('WEIGHT_FRIENDS_VK') . " * icount(friends_vk_ids & array[$friends_vk_ids]::int[]) +
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

    // rebuilding algorithm (fill up fresh index and rotate)
    public static function fillMatchesInUsersMatches($user_id, $enqueued_at) {
        TIMER('fillMatches.awaiting', microtime(true) - $enqueued_at);

        self::createMatchesTables($user_id);

        $settings = Users::getMySettings($user_id);
        $geography = Users::getMyGeography($user_id);

        $sex = self::aggregateSexIds($settings);
        if (! $sex) {
            return true;
        }

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

        $search_weights_params = Users::getMySearchWeightParams($user_id);
        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;

        // take WEIGHTS_PROCESSING_BATCH_SIZE users from foreign table public.users_index
        // filter by region, age, sex, geography
        // calculate weights, group by them
        // do it in cycle
        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {

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

                            " . self::weightFormula($groups_vk_ids, $friends_vk_ids, $likes_users) . "
                            AS matching_level

                        FROM public.users_index AS ui

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                user_id NOT IN (" . $liked_users . ") AND
                                region_id = :region_id AND
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex) AND
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
                'region_id' => $geography['region_id'],
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
        START('getMatches');

        self::checkIfNeedRebuilding($user_id);

        self::createMatchesTables($user_id);

        $users_ids = [];
        $iterations = 0;
        $level_id = null;

        // we need to perform some iterations, because we gonna
        // read from both indexes (current and fresh)
        do {
            $iterations ++;

            // read the highest levels from current and fresh
            $max_levels_ids = self::getConnection($user_id)->select("
                SELECT  COALESCE((
                            SELECT level_id
                                FROM public.matching_levels_$user_id
                                WHERE icount(users_ids) > 0
                                ORDER BY level_id DESC
                                LIMIT 1
                            ), -1
                        ) AS current_level_id,
                        COALESCE((
                            SELECT level_id
                                FROM public.matching_levels_fresh_$user_id
                                WHERE icount(users_ids) > 0
                                ORDER BY level_id DESC
                                LIMIT 1
                            ), -1
                        ) AS fresh_level_id;
            ")[0];

            // compare them
            $max_current_level_id = $max_levels_ids->current_level_id;
            $max_fresh_level_id = $max_levels_ids->fresh_level_id;

            if ($max_fresh_level_id >= $max_current_level_id and $max_fresh_level_id >= 0) {
                // we need to read from fresh
                $table = 'fresh_';
                $level_id = $max_fresh_level_id;
            } else if ($max_current_level_id >= $max_fresh_level_id and $max_current_level_id >= 0) {
                // we need to read from current
                $table = '';
                $level_id = $max_current_level_id;
            } else {
                // both indexes are empty
                break;
            }

            // read users at level
            $users_ids = self::getConnection($user_id)->select("
                SELECT array_to_string(subarray(users_ids, 0, $limit), ',') AS users_ids
                    FROM public.matching_levels_{$table}{$user_id}
                    WHERE level_id = ?;
            ", [$level_id]);

            // there is time lag between reading level and reading users
            // it is possible fresh index to be truncated between these moments of time
            // the actual data is now in current index
            if (sizeof($users_ids) and $users_ids[0]->users_ids) {
                $users_ids = explode(',', $users_ids[0]->users_ids);
            }
            // so $users_ids can be still empty and we need to take another iteration

        // it is unbelievable 5 iterations take place :)
        } while (! $users_ids and $iterations < 5);

        FINISH();

        // indexes are empty, we need to find matching users right now!
        if (! sizeof($users_ids)) {
            START('getMatches.reserveAlgorithm');

            $settings = Users::getMySettings($user_id);
            $geography = Users::getMyGeography($user_id);

            $sex = self::aggregateSexIds($settings);

            if ($sex) {
                $search_weights_params = Users::getMySearchWeightParams($user_id);
                $friends_vk_ids = $search_weights_params->friends_vk_ids;
                $groups_vk_ids = $search_weights_params->groups_vk_ids;

                $liked_users = Likes::getAllLikedUsers($user_id);
                $likes_users = Likes::getAllLikesUsers($user_id);

                // heavy query without any guarantee to get the most relative users
                // they will be simply matching by geo, age and sex
                $users_ids = \DB::select("
                    WITH matches AS (
                        SELECT  ui.user_id AS match_user_id,

                                " . self::weightFormula($groups_vk_ids, $friends_vk_ids, $likes_users) . " AS weight_level

                            FROM public.users_index AS ui
                            WHERE   region_id = :region_id AND
                                    age BETWEEN :age_from AND :age_to AND
                                    sex IN ($sex) AND
                                    ST_DWithin(geography, (:geography)::geography, :radius * 1000) AND
                                    user_id NOT IN (" . $liked_users . ")
                            ORDER BY ui.last_activity_at DESC
                            LIMIT :limit
                    ),
                    matches_ordered AS (
                        SELECT match_user_id
                            FROM matches
                            ORDER BY weight_level DESC
                    )
                    SELECT string_agg(m.match_user_id::varchar, ',') AS users_ids
                        FROM matches_ordered AS m;
                ", [
                    'user_id' => $user_id,
                    'age_from' => $settings->age_from ? : env('MIN_AGE'),
                    'age_to' => $settings->age_to ? : env('MAX_AGE'),
                    'radius' => $settings->radius,
                    'geography' =>  $geography['geography'],
                    'region_id' => $geography['region_id'],
                    'limit' => min($limit, env('RESERVE_ALGORITHM_USERS_COUNT')),
                ]);

                $level_id = 0;
                if (sizeof($users_ids) and $users_ids[0]->users_ids) {
                    $users_ids = explode(',', $users_ids[0]->users_ids);
                } else {
                    // there are no mathing users at all
                    $users_ids = '';
                }
            }

            FINISH();
        }

        self::disconnect($user_id);

        return [
            'users_ids' => $users_ids,
            'weight_level' => $level_id,
        ];
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


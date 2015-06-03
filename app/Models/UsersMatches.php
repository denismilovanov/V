<?php namespace App\Models;

class UsersMatches
{
    // get connection to one from the 10 working databases
    private static function getConnection($user_id) {
        $num = $user_id % 10;
        return \DB::connection('matches' . $num);
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
    }

    // enqueue job for rebuilding (filling up)
    public static function enqueueFillMatchesJob($user_id, $priority = 10) {
        \Queue::push('fill_matches', ['user_id' => $user_id], 'fill_matches', ['priority' => $priority]);
    }

    // the heart of the system - the formula for ranging users
    private static function weightFormula($groups_vk_ids, $friends_vk_ids, $likes_users) {
        return "
        (
            35.0 * icount(groups_vk_ids & array[$groups_vk_ids]::int[]) / 10.0 +
            35.0 * icount(friends_vk_ids & array[$friends_vk_ids]::int[]) / 5.0 +
            10.0 * (:radius - st_distance(geography, (:geography)::geography)::decimal / 1000.0) / :radius +
            10.0 * popularity +
            10.0 * friendliness +
            20.0 * (ui.user_id IN ($likes_users))::integer
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
    public static function fillMatchesInUsersMatches($user_id) {
        self::createMatchesTables($user_id);

        $settings = Users::getMySettings($user_id);
        $geography = Users::getMyGeography($user_id);

        $sex = self::aggregateSexIds($settings);
        if (! $sex) {
            return true;
        }

        // update ts
        \DB::select("
            UPDATE public.users_matches
                SET last_reindexed_at = now()
                WHERE user_id = ?
        ", [$user_id]);

        // filling up "fresh" index with initial data - 1000 empty levels
        self::getConnection($user_id)->select("
            SET synchronous_commit TO off;

            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            TRUNCATE public.matching_levels_fresh_$user_id;
            INSERT INTO public.matching_levels_fresh_$user_id
                SELECT generate_series(0, 1000) AS level_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        $search_weights_params = Users::getMySearchWeightParams($user_id);
        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;

        // take 10000 users from foreign table public.users_index
        // filter by region, age, sex, geography
        // calculate weights, group by them
        // do it in cycle
        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {

            // users I liked - remove them from index
            $liked_users = Likes::getLikedUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);

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
                    SELECT  CASE WHEN matching_level < 1000 THEN matching_level ELSE 1000 END AS matching_level,
                            array_agg(match_user_id) AS users_ids
                        FROM all_users
                        GROUP BY CASE WHEN matching_level < 1000 THEN matching_level ELSE 1000 END
                )

                UPDATE public.matching_levels_fresh_$user_id AS l
                    SET users_ids = l.users_ids + levels.users_ids
                    FROM levels
                    WHERE level_id = levels.matching_level;

            ", [
                'user_id' => $user_id,
                'age_from' => $settings->age_from ? : 18,
                'age_to' => $settings->age_to ? : 80,
                'radius' => $settings->radius,
                'geography' =>  $geography['geography'],
                'region_id' => $geography['region_id'],
            ]);
        }

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
            'MAX_MAINTAINED_MATCHES_COUNT' => env('MAX_MAINTAINED_MATCHES_COUNT', 1000)
        ]);

        self::getConnection($user_id)->disconnect();

        return true;
    }

    //
    public static function getMatches($user_id, $limit) {
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


        // indexes are empty, we need to find matching users right now!
        if (! sizeof($users_ids)) {
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

                                -- not used yet:
                                " . self::weightFormula($groups_vk_ids, $friends_vk_ids, $likes_users) . " AS weight_level

                            FROM public.users_index AS ui
                            WHERE   region_id = :region_id AND
                                    age BETWEEN :age_from AND :age_to AND
                                    sex IN ($sex) AND
                                    ST_DWithin(geography, (:geography)::geography, :radius * 1000) AND
                                    user_id NOT IN (" . $liked_users . ")
                            ORDER BY ui.last_activity_at DESC
                            LIMIT :limit
                    )
                    SELECT string_agg(m.match_user_id::varchar, ',') AS users_ids
                        FROM matches AS m;
                ", [
                    'user_id' => $user_id,
                    'age_from' => $settings->age_from ? : 18,
                    'age_to' => $settings->age_to ? : 80,
                    'radius' => $settings->radius,
                    'geography' =>  $geography['geography'],
                    'region_id' => $geography['region_id'],
                    // 20 seems to be enough
                    'limit' => $limit > 20 ? 20 : $limit,
                ]);

                $level_id = 0;
                if (sizeof($users_ids) and $users_ids[0]->users_ids) {
                    $users_ids = explode(',', $users_ids[0]->users_ids);
                } else {
                    // there are no mathing users at all
                    $users_ids = '';
                }
            }
        }

        return [
            'users_ids' => $users_ids,
            'weight_level' => $level_id,
        ];
    }

    // take users with old current index and enqueue rebuild job
    public static function rebuildBatch() {
        $users_ids = \DB::select("
            WITH u AS (
                SELECT user_id
                    FROM public.users_matches
                    WHERE last_reindexed_at < now() - interval '1 day'
                    ORDER BY last_reindexed_at
                    LIMIT 100
            )
            UPDATE public.users_matches
                SET last_reindexed_at = now()
                WHERE user_id IN (SELECT user_id FROM u)
                RETURNING user_id;
        ");

        $list = [];

        foreach ($users_ids as $user_id) {
            $user_id = $user_id->user_id;
            $list []= $user_id;

            // enqueue with priority = 0
            self::enqueueFillMatchesJob($user_id, 0);
        }

        return implode(', ', $list);
    }

}


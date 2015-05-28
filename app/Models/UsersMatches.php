<?php namespace App\Models;

class UsersMatches
{
    private static function getConnection($user_id) {
        $num = $user_id % 10;
        return \DB::connection('matches' . $num);
    }
    public static function createMatchesTables($user_id) {
        self::getConnection($user_id)->select("
            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            CREATE UNLOGGED TABLE IF NOT EXISTS public.matching_levels_$user_id (LIKE public.matching_levels INCLUDING ALL);

            CREATE UNLOGGED TABLE IF NOT EXISTS public.matching_levels_fresh_$user_id
                (LIKE public.matching_levels INCLUDING ALL)
                WITH (autovacuum_enabled = false, toast.autovacuum_enabled = false);
        ");
    }

    public static function deleteMatch($user_id, $match_user_id, $weight_level) {
        self::createMatchesTables($user_id);

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
                'level_id' => intval($weight_level),
            ]);
        }

        if (! sizeof($deleted_matching_levels)) {
            // не нашли на указанном уровне, удаляем отовсюду
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

    public static function jobFillMatches($user_id) {
        \Queue::push('fill_matches', ['user_id' => $user_id], 'fill_matches');
    }

    public static function fillMatchesInUsersMatches($user_id) {
        self::createMatchesTables($user_id);

        $settings = Users::getMySettings($user_id);
        $geography = Users::getMyGeography($user_id);

        $sex = [];
        if ($settings->is_show_male) {
            $sex []= 2;
        }
        if ($settings->is_show_female) {
            $sex []= 1;
        }
        $sex = implode(", ", $sex);

        self::createMatchesTables($user_id);

        self::getConnection($user_id)->select("
            SET synchronous_commit TO off;

            SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

            TRUNCATE public.matching_levels_fresh_$user_id;
            INSERT INTO public.matching_levels_fresh_$user_id
                SELECT generate_series(0, 100) AS level_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        $search_weights_params = Users::getMySearchWeightParams($user_id);
        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {

            $liked_users = Likes::getLikedUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);
            if (! $liked_users) {
                $liked_users = '0';
            }

            // селект на основании foreign table
            self::getConnection($user_id)->select("
                SET synchronous_commit TO off;

                SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

                WITH all_users AS (
                    SELECT  ui.user_id AS match_user_id,

                            -- уровень
                            public.get_weight_level(
                                icount(groups_vk_ids & array[$groups_vk_ids]::int[]),
                                icount(friends_vk_ids & array[$friends_vk_ids]::int[]),
                                :radius * 1000,
                                st_distance(geography, (:geography)::geography)::integer
                            ) AS matching_level

                        FROM public.users_index AS ui

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                user_id NOT IN (" . $liked_users . ") AND
                                region_id = :region_id AND
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex) AND
                                ST_DWithin(geography, (:geography)::geography, :radius * 1000)
                ),
                levels AS (
                    SELECT matching_level, array_agg(match_user_id) AS users_ids
                        FROM all_users
                        GROUP BY matching_level
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

        return true;
    }

    private static function getMatchesAtLevel($user_id, $level_id, $limit) {
        return self::getConnection($user_id)->select("
            WITH u AS (
                SELECT unnest(users_ids) AS user_id
                    FROM public.matching_levels_$user_id
                    WHERE level_id = ?
            )
            SELECT u.user_id
                FROM u
                LIMIT ?
                -- ORDER BY u.user_id
        ", [$level_id, $limit]);
    }

    public static function getMatches($user_id, $limit) {
        self::createMatchesTables($user_id);

        $levels_ids = self::getConnection($user_id)->select("
            SELECT level_id
                FROM public.matching_levels_$user_id
                WHERE icount(users_ids) > 0
                ORDER BY level_id DESC
                LIMIT 1;
        ");

        $result = [];

        foreach ($levels_ids as $level_id) {
            $level_id = $level_id->level_id;
            $result = self::getMatchesAtLevel($user_id, $level_id, $limit);
            break;
        }

        if (! sizeof($result)) {
            // запасной вариант
        }

        return $result;
    }
}

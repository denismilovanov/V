<?php namespace App\Models;

class UsersMatches
{
    private static function getConnection($user_id) {
        $num = $user_id % 10;
        return \DB::connection('matches' . $num);
    }
    public static function createMatchesTables($user_id) {
        self::getConnection($user_id)->select("
            SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'));
            CREATE TABLE IF NOT EXISTS public.processing_levels_$user_id (LIKE public.processing_levels INCLUDING ALL);
            CREATE TABLE IF NOT EXISTS public.matching_levels_$user_id (LIKE public.matching_levels INCLUDING ALL);
        ");
    }

    public static function stopProcessing($user_id) {
        self::getConnection($user_id)->select("
            SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'));
            DROP TABLE public.processing_levels_$user_id;
        ");
    }

    public static function deleteMatch($user_id, $match_user_id, $weight_level) {
        self::createMatchesTables($user_id);

        self::getConnection($user_id)->select("
            WITH lock AS (
                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
            )
            UPDATE public.processing_levels_$user_id
                SET users_ids = users_ids - intset(:match_user_id);
                -- удаляет по всем уровням, хотя значение присутствует только на одном из них
                -- это самый быстрый вариант
        ", [
            'match_user_id' => $match_user_id,
        ]);

        $deleted_matching_levels = [];

        if ($weight_level !== null) {
            $deleted_matching_levels = self::getConnection($user_id)->select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
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
                    SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
                )
                UPDATE public.matching_levels
                    SET users_ids = users_ids - intset(:match_user_id);
            ", [
                'match_user_id' => $match_user_id,
            ]);
        }
    }

    public static function jobFillMatches($user_id) {
        \Queue::push('fill_matches', ['user_id' => $user_id], 'fill_matches');
    }

    public static function fillMatchesInUsersMatches($user_id) {
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

            SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'));

            TRUNCATE public.processing_levels_$user_id;
            INSERT INTO public.processing_levels_$user_id
                SELECT generate_series(0, 100) AS level_id;

            TRUNCATE public.matching_levels_$user_id;
            INSERT INTO public.matching_levels_$user_id
                SELECT generate_series(0, 100) AS level_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {

            $liked_users = Likes::getLikedUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);
            if (! $liked_users) {
                $liked_users = '0';
            }

            // селект на основании foreign table
            self::getConnection($user_id)->select("
                SET synchronous_commit TO off;

                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'));

                WITH all_users AS (
                    SELECT  ui.user_id AS match_user_id,

                            -- порядок для подсчета весов
                            public.get_processing_level(
                                icount(groups_vk_ids),
                                icount(friends_vk_ids),
                                :radius * 1000,
                                st_distance(geography, (:geography)::geography)::integer
                            ) AS processing_level

                        FROM public.users_index AS ui

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                user_id NOT IN (" . $liked_users . ") AND
                                ST_DWithin(geography, (:geography)::geography, :radius * 1000) AND
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex)
                ),
                levels AS (
                    SELECT processing_level, array_agg(match_user_id) AS users_ids
                        FROM all_users
                        GROUP BY processing_level
                )

                UPDATE public.processing_levels_$user_id AS l
                    SET users_ids = l.users_ids + levels.users_ids
                    FROM levels
                    WHERE level_id = levels.processing_level;

            ", [
                'user_id' => $user_id,
                'age_from' => $settings->age_from ? : 18,
                'age_to' => $settings->age_to ? : 80,
                'radius' => $settings->radius,
                'geography' => $geography,
            ]);
        }

        // \Queue::push('update_weights', ['user_id' => $user_id], 'update_weights');
        // первую пачку весов обновим сразу, без очереди
        self::updateWeights($user_id);

        return true;
    }

    public static function updateWeights($user_id, $already_filled = 0) {
        self::createMatchesTables($user_id);

        $search_weights_params = Users::getMySearchWeightParams($user_id);

        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;

        $result = self::getConnection($user_id)->select("
            SET synchronous_commit TO off;
            WITH lock AS (
                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
            ),
            level_id AS (
                SELECT level_id
                    FROM public.processing_levels_$user_id
                    WHERE icount(users_ids) > 0
                    ORDER BY level_id DESC
                    LIMIT 1
            ),
            users_ids AS (
                SELECT subarray(users_ids, 0, 500) AS users_ids
                    FROM public.processing_levels_$user_id
                    WHERE level_id = (SELECT level_id FROM level_id)
            )
            UPDATE public.processing_levels_$user_id AS pl
                SET users_ids = pl.users_ids - ids.users_ids
                FROM users_ids AS ids, level_id AS l
                WHERE pl.level_id = l.level_id
                RETURNING array_to_string(ids.users_ids, ',') AS users_ids, l.level_id
        ");

        $count = 0;
        $users_ids = '';
        $level_id = 'n/a';

        if ($result) {
            $result = $result[0];
            $users_ids = $result->users_ids;
            $level_id = $result->level_id;
            $count = count(explode(",", $users_ids));

            if ($count) {
                // апдейт на основании foreign table
                self::getConnection($user_id)->select("
                    WITH lock AS (
                        SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
                    ),
                    weights_levels AS (
                        SELECT  i.user_id AS match_user_id,
                            public.get_weight_level(
                                icount(i.friends_vk_ids & array[$friends_vk_ids]::int[]),
                                icount(i.groups_vk_ids & array[$groups_vk_ids]::int[])
                            ) AS weight_level
                        FROM public.users_index AS i
                        WHERE i.user_id IN ($users_ids)
                    ),
                    levels AS (
                        SELECT weight_level, array_agg(match_user_id) AS users_ids
                            FROM weights_levels
                            GROUP BY weight_level
                    )
                    UPDATE public.matching_levels_$user_id AS m
                        SET users_ids = m.users_ids + l.users_ids
                        FROM levels AS l
                        WHERE m.level_id = ?;
                ", [$level_id]);
            }
        }

        \Log::info('level_id = ' . $level_id . ', count = ' . $count . ', already_filled = ' . $already_filled .
            ', sum = ' . ($count + $already_filled));

        if ($count and $already_filled + $count < env('MAX_MAINTAINED_MATCHES_COUNT', 1000)) {
            // чем выше уровень мы только что посчитали, тем выше должен быть приоритет для подсчета следующего
            \Queue::push('update_weights', ['user_id' => $user_id, 'count' => $already_filled + $count], 'update_weights', ['priority' => $level_id]);
        } else {
            self::stopProcessing($user_id);
        }

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

<?php namespace App\Models;

class UsersMatches
{
    public static function deleteMatch($user_id, $match_user_id, $weight_level) {
        \DB::select("
            WITH lock AS (
                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
            )
            UPDATE public.users_processing_levels
                SET users_ids = users_ids - intset(:match_user_id)
                WHERE user_id = :user_id;
                -- удаляет по всем уровням, хотя значение присутствует только на одном из них
                -- это самый быстрый вариант
        ", [
            'user_id' => $user_id,
            'match_user_id' => $match_user_id,
        ]);

        $deleted_matching_levels = [];

        if ($weight_level !== null) {
            $deleted_matching_levels = \DB::select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
                )
                UPDATE public.users_matching_levels
                    SET users_ids = users_ids - intset(:match_user_id)
                    WHERE user_id = :user_id AND
                          level_id = :level_id AND
                          users_ids @> intset(:match_user_id)
                    RETURNING level_id
            ", [
                'user_id' => $user_id,
                'match_user_id' => $match_user_id,
                'level_id' => intval($weight_level) ,
            ]);
        }

        if (! sizeof($deleted_matching_levels)) {
            // не нашли на указанном уровне, удаляем отовсюду
            \DB::select("
                WITH lock AS (
                    SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
                )
                UPDATE public.users_matching_levels
                    SET users_ids = users_ids - intset(:match_user_id)
                    WHERE user_id = :user_id;
            ", [
                'user_id' => $user_id,
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

        \DB::select("
            SET synchronous_commit TO off;

            SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'));

            DELETE FROM public.users_processing_levels
                WHERE user_id = :user_id;
            INSERT INTO public.users_processing_levels
                SELECT  :user_id,
                        generate_series(0, 100) AS level_id;

            DELETE FROM public.users_matching_levels
                WHERE user_id = :user_id;
            INSERT INTO public.users_matching_levels
                SELECT  :user_id,
                        generate_series(0, 100) AS level_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {
            \DB::select("
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

                        LEFT JOIN public.likes AS l
                            ON  l.user1_id = :user_id AND
                                l.user2_id = ui.user_id

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                ST_DWithin(geography, (:geography)::geography, :radius * 1000) AND
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex) AND
                                l.user1_id IS NULL
                ),
                levels AS (
                    SELECT processing_level, array_agg(match_user_id) AS users_ids
                        FROM all_users
                        GROUP BY processing_level
                )

                UPDATE public.users_processing_levels AS l
                    SET users_ids = l.users_ids + levels.users_ids
                    FROM levels
                    WHERE   user_id = :user_id AND
                            level_id = levels.processing_level

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

    public static function updateWeights($user_id) {
        $search_weights_params = Users::getMySearchWeightParams($user_id);

        $friends_vk_ids = $search_weights_params->friends_vk_ids;
        $groups_vk_ids = $search_weights_params->groups_vk_ids;

        $result = \DB::select("
            SET synchronous_commit TO off;
            WITH lock AS (
                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
            ),
            level AS (
                SELECT level_id
                    FROM users_processing_levels
                    WHERE   user_id = :user_id AND
                            icount(users_ids) > 0
                    ORDER BY level_id DESC
                    LIMIT 1
            ),
            leveled_users AS (
                SELECT subarray(users_ids, 0, 500) AS users_ids
                    FROM users_processing_levels
                    WHERE   user_id = :user_id AND
                            level_id = (SELECT level_id FROM level)
            ),
            removed_users_processing_levels AS (
                UPDATE users_processing_levels AS l
                    SET users_ids = l.users_ids - leveled_users.users_ids
                    FROM leveled_users
                    WHERE   l.user_id = :user_id AND
                            l.level_id = (SELECT level_id FROM level)
            ),
            weights_levels AS (
                SELECT  i.user_id AS match_user_id,
                        public.get_weight_level(
                            icount(i.friends_vk_ids & array[$friends_vk_ids]::int[]),
                            icount(i.groups_vk_ids & array[$groups_vk_ids]::int[])
                        ) AS weight_level
                    FROM public.users_index AS i, leveled_users
                    WHERE i.user_id = ANY(leveled_users.users_ids)
            ),
            levels AS (
                SELECT weight_level, array_agg(match_user_id) AS users_ids
                    FROM weights_levels
                    GROUP BY weight_level
            ),
            upd AS (
                UPDATE public.users_matching_levels AS m
                    SET users_ids = m.users_ids + l.users_ids
                    FROM levels AS l
                    WHERE   m.user_id = :user_id AND
                            m.level_id = l.weight_level
            )
            SELECT COALESCE(array_to_string(users_ids, ','), '') AS users_ids, level.level_id, icount(users_ids) AS count
                FROM leveled_users, level
        ", [
            'user_id' => $user_id,
        ]);

        $count = 0;
        $level_id = 'n/a';
        //$users_ids = '';

        if (isset($result[0]) and $result = $result[0]) {
            //$users_ids = $result->users_ids;
            $count = $result->count;
            $level_id = $result->level_id;
        }

        \Log::info('level_id = ' . $level_id);
        \Log::info('count = ' . $count);
        //\Log::info('users_ids = ' . $users_ids);

        if ($count) {
            // чем выше уровень мы только что посчитали, тем выше должен быть приоритет для подсчета следующего
            \Queue::push('update_weights', ['user_id' => $user_id], 'update_weights', ['priority' => $level_id]);
        }

        return true;
    }

    private static function getMatchesAtLevel($me_id, $level_id, $limit) {
        return \DB::select("
            WITH u AS (
                SELECT unnest(users_ids) AS user_id
                    FROM public.users_matching_levels
                    WHERE   user_id = ? AND
                            level_id = ?
            )
            SELECT u.user_id
                FROM u
                LIMIT ?
                -- ORDER BY u.user_id
        ", [$me_id, $level_id, $limit]);
    }

    public static function getMatches($me_id, $limit) {
        $levels_ids = \DB::select("
            SELECT level_id
                FROM public.users_matching_levels
                WHERE   user_id = ? AND
                        icount(users_ids) > 0
                ORDER BY level_id DESC
                LIMIT 1;
        ", [$me_id]);

        $result = [];

        foreach ($levels_ids as $level_id) {
            $level_id = $level_id->level_id;
            $result = self::getMatchesAtLevel($me_id, $level_id, $limit);
            break;
        }

        if (! sizeof($result)) {
            // запасной вариант
        }

        return $result;
    }
}

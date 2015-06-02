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
            // не нашли на указанном уровне, удаляем отовсюду
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

    public static function enqueueFillMatchesJob($user_id, $priority = 10) {
        \Queue::push('fill_matches', ['user_id' => $user_id], 'fill_matches', ['priority' => $priority]);
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

        if (! $sex) {
            return true;
        }

        $sex = implode(", ", $sex);

        // считаем, что индекс уже построен (хотя будем строить прямо сейчас
        // чтобы не напороться на регулярные обновления)
        \DB::select("
            UPDATE public.users_matches
                SET last_reindexed_at = now()
                WHERE user_id = ?
        ", [$user_id]);

        self::createMatchesTables($user_id);

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

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {

            $liked_users = Likes::getLikedUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);
            if (! $liked_users) {
                $liked_users = '0';
            }

            $likes_users = Likes::getLikesUsers($user_id, $i * $limit, ($i + 1) * $limit - 1);
            if (! $likes_users) {
                $likes_users = '0';
            }

            // селект на основании foreign table
            self::getConnection($user_id)->select("
                SET synchronous_commit TO off;

                SELECT pg_advisory_xact_lock(hashtext('matching_levels_$user_id'));

                WITH all_users AS (
                    SELECT  ui.user_id AS match_user_id,

                            -- уровень
                            (
                                35.0 * icount(groups_vk_ids & array[$groups_vk_ids]::int[]) / 10.0 +
                                35.0 * icount(friends_vk_ids & array[$friends_vk_ids]::int[]) / 5.0 +
                                10.0 * (:radius - st_distance(geography, (:geography)::geography)::decimal / 1000.0) / :radius +
                                10.0 * popularity +
                                10.0 * friendliness +
                                20.0 * (ui.user_id IN ($likes_users))::integer
                            )
                            ::integer AS matching_level

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

    public static function getMatches($user_id, $limit) {
        self::createMatchesTables($user_id);

        $users_ids = [];
        $iterations = 0;
        $level_id = null;

        // пояснение необходимости итерирование см. ниже
        do {
            $iterations ++;

            // читаем максимальный уровень из таблицы со старыми данными
            // и из таблицы с данными, которые заполняются в настоящее время
            $max_levels_ids = self::getConnection($user_id)->select("
                SELECT  COALESCE((
                            SELECT level_id
                                FROM public.matching_levels_$user_id
                                WHERE icount(users_ids) > 0
                                ORDER BY level_id DESC
                                LIMIT 1
                            ), -1
                        ) AS old_level_id,
                        COALESCE((
                            SELECT level_id
                                FROM public.matching_levels_fresh_$user_id
                                WHERE icount(users_ids) > 0
                                ORDER BY level_id DESC
                                LIMIT 1
                            ), -1
                        ) AS fresh_level_id;
            ")[0];

            $max_old_level_id = $max_levels_ids->old_level_id;
            $max_fresh_level_id = $max_levels_ids->fresh_level_id;

            if ($max_fresh_level_id >= $max_old_level_id and $max_fresh_level_id >= 0) {
                // в строящейся таблице данные лучше, читаем из нее
                $table = 'fresh_';
                $level_id = $max_fresh_level_id;
            } else if ($max_old_level_id >= $max_fresh_level_id and $max_old_level_id >= 0) {
                // в старой таблице данные лучше, читаем из нее
                $table = '';
                $level_id = $max_old_level_id;
            } else {
                // уровни в обеих таблицах пусты, то есть пользователей нет ни там, ни там
                break;
            }

            $users_ids = self::getConnection($user_id)->select("
                SELECT array_to_string(subarray(users_ids, 0, $limit), ',') AS users_ids
                    FROM public.matching_levels_{$table}{$user_id}
                    WHERE level_id = ?;
            ", [$level_id]);

            // за то время, которое прошло между считыванием уровня и считыванием пользователей на нем
            // таблица fresh могла быть отротирована в таблицу без суффикса
            // а новая fresh становится пустой
            // поэтому сейчас мы могли считать пустой набор пользователей
            // поэтому повторим итерацию
            if (sizeof($users_ids)) {
                $users_ids = explode(',', $users_ids[0]->users_ids);
            }
            // $users_ids может остаться пустым

        // повторяем пока не найдем, на всякий случай ограничим еще и число итераций
        } while (! $users_ids and $iterations < 5);

        // запасной вариант
        if (! sizeof($users_ids)) {

        }

        return [
            'users_ids' => $users_ids,
            'weight_level' => $level_id,
        ];
    }

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

            // посылаем на перестраивание
            self::enqueueFillMatchesJob($user_id, 0); // 0 - приоритет
        }

        return implode(', ', $list);
    }

}

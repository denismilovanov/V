<?php namespace App\Models;

class UsersMatches
{
    public static function deleteMatch($user_id, $match_user_id) {
        \DB::select("
            DELETE FROM public.users_matches
                WHERE   user_id = ? AND
                        match_user_id = ?;
        ", [$user_id, $match_user_id]);
    }

    public static function jobFillMatches($user_id) {
        \Queue::push('fill_matches', ['user_id' => $user_id], 'fill_matches');
    }

    public static function fillMatchesInUsersMatches($user_id) {
        $settings = Users::getMySettings($user_id);
        $checkin = Users::getMyCheckin($user_id);

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
            DELETE FROM public.users_matches
                WHERE user_id = :user_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {
            \DB::select("
                SET synchronous_commit TO off;
                INSERT INTO public.users_matches
                    SELECT  :user_id,
                            ui.user_id AS match_user_id,
                            st_distance(geography, geography(ST_MakePoint(:longitude, :latitude)))::integer AS distance,

                            -- порядок для подсчета весов
                            icount(friends_vk_ids) +
                            icount(groups_vk_ids) +
                            0
                            AS processing_order,

                            -- вес посчитаем потом
                            NULL AS weight

                        FROM public.users_index AS ui

                        LEFT JOIN public.likes AS l
                            ON  l.user1_id = :user_id AND
                                l.user2_id = ui.user_id

                        WHERE   user_id BETWEEN $i * $limit AND ($i + 1) * $limit - 1 AND
                                ST_DWithin(geography, geography(ST_MakePoint(:longitude, :latitude)), :radius * 1000) AND
                                age BETWEEN :age_from AND :age_to AND
                                sex IN ($sex) AND
                                l.user1_id IS NULL;
            ", [
                'user_id' => $user_id,
                'age_from' => $settings->age_from,
                'age_to' => $settings->age_to,
                'radius' => $settings->radius,
                'latitude' => $checkin->latitude,
                'longitude' => $checkin->longitude,
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

        $users_ids = \DB::select("
            SET synchronous_commit TO off;
            WITH lock AS (
                SELECT pg_advisory_xact_lock(hashtext('users_matches_$user_id'))
            ),
            ids AS (
                SELECT match_user_id
                    FROM public.users_matches
                    WHERE   user_id = :user_id AND
                            weight IS NULL
                    ORDER BY processing_order DESC
                    LIMIT 500
            ),
            upd AS (
                UPDATE public.users_matches AS m

                    SET weight = icount(i.friends_vk_ids & array[$friends_vk_ids]::int[]) +
                                 icount(i.groups_vk_ids & array[$groups_vk_ids]::int[])

                    FROM public.users_index AS i
                    WHERE   m.user_id = :user_id AND
                            m.match_user_id IN (SELECT match_user_id FROM ids) AND
                            i.user_id = m.match_user_id
            )
            SELECT string_agg(match_user_id::varchar, ',') AS users_ids
                FROM ids;
        ", [
            'user_id' => $user_id,
        ])[0]->users_ids;

        if ($users_ids) {
            /*\DB::select("
                SET synchronous_commit TO off;
                UPDATE public.users_matches AS m

                    SET weight = icount(i.friends_vk_ids & array[$friends_vk_ids]::int[]) +
                                 icount(i.groups_vk_ids & array[$groups_vk_ids]::int[])

                    FROM public.users_index AS i
                    WHERE   m.user_id = ? AND
                            m.match_user_id IN ($users_ids) AND
                            i.user_id = m.match_user_id;
            ", [$user_id]);*/

            \Queue::push('update_weights', ['user_id' => $user_id], 'update_weights');
        }

        return true;
    }

    public static function getMatches($me_id) {
        $users = \DB::select("
            SELECT match_user_id AS user_id, weight, (distance / 1000.)::integer AS distance
                FROM public.users_matches
                WHERE   user_id = ? AND
                        weight IS NOT NULL
                ORDER BY weight DESC
                LIMIT 50;
        ", [$me_id]);

        if (! sizeof($users)) {
            //
        }

        return $users;
    }
}

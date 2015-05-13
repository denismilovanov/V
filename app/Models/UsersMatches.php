<?php namespace App\Models;

use App\Models\PushQueue;

class UsersMatches
{
    public static function deleteMatch($user_id, $match_user_id) {
        \DB::select("
            DELETE FROM public.users_matches
                WHERE   user_id = ? AND
                        match_user_id = ?;
        ", [$user_id, $match_user_id]);
    }

    public static function fillCandidatesInUsersMatches($user_id) {
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
            DELETE FROM public.users_matches
                WHERE user_id = :user_id;
        ", [
            'user_id' => $user_id,
        ]);

        $limit = 10000;
        $users_max_id = Users::getMaxId();

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {
            $shard = $user_id % 2;
            $u = \DB::select("
                INSERT INTO public.users_matches$shard
                    SELECT  :user_id,
                            ui.user_id AS match_user_id,
                            st_distance(geography, geography(ST_MakePoint(:longitude, :latitude)))::integer AS distance,
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
    }

    public static function updateWeights($user_id) {
        $users_ids = \DB::select("
            WITH ids AS (
                SELECT match_user_id
                    FROM public.users_matches
                    WHERE   user_id = ? AND
                            weight IS NULL
                    ORDER BY distance DESC
                    LIMIT 100
            )
            SELECT string_agg(match_user_id::varchar, ',') AS users_ids
                FROM ids;
        ", [$user_id])[0]->users_ids;

        if ($users_ids) {



            \DB::select("
                UPDATE public.users_matches
                    SET weight = 1. / match_user_id
                    WHERE   user_id = ? AND
                            match_user_id IN ($users_ids);
            ", [$user_id]);
        }
    }

    public static function getMatches($me_id) {
        self::fillCandidatesInUsersMatches($me_id);

        self::updateWeights($me_id);

        return \DB::select("
            SELECT match_user_id AS user_id, weight, (distance / 1000.)::integer AS distance
                FROM public.users_matches
                WHERE   user_id = ? AND
                        weight IS NOT NULL
                ORDER BY weight DESC
                LIMIT 50;
        ", [$me_id]);
    }
}

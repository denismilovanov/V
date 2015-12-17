<?php namespace App\Models;

class Stats {

    public static function getAgesData() {
        $data = \DB::select("
            SELECT extract('year' from age(bdate)) AS age, count(*) AS count
                FROM public.users
                WHERE   id NOT BETWEEN 100000 AND 299999 AND
                        extract('year' from age(bdate)) IS NOT NULL
                GROUP BY extract('year' from age(bdate))
                ORDER BY extract('year' from age(bdate));
        ");

        $result = [];

        foreach ($data as $row) {
            $result []= [$row->age, (int)$row->count];
        }

        return $result;
    }

    public static function getRegistrationsData() {
        $data = \DB::select("
            SELECT date_trunc('day', registered_at)::date AS date, count(*) AS count
                FROM public.users
                WHERE   id NOT BETWEEN 100000 AND 299999 AND
                        registered_at > now() - interval '2 months'
                GROUP BY date_trunc('day', registered_at)::date
                ORDER BY date_trunc('day', registered_at)::date;
        ");

        $result = [];

        foreach ($data as $row) {
            $result []= [$row->date, (int)$row->count];
        }

        return $result;
    }

    public static function getGeoData() {
        $data = \DB::select("
            SELECT  region_id, city_id,
                    sum(1) AS count,
                    sum(CASE WHEN sex = 1 THEN 1 ELSE 0 END) AS count_females,
                    sum(CASE WHEN sex = 2 THEN 1 ELSE 0 END) AS count_males
                FROM public.users_index
                WHERE user_id NOT BETWEEN 100000 AND 299999
                GROUP BY region_id, city_id
                HAVING sum(1) > 10
                ORDER BY sum(1) DESC
                LIMIT 100;
        ");

        foreach ($data as $row) {
            $city = \DB::connection('gis')->select("
                SELECT name AS geo
                    FROM planet_osm_polygon
                    WHERE osm_id = ?;
            ", [$row->city_id]);
            $row->city = isset($city[0]) ? $city[0]->geo : '';

            $region = \DB::connection('gis')->select("
                SELECT name AS geo
                    FROM planet_osm_polygon
                    WHERE osm_id = ?;
            ", [$row->region_id]);
            $row->region = isset($region[0]) ? $region[0]->geo : '';
        }

        return $data;
    }

    public static function getActivityData() {
        $data = \DB::select("
            SELECT  date,
                    active_males_count,
                    active_females_count,
                    active_males_count + active_females_count AS active_all_count
                FROM stats.daily
                WHERE date > now() - interval '2 months'
                ORDER BY date;
        ");

        $result = [
            'males' => [],
            'females' => [],
            'all' => [],
        ];

        foreach ($data as $row) {
            $result['males'] []= [$row->date, (int)$row->active_males_count];
            $result['females'] []= [$row->date, (int)$row->active_females_count];
            $result['all'] []= [$row->date, (int)$row->active_all_count];
        }

        return $result;
    }

    public static function getLikesActivityData() {
        $data = \DB::select("
            SELECT  date,
                    likes_count AS all_likes,
                    male_likes_female_count + male_likes_male_count AS male_likes,
                    female_likes_male_count + female_likes_female_count AS female_likes
                FROM stats.daily
                WHERE date > now() - interval '2 months'
                ORDER BY date;
        ");

        $result = [
            'males_likes' => [],
            'females_likes' => [],
            'all_likes' => [],
        ];

        foreach ($data as $row) {
            $result['males_likes'] []= [$row->date, (int)$row->male_likes];
            $result['females_likes'] []= [$row->date, (int)$row->female_likes];
            $result['all_likes'] []= [$row->date, (int)$row->all_likes];
        }

        return $result;
    }

    public static function getMatchesActivityData() {
        $data = \DB::select("
            SELECT  date,
                    matches_count
                FROM stats.daily
                WHERE date > now() - interval '2 months'
                ORDER BY date;
        ");

        $result = [
            'matches_count' => [],
        ];

        foreach ($data as $row) {
            $result['matches_count'] []= [$row->date, (int)$row->matches_count];
        }

        return $result;
    }

    public static function getMatchesMonthsActivityData() {
        $data = \DB::select("
            SELECT  date_trunc('month', date)::date AS date,
                    avg(matches_count) AS matches_count
                FROM stats.daily
                GROUP BY date_trunc('month', date)::date
                ORDER BY date_trunc('month', date)::date;
        ");

        $result = [
            'matches_count' => [],
        ];

        foreach ($data as $row) {
            $row->date = date("m.Y", strtotime($row->date));
            $result['matches_count'] []= [$row->date, (float)sprintf("%.2f", $row->matches_count)];
        }

        return $result;
    }

    public static function whoLikesWhoData() {
        $data = \DB::select("
            SELECT  sum(male_likes_female_count) AS male_likes_female_count,
                    sum(female_likes_male_count) AS female_likes_male_count,
                    sum(male_likes_male_count) AS male_likes_male_count,
                    sum(female_likes_female_count) AS female_likes_female_count,
                    sum(likes_count) AS likes_count
                FROM stats.daily
                WHERE date > now() - interval '2 months'
        ");

        return $data[0];
    }

    public static function genderData() {
        $data = \DB::select("
            SELECT  sum(CASE WHEN sex = 1 THEN 1 ELSE 0 END) AS females_count,
                    sum(CASE WHEN sex = 2 THEN 1 ELSE 0 END) AS males_count,
                    sum(1) AS users_count
                FROM public.users
                WHERE   id NOT BETWEEN 100000 AND 299999;
        ");

        return $data[0];
    }

    public static function processEvent($data) {
        // событие - лайк
        if ($data['type'] == 'like') {
            $from_user_sex = Users::findById($data['from_user_id'])->sex == 1 ? 'female' : 'male';
            $to_user_sex = Users::findById($data['to_user_id'])->sex == 1 ? 'female' : 'male';

            $date = date("Y-m-d", strtotime($data['ts']));

            if ($data['is_like'] == 1) {
                \DB::select("
                    UPDATE stats.daily
                        SET {$from_user_sex}_likes_{$to_user_sex}_count = {$from_user_sex}_likes_{$to_user_sex}_count + 1,
                            likes_count = likes_count + 1
                        WHERE date = ?
                ", [$date]);
            }

            $action_active = $data['is_like'] == 1 ? 'likes' : 'dislikes';
            $action_passive = $data['is_like'] == 1 ? 'liked' : 'disliked';

            \DB::select("
                UPDATE stats.users_overall
                    SET {$action_active}_count = {$action_active}_count + 1
                    WHERE user_id = ?
            ", [$data['from_user_id']]);

            \DB::select("
                UPDATE stats.users_overall
                    SET {$action_passive}_count = {$action_passive}_count + 1
                    WHERE user_id = ?
            ", [$data['to_user_id']]);

        // событие - матч
        } else if ($data['type'] == 'match') {
            $date = date("Y-m-d", strtotime($data['ts']));

            // обновляем число мачтей в системе
            \DB::select("
                UPDATE stats.daily
                    SET matches_count = matches_count + 1
                    WHERE date = ?
            ", [$date]);

            // обновляем число матчей пользователю
            \DB::select("
                UPDATE stats.users_overall
                    SET matches_count = matches_count + 1
                    WHERE user_id = ?
            ", [$data['from_user_id']]);

            // обновляем число матчей пользователю
            \DB::select("
                UPDATE stats.users_overall
                    SET matches_count = matches_count + 1
                    WHERE user_id = ?
            ", [$data['to_user_id']]);

        // событие - некоторая активность (нужна для статы по активности)
        } else if ($data['type'] == 'activity') {
            $user_sex = Users::findById($data['user_id'])->sex == 1 ? 'female' : 'male';

            $date = date("Y-m-d", strtotime($data['ts']));

            \DB::select("
                UPDATE stats.daily
                    SET active_{$user_sex}s_count = active_{$user_sex}s_count + 1
                    WHERE date = ?
            ", [$date]);
        }

        return true;
    }

    public static function createTodayStatsRecord() {
        \DB::select("
            INSERT INTO stats.daily
                SELECT current_date
                    WHERE NOT EXISTS (
                        SELECT 1
                            FROM stats.daily
                            WHERE date = current_date
                            LIMIT 1
                    );
        ");
    }

}

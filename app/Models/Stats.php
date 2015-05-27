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
            $result []= [$row->age, $row->count];
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
            $result []= [$row->date, $row->count];
        }

        return $result;
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
            $result['males'] []= [$row->date, $row->active_males_count];
            $result['females'] []= [$row->date, $row->active_females_count];
            $result['all'] []= [$row->date, $row->active_all_count];
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
            $result['males_likes'] []= [$row->date, $row->male_likes];
            $result['females_likes'] []= [$row->date, $row->female_likes];
            $result['all_likes'] []= [$row->date, $row->all_likes];
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
                FROM public.users;
        ");

        return $data[0];
    }

}
<?php namespace App\Models;

class Checkins {

    public static function checkin($user_id, $longitude, $latitude, $fill_matches = true) {
        $city_id = null;
        $region_id = null;

        $geography = \DB::connection('gis')->select("
            SELECT * FROM public.get_geography(?, ?)
        ", [$longitude, $latitude]);

        if ($geography) {
            $city_id = $geography[0]->city_id;
            $region_id = $geography[0]->region_id;
        }

        if ($latitude and $longitude) {
            $result = \DB::select("
                SELECT public.checkin(?, ?, ?, ?, ?);
            ", [$user_id, $latitude, $longitude, $city_id, $region_id]);
        }

        // важно было записать географию в базу прежде, чем создавать этот джоб
        // чтобы он получил новую географию
        if ($fill_matches) {
            UsersMatches::enqueueFillMatchesJob($user_id);
        }

        return [
            'result' => $result,
            'geography' => $geography,
        ];
    }

}

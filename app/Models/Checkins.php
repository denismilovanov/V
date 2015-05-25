<?php namespace App\Models;

class Checkins {

    public static function checkin($user_id, $longitude, $latitude, $fill_matches = true) {
        $osm_id = null;

        $gis = \DB::connection('gis')->select("
            SELECT name, osm_id
                FROM planet_osm_polygon
                WHERE   ST_Within(ST_GeometryFromText('POINT($longitude $latitude)', 4326), way) AND
                        admin_level IN ('4')
                ORDER BY place NULLS LAST
                LIMIT 1
        ");

        if ($gis) {
            $osm_id = abs($gis[0]->osm_id);
        }

        $result = \DB::select("
            SELECT public.checkin(?, ?, ?, ?);
        ", [$user_id, $latitude, $longitude, $osm_id]);

        // важно было записать географию в базу прежде, чем создавать этот джоб
        // чтобы он получил новую географию
        if ($fill_matches) {
            UsersMatches::jobFillMatches($user_id);
        }

        return $result;
    }

}

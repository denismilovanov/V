<?php namespace App\Models;

class Checkins {

    public static function checkin($user_id, $longitude, $latitude) {
        return \DB::select("
            SELECT public.checkin(?, ?, ?);
        ", [$user_id, $latitude, $longitude]);
    }

}

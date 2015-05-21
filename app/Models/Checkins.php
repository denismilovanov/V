<?php namespace App\Models;

class Checkins {

    public static function checkin($user_id, $longitude, $latitude) {
        $result = \DB::select("
            SELECT public.checkin(?, ?, ?);
        ", [$user_id, $latitude, $longitude]);

        // важно было записать географию в базу прежде, чем создавать этот джоб
        // чтобы он получил новую географию
        UsersMatches::jobFillMatches($user_id);

        return $result;
    }

}

<?php namespace App\Models;

class Abuses {

    public static function deleteAllFromTo($from_user_id, $to_user_id) {
        return \DB::select("
            DELETE FROM public.abuses
                WHERE   from_user_id = ? AND
                        to_user_id = ?
                RETURNING *;
        ", [$from_user_id, $to_user_id]);
    }

}

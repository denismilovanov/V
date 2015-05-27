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

    public static function abuse($from_user_id, $to_user_id, $text) {
        if (! Users::findById($to_user_id)) {
            return false;
        }

        return \DB::select("
            SELECT public.add_abuse(?, ?, ?);
        ", [$from_user_id, $to_user_id, $text])[0]->add_abuse;
    }

    public static function remove($abuse_id) {
        \DB::select("
            DELETE FROM public.abuses
                WHERE id = ?;
        ", [$abuse_id]);
    }

    public static function removeAllByToUserId($to_user_id) {
        \DB::select("
            DELETE FROM public.abuses
                WHERE to_user_id = ?
        ", [$to_user_id]);
    }

}

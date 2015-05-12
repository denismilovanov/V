<?php namespace App\Models;

class Likes {

    public static function deleteAllBetween($from_user_id, $to_user_id) {
        return \DB::select("
            DELETE FROM public.likes
                WHERE   user1_id = ? AND
                        user2_id = ?;
            DELETE FROM public.likes
                WHERE   user1_id = ? AND
                        user2_id = ?;
        ", [$from_user_id, $to_user_id, $to_user_id, $from_user_id]);
    }

}

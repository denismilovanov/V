<?php namespace App\Models;

class PushQueue {

    public static function enqueuePush($push_type, $to_user_id, $from_user_id) {
        $push_type_id = 0;
        if ($push_type == 'MATCH') {
            $push_type_id = 1;
        }
        \DB::select("
            SELECT public.add_push(?, ?, ?)
        ", [$push_type_id, $to_user_id, $from_user_id]);
    }

}

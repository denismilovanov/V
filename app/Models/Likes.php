<?php namespace App\Models;

class Likes {

    public static function like($from_user_id, $to_user_id, $is_like) {
        if (! Users::findById($to_user_id)) {
            return false;
        }

        \DB::select("
            SELECT public.upsert_like(?, ?, ?);
        ", [$from_user_id, $to_user_id, $is_like]);

        $mutual = 0;

        if ($is_like === "1") {

            $mutual_row = \DB::select("
                SELECT *
                    FROM public.likes
                    WHERE   user1_id = ? AND
                            user2_id = ? AND
                            NOT is_blocked;
            ", [$to_user_id, $from_user_id]);

            if (sizeof($mutual_row)) {
                $mutual = 1;

                // второму посылаем пуш через очередь
                \Queue::push('matches', [
                    'to_user_id' => $to_user_id,
                    'from_user_id' => $from_user_id,
                ], 'matches');
            }
        }

        UsersMatches::deleteMatch($from_user_id, $to_user_id);

        return [
            'mutual' => $mutual,
        ];
    }

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

    public static function isMutual($from_user_id, $to_user_id) {
        return \DB::select("
            SELECT COUNT(*) AS c
                FROM public.likes
                WHERE   (user1_id = ? AND
                        user2_id = ?) OR
                        (user1_id = ? AND
                        user2_id = ?);
        ", [$from_user_id, $to_user_id, $to_user_id, $from_user_id])[0]->c == 2;
    }

    public static function blockUser($from_user_id, $to_user_id, $reason) {
        if (! Users::findById($to_user_id)) {
            return false;
        }

        return sizeof(\DB::select("
            UPDATE public.likes
                SET is_blocked = 't',
                    reason = ?
                WHERE   user1_id = ? AND
                        user2_id = ?
                RETURNING *;
        ", [$reason, $from_user_id, $to_user_id])) > 0;
    }

}

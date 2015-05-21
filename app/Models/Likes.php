<?php namespace App\Models;

use \App\Http\Controllers\ApiController;

class Likes {

    public static function like($from_user_id, $to_user_id, $is_like) {
        if (! Users::findById($to_user_id) or $from_user_id == $to_user_id) {
            return false;
        }

        $is_new = \DB::select("
            SELECT public.upsert_like(?, ?, ?);
        ", [$from_user_id, $to_user_id, $is_like])[0]->upsert_like;

        if (! $is_new) {
            return false;
        }

        $mutual = 0;

        if ($is_like === 1) {

            $mutual_row = \DB::select("
                SELECT *
                    FROM public.likes
                    WHERE   user1_id = ? AND
                            user2_id = ? AND
                            NOT is_blocked;
            ", [$to_user_id, $from_user_id]);

            if (sizeof($mutual_row)) {

                $mutual = 1;

                Messages::createDialog($from_user_id, $to_user_id);

                // надо ли послать пуш второму?
                $to_user_settings = Users::getMySettings($to_user_id);

                if ($to_user_settings->is_notification and $to_user_settings->is_notification_likes) {
                    // второму посылаем пуш через очередь
                    \Queue::push('push_matches', [
                        'to_user_id' => $to_user_id,
                        'from_user_id' => $from_user_id,
                    ], 'push_matches');
                }

            } else {

                // если мы лайкнули тестового, то он может ответить
                if (Users::isTestUser($to_user_id)) {
                    \Queue::push('echo_likes', [
                        'from_user_id' => $from_user_id,
                        'to_user_id' => $to_user_id,
                    ], 'echo_likes');
                }

            }
        }

        UsersMatches::deleteMatch($from_user_id, $to_user_id);

        return [
            'mutual' => $mutual,
        ];
    }

    public static function echoLike($data) {
        if (mt_rand() / mt_getrandmax() <= env('ECHO_LIKE_PROBABILITY', 0.20)) {
            $to_user_id = $data['from_user_id'];
            $from_user_id = $data['to_user_id'];

            ApiController::$user = Users::findById($from_user_id);

            // лайкаем
            $mutual = self::like($from_user_id, $to_user_id, 1);

            // пишем сообщение
            $message = 'Привет, ' . Users::findById($to_user_id)->name .  '!';
            Messages::addMessage($from_user_id, $to_user_id, $message);

            return [
                'mutual' => $mutual,
                'message' => $message,
            ];
        }

        return 'Не делаем встречный лайк.';
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

    public static function isBlocked($from_user_id, $to_user_id) {
        return \DB::select("
            SELECT COUNT(*) AS c
                FROM public.likes
                WHERE   user1_id = ? AND
                        user2_id = ? AND
                        NOT is_blocked;
        ", [$from_user_id, $to_user_id])[0]->c != 1;
    }

    public static function blockUser($from_user_id, $to_user_id) {
        if (! Users::findById($to_user_id)) {
            return false;
        }

        Messages::blockDialog($from_user_id, $to_user_id);

        return sizeof(\DB::select("
            UPDATE public.likes
                SET is_blocked = 't'
                WHERE   user1_id = ? AND
                        user2_id = ?
                RETURNING *;
        ", [$from_user_id, $to_user_id])) > 0;
    }

}

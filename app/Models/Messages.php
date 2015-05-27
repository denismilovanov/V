<?php namespace App\Models;

use \App\Http\Controllers\ApiController;

class Messages {

    public static function deleteAllBetween($from_user_id, $to_user_id) {
        return \DB::select("
            DELETE FROM public.messages_new
                WHERE   me_id = ? AND
                        buddy_id = ?;
            DELETE FROM public.messages_new
                WHERE   me_id = ? AND
                        buddy_id = ?;
            DELETE FROM public.messages_dialogs
                WHERE   me_id = ? AND
                        buddy_id = ?;
            DELETE FROM public.messages_dialogs
                WHERE   me_id = ? AND
                        buddy_id = ?;
        ", [$from_user_id, $to_user_id, $to_user_id, $from_user_id,
            $from_user_id, $to_user_id, $to_user_id, $from_user_id]);
    }

    public static function addMessage($from_user_id, $to_user_id, $text) {
        if (! Users::findById($to_user_id)) {
            return [
                'message_id' => '',
                'added_at' => '',
            ];
        }

        $message_id = \DB::select(
            "SELECT public.add_message(?, ?, ?, 't');
        ", [$from_user_id, $to_user_id, $text])[0]->add_message;

        // вставляем парное сообщение только если получатель не заблокировал отправителя
        if (! Likes::isBlocked($to_user_id, $from_user_id)) {
            // потенциально это может уйти на другую базу, которая обслуживает второго пользователя
            $message_id_pair = \DB::select(
                "SELECT public.add_message(?, ?, ?, 'f');
            ", [$to_user_id, $from_user_id, $text])[0]->add_message;

            // надо ли послать пуш второму?
            $to_user_settings = Users::getMySettings($to_user_id);

            if ($to_user_settings->is_notification and $to_user_settings->is_notification_messages) {
                // отправляем пуш второму
                \Queue::push('push_messages', [
                    'from_user_id' => $from_user_id,
                    'to_user_id' => $to_user_id,
                    'message' => $text,
                ], 'push_messages');
            }

            // эхо-юзеры
            if (in_array($to_user_id, [100000, 200000]) and ! in_array($from_user_id, [100000, 200000])) {
                \Queue::push('echo_messages', [
                    'from_user_id' => $from_user_id,
                    'to_user_id' => $to_user_id,
                    'message' => $text,
                ], 'echo_messages');
            }
        }

        //
        $added_at = \DB::select("
            SELECT public.format_date(created_at, ?) AS added_at
                FROM public.messages_new
                WHERE id = ?;
        ", [ApiController::$user->time_zone, $message_id])[0]->added_at;

        return [
            'message_id' => $message_id,
            'added_at' => $added_at,
        ];
    }

    public static function createDialog($from_user_id, $to_user_id) {
        \DB::select("
            INSERT INTO public.messages_dialogs
                (me_id, buddy_id, last_message, last_message_i, is_new)
                VALUES (
                    ?,
                    ?,
                    '',
                    't',
                    'f'
                );
        ", [$from_user_id, $to_user_id]);

        \DB::select("
            INSERT INTO public.messages_dialogs
                (me_id, buddy_id, last_message, last_message_i, is_new)
                VALUES (
                    ?,
                    ?,
                    '',
                    't',
                    'f'
                );
        ", [$to_user_id, $from_user_id]);
    }

    public static function echoMessage($data) {
        $to_user_id = $data['from_user_id'];
        $from_user_id = $data['to_user_id'];
        $message = 'ECHO: ' . $data['message'];

        ApiController::$user = Users::findById($from_user_id);

        return [
            'echo' => $message,
            'message_id' => self::addMessage($from_user_id, $to_user_id, $message)['message_id'],
        ];
    }

    public static function getAllBetweenUsers($me_id, $buddy_id, $older_than, $later_than) {
        $sql = "
            SELECT  id,
                    public.format_date(created_at, :time_zone) AS added_at,
                    message,
                    CASE WHEN i THEN 2 ELSE 1 END AS direction
                FROM public.messages_new
                WHERE   me_id = :me_id AND
                        buddy_id = :buddy_id";

        $data = [
            'me_id' => $me_id,
            'buddy_id' => $buddy_id,
            'time_zone' => ApiController::$user->time_zone
        ];

        if (! is_null($older_than)) {
            $sql .= ' AND id < :older_than ';
            $data['older_than'] = $older_than;
        }

        if (! is_null($later_than)) {
            $sql .= ' AND id > :later_than ';
            $data['later_than'] = $later_than;
        }

        $sql .= "
            ORDER BY id DESC
            LIMIT 50;
        ";

        $messages = \DB::select($sql, $data);

        // после того как сообщения выданы в устройство считаем их старыми
        \DB::select("
            UPDATE public.messages_new
                SET is_new = 'f'
                WHERE   me_id = ? AND
                        buddy_id = ?;
            UPDATE public.messages_dialogs
                SET is_new = 'f'
                WHERE   me_id = ? AND
                        buddy_id = ?;
        ", [$me_id, $buddy_id, $me_id, $buddy_id]);

        return $messages;
    }

    public static function getMessages($user_id, $limit = 100, $offset = 0) {
        $chats = \DB::select("
            SELECT
                -- с кем
                d.buddy_id AS id,
                --
                public.format_date(d.created_at, ?) AS created_at,
                d.last_message,
                d.is_new

                FROM public.messages_dialogs AS d
                WHERE   d.me_id = ? AND
                        NOT d.is_buddy_blocked
                ORDER BY d.updated_at DESC
                LIMIT ? OFFSET ?
        ", [ApiController::$user->time_zone, $user_id, $limit, $offset]);

        // собираем айди
        $buddies_ids = [];

        foreach ($chats as $chat) {
            $buddies_ids []= $chat->id;
        }

        // получаем всех сразу
        $buddies = Users::findByIds($buddies_ids);

        // подтягиваем в список чатов недостающее - имя и аватар
        foreach ($chats as $chat) {
            $buddy = $buddies[$chat->id];

            if (! $buddy) {
                continue;
            }

            $chat->name = $buddy->name;
            $chat->avatar_url = $buddy->avatar_url;
        }

        return $chats;
    }

    public static function blockDialog($me_id, $buddy_id) {
        \DB::select("
            UPDATE public.messages_dialogs
                SET is_buddy_blocked = 't'
                WHERE   me_id = ? AND
                        buddy_id = ?;
        ", [$me_id, $buddy_id]);
    }

}

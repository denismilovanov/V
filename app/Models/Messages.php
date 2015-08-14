<?php namespace App\Models;

use \App\Http\Controllers\ApiController;
use Redis;

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
        if  (! Users::findById($to_user_id) or
            // нельзя послать сообщение тестовому, если ты обычный пользователь
            (! Users::isDeveloperOrTestUser($from_user_id) and Users::isTestUser($to_user_id))) {
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

            // эхо
            if (Users::isTestUser($to_user_id) and ! Users::isTestUser($from_user_id)) {
                \Queue::push('echo_messages', [
                    'from_user_id' => $from_user_id,
                    'to_user_id' => $to_user_id,
                    'message' => $text,
                ], 'echo_messages');
            }

            // открыт ли сокет?
            $socket_id = null;
            try {
                $socket_id = app('redis')->hget('users_ids_to_socket_ids', $to_user_id);
            } catch (\Exception $e) {
                ;
            }

            // надо забросить в сокет
            if ($socket_id) {
                $msg = self::packForSocketIo([[
                    'type' => 2,
                    'data' => ['message', [
                        'message_id' => $message_id_pair,
                        'user_id' => $from_user_id,
                        'message' => $text,
                    ]],
                    'nsp' => '/',
                ], [
                   'rooms' => [$socket_id],
                   'flags' => [],
                ]]);


                try {
                    app('redis')->publish('socket.io#emitter', $msg);
                } catch (\Exception $e) {
                    ;
                }
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

    private static function correctLastMessageinDialogs($me_id, $buddy_id) {
        \DB::select("
            UPDATE public.messages_dialogs
                SET last_message = COALESCE((
                        SELECT message
                            FROM public.messages_new
                            WHERE   me_id = :me_id AND
                                    buddy_id = :buddy_id AND
                                    NOT is_deleted
                            ORDER BY created_at DESC
                            LIMIT 1
                    ), '')
                WHERE   me_id = :me_id AND
                        buddy_id = :buddy_id;
        ", [
            'me_id' => $me_id,
            'buddy_id' => $buddy_id,
        ]);
    }

    public static function deleteMessage($user_id, $message_id) {
        $message = \DB::select("
            UPDATE public.messages_new
                SET is_deleted = TRUE
                WHERE   id = ? AND
                        me_id = ? AND
                        NOT is_deleted
                RETURNING buddy_id;
        ", [$message_id, $user_id]);

        if ($message) {
            self::correctLastMessageinDialogs($user_id, $message[0]->buddy_id);
        }

        return (bool)$message;
    }

    public static function deleteMessagesWithUser($me_id, $buddy_id) {
        $dialog = \DB::select("
            SELECT count(1) AS c
                FROM public.messages_dialogs
                WHERE   me_id = ? AND
                        buddy_id = ?;
        ", [$me_id, $buddy_id])[0]->c > 0;

        if (! $dialog) {
            return false;
        }

        $messages = \DB::select("
            UPDATE public.messages_new
                SET is_deleted = TRUE
                WHERE   me_id = ? AND
                        buddy_id = ? AND
                        NOT is_deleted
                RETURNING id
        ", [$me_id, $buddy_id]);

        if ($messages) {
            self::correctLastMessageinDialogs($me_id, $buddy_id);
        }

        return true;
    }

    public static function createDialog($from_user_id, $to_user_id) {
        \DB::select("
            INSERT INTO public.messages_dialogs
                (me_id, buddy_id, last_message, last_message_i, is_new)
                SELECT
                    ?,
                    ?,
                    '',
                    't',
                    'f'
                WHERE NOT EXISTS (
                    SELECT 1
                        FROM public.messages_dialogs
                        WHERE   me_id = ? AND
                                buddy_id = ?
                );
        ", [$from_user_id, $to_user_id, $from_user_id, $to_user_id]);

        \DB::select("
            INSERT INTO public.messages_dialogs
                (me_id, buddy_id, last_message, last_message_i, is_new)
                SELECT
                    ?,
                    ?,
                    '',
                    't',
                    'f'
                WHERE NOT EXISTS (
                    SELECT 1
                        FROM public.messages_dialogs
                        WHERE   me_id = ? AND
                                buddy_id = ?
                );
        ", [$to_user_id, $from_user_id, $to_user_id, $from_user_id]);
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
                    CASE WHEN i THEN 2 ELSE 1 END AS direction,
                    is_read
                FROM public.messages_new
                WHERE   me_id = :me_id AND
                        buddy_id = :buddy_id AND
                        NOT is_deleted ";

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
            ORDER BY created_at DESC
            LIMIT 50;
        ";

        $messages = \DB::select($sql, $data);

        // после того как сообщения выданы в устройство считаем их старыми
        // и прочитанными
        \DB::select("
            UPDATE public.messages_new
                SET is_new = 'f',
                    is_read = 't'
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
                public.format_date(d.created_at, :time_zone) AS created_at,
                public.format_date(d.updated_at, :time_zone) AS last_message_added_at,
                d.last_message,
                d.is_new

                FROM public.messages_dialogs AS d
                WHERE   d.me_id = :user_id AND
                        NOT d.is_buddy_blocked
                ORDER BY d.updated_at DESC
                LIMIT :limit OFFSET :offset
        ", [
            'time_zone' => ApiController::$user->time_zone,
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        // собираем айди
        $buddies_ids = [];

        foreach ($chats as $chat) {
            $buddies_ids []= $chat->id;
        }

        // получаем всех сразу
        $buddies = Users::findByIds($buddies_ids, 'dialogs');

        // подтягиваем в список чатов недостающее - имя и аватар
        foreach ($chats as $chat) {
            $buddy = $buddies[$chat->id];

            if (! $buddy) {
                continue;
            }

            $chat->name = $buddy->name;
            $chat->avatar_url = $buddy->avatar_url;
            $chat->is_blocked = $buddy->is_blocked;
            $chat->is_deleted = $buddy->is_deleted;
        }

        return $chats;
    }

    public static function getCountDialogsWithNewMessages($user_id) {
        return \DB::select("
            SELECT count(1) AS c
                FROM public.messages_dialogs AS d
                WHERE   d.me_id = ? AND
                        NOT d.is_buddy_blocked AND
                        d.is_new;
        ", [$user_id])[0]->c;
    }

    public static function blockDialog($me_id, $buddy_id) {
        \DB::select("
            UPDATE public.messages_dialogs
                SET is_buddy_blocked = 't'
                WHERE   me_id = ? AND
                        buddy_id = ?;
        ", [$me_id, $buddy_id]);
    }

    private static function packForSocketIo($input) {
        static $bigendian;
        if (!isset($bigendian)) $bigendian = (pack('S', 1) == pack('n', 1));

        // null
        if (is_null($input)) {
          return pack('C', 0xC0);
        }

        // booleans
        if (is_bool($input)) {
          return pack('C', $input ? 0xC3 : 0xC2);
        }

        // Integers
        if (is_int($input)) {
            // positive fixnum
            if (($input | 0x7F) == 0x7F) return pack('C', $input & 0x7F);
            // negative fixnum
            if ($input < 0 && $input >= -32) return pack('c', $input);
            // uint8
            if ($input > 0 && $input <= 0xFF) return pack('CC', 0xCC, $input);
            // uint16
            if ($input > 0 && $input <= 0xFFFF) return pack('Cn', 0xCD, $input);
            // uint32
            if ($input > 0 && $input <= 0xFFFFFFFF) return pack('CN', 0xCE, $input);
            // uint64
            if ($input > 0 && $input <= 0xFFFFFFFFFFFFFFFF) {
               // pack() does not support 64-bit ints, so pack into two 32-bits
               $h = ($input & 0xFFFFFFFF00000000) >> 32;
               $l = $input & 0xFFFFFFFF;
               return $bigendian ? pack('CNN', 0xCF, $l, $h) : pack('CNN', 0xCF, $h, $l);
            }
            // int8
            if ($input < 0 && $input >= -0x80) return pack('Cc', 0xD0, $input);
            // int16
            if ($input < 0 && $input >= -0x8000) {
               $p = pack('s', $input);
               return pack('Ca2', 0xD1, $bigendian ? $p : strrev($p));
            }
            // int32
            if ($input < 0 && $input >= -0x80000000) {
               $p = pack('l', $input);
               return pack('Ca4', 0xD2, $bigendian ? $p : strrev($p));
            }
            // int64
            if ($input < 0 && $input >= -0x8000000000000000) {
               // pack() does not support 64-bit ints either so pack into two 32-bits
               $p1 = pack('l', $input & 0xFFFFFFFF);
               $p2 = pack('l', ($input >> 32) & 0xFFFFFFFF);
               return $bigendian ? pack('Ca4a4', 0xD3, $p1, $p2) : pack('Ca4a4', 0xD3, strrev($p2), strrev($p1));
            }
            throw new \InvalidArgumentException('Invalid integer: ' . $input);
        }

        // Floats
        if (is_float($input)) {
            // Just pack into a double, don't take any chances with single precision
            return pack('C', 0xCB) . ($bigendian ? pack('d', $input) : strrev(pack('d', $input)));
        }

        // Strings/Raw
        if (is_string($input)) {
            $len = strlen($input);
            if ($len < 32) {
               return pack('Ca*', 0xA0 | $len, $input);
            } else if ($len <= 0xFFFF) {
               return pack('Cna*', 0xDA, $len, $input);
            } else if ($len <= 0xFFFFFFFF) {
               return pack('CNa*', 0xDB, $len, $input);
            } else {
               throw new \InvalidArgumentException('Input overflows (2^32)-1 byte max');
            }
        }

        // Arrays & Maps
        if (is_array($input)) {
            $keys = array_keys($input);
            $len = count($input);

            // Is this an associative array?
            $isMap = false;
            foreach ($keys as $key) {
               if (!is_int($key)) {
                    $isMap = true;
                    break;
               }
            }

            $buf = '';
            if ($len < 16) {
               $buf .= pack('C', ($isMap ? 0x80 : 0x90) | $len);
            } else if ($len <= 0xFFFF) {
               $buf .= pack('Cn', ($isMap ? 0xDE : 0xDC), $len);
            } else if ($len <= 0xFFFFFFFF) {
               $buf .= pack('CN', ($isMap ? 0xDF : 0xDD), $len);
            } else {
               throw new \InvalidArgumentException('Input overflows (2^32)-1 max elements');
            }

            foreach ($input as $key => $elm) {
               if ($isMap) $buf .= self::packForSocketIo($key);
               $buf .= self::packForSocketIo($elm);
            }
            return $buf;
        }

        throw new \InvalidArgumentException('Not able to pack/serialize input type: ' . gettype($input));
    }

}

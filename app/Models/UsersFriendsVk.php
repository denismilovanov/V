<?php namespace App\Models;

class UsersfriendsVk
{
    /*
     * Обновление списка друзей пользователя
     */

    public static function setUserFriendsVk($user_id, $friends_ids)
    {
        $friends_ids = array_slice($friends_ids, 0, 5000);
        $friends = [];

        foreach ($friends_ids as $record) {
            if (! is_scalar($record) or ! is_int($record)) {
                continue;
            }
            $friends []= $record;
        }

        return \DB::select("
            SELECT public.set_user_friends_vk(?, array[" . implode(', ', $friends) . "]::integer[]);
        ", [$user_id]);
    }
}


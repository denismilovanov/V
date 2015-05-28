<?php namespace App\Models;

class UsersfriendsVk
{
    /*
     * Обновление списка друзей пользователя
     */

    public static function setUserFriendsVk($user_id, $friends_ids)
    {
        $friends_ids = array_slice($friends_ids, 0, 5000);

        return \DB::select("
            SELECT public.set_user_friends_vk(?, array[" . implode(', ', $friends_ids) . "]::integer[]);
        ", [$user_id]);
    }
}


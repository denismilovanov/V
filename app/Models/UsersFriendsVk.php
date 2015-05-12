<?php namespace App\Models;

class UsersfriendsVk
{
    /*
     * Обновление списка друзей пользователя
     */

    public static function setUserFriendsVk($user_id, $friends_ids)
    {
        return \DB::select("
            SELECT public.set_user_friends_vk(?, array[" . implode(', ', $friends_ids) . "]::integer[]);
        ", [$user_id]);
    }
}


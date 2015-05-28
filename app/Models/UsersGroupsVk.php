<?php namespace App\Models;

class UsersGroupsVk
{
    /*
     * Обновление списка групп пользователя
     */

    public static function setUserGroupsVk($user_id, $groups_ids)
    {
        $groups_ids = array_slice($groups_ids, 0, 5000);

        return \DB::select("
            SELECT public.set_user_groups_vk(?, array[" . implode(', ', $groups_ids) . "]::integer[]);
        ", [$user_id]);
    }

    /*
     * Получение списка групп пользователя
     */

    public static function getAllByUserId($user_id)
    {
        return \DB::select("
            SELECT g.name, g.photo_url
                FROM public.users_groups_vk AS ugv
                INNER JOIN public.groups_vk AS g
                    ON g.id = ugv.group_id
                WHERE ugv.user_id = :user_id
                ORDER BY g.name
        ", [$user_id]);
    }
}

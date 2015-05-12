<?php namespace App\Models;

class GroupsVk
{
    /*
     * Добавление или обновление группы
     */

    public static function upsert($id, $name, $photo_url)
    {
        return \DB::select("
            SELECT public.upsert_group_vk(?, ?, ?);
        ", [$id, $name, $photo_url])[0]->upsert_group_vk;
    }
}

<?php namespace App\Models;

class GroupsVk
{
    /*
     * Добавление или обновление группы
     */

    public static function upsert($id, $name, $photo_url) {
        return \DB::select("
            SELECT public.upsert_group_vk(?, ?, ?);
        ", [$id, $name, $photo_url])[0]->upsert_group_vk;
    }

    public static function getRandomGroupsIds($count) {
        $groups = \DB::select("
            SELECT id
                FROM public.groups_vk
                ORDER BY random()
                LIMIT ?;
        ", [$count]);

        $groups_ids = [];

        foreach ($groups as $group) {
            $groups_ids []= $group->id;
        }

        return $groups_ids;
    }
}

<?php namespace App\Models;

use App\Models\PushQueue;

class Users
{
    /*
     *
     */

    public static function syncGroupsVK($user_id, $groups) {
        if(function_exists('pinba_timer_start')){
            $timer = pinba_timer_start(array('query' => 'authVK', 'resource' => 'DB_updateGroups'));
        }

        // собираем айдишки
        $groups_ids = array();

        foreach($groups as $group)
        {
            $group['gid'] = intval($group['id']);

            // вставляем группу, если ее еще нет
            GroupsVk::upsert(
                $group['gid'],
                $group['name'],
                $group['photo_200']
            );

            $groups_ids []= $group['gid'];
        }

        // линкуем пользователя на группы
        UsersGroupsVk::setUserGroupsVk(
            $user_id,
            $groups_ids
        );

        if(function_exists('pinba_timer_stop')){
            pinba_timer_stop($timer);
        }

        return true;
    }

    public static function syncFriendsVK($user_id, $friends) {
        if(function_exists('pinba_timer_start')){
            $timer = pinba_timer_start(array('query' => 'authVK', 'resource' => 'DB_updateFriends'));
        }

        UsersFriendsVk::setUserFriendsVk(
            $user_id,
            $friends
        );

        if(function_exists('pinba_timer_stop')){
            pinba_timer_stop($timer);
        }

        return true;
    }

    public static function getSettings($user_id) {
        $settings = \DB::select("
            SELECT *
                FROM public.users_settings
                WHERE user_id = ?;
        ", [$user_id]);

        if (! isset($settings[0])) {
            return [];
        }

        $settings = $settings[0];

        return [
            'sex' => $settings->sex,
            'radius' => $settings->radius,
            'age_from' => $settings->age_from,
            'age_to' => $settings->age_to,
            'is_show_male' => $settings->is_show_male,
            'is_show_female' => $settings->is_show_female,
            'is_notification' => $settings->is_notification,
            'is_notification_likes' => $settings->is_notification_likes,
            'is_notification_messages' => $settings->is_notification_messages,
        ];
    }

    public static function setSettings($user_id, $sex, $radius, $age_from, $age_to, $is_show_male,
                                         $is_show_female, $is_notification,
                                         $is_notification_likes, $is_notification_messages) {

        \DB::select("
            UPDATE public.users_settings
                SET sex = ?, radius = ?, age_from = ?, age_to = ?,
                    is_show_male = ?, is_show_female = ?,
                    is_notification = ?, is_notification_likes = ?, is_notification_messages = ?
                WHERE user_id = ?;

            UPDATE public.users
                SET sex = ?
                WHERE id = ?;
            ", [
                $sex, $radius, $age_from, $age_to, $is_show_male,
                $is_show_female, $is_notification,
                $is_notification_likes, $is_notification_messages,
                $user_id,

                $sex,
                $user_id
            ]);

        return true;
    }

    public static function setDeviceToken($user_id, $key, $device_token, $device_type) {
        if (! $device_token or ! $device_type) {
            return false;
        }

        return sizeof(\DB::select("
            UPDATE public.users_devices
                SET device_token = ?,
                    device_type = ?
                WHERE   user_id = ? AND
                        key = ?
                RETURNING *;
        ", [$device_token, $device_type, $user_id, $key])) > 0;
    }

    public static function logout($user_id, $key) {
        return sizeof(\DB::select("
            DELETE FROM public.users_devices
                WHERE   user_id = ? AND
                        key = ?
                RETURNING *;
        ", [$user_id, $key])) > 0;
    }

    public static function findById($user_id) {
        $user = \DB::select("
            SELECT *
                FROM public.users
                WHERE id = ?;
        ", [$user_id]);

        return $user ? $user[0] : null;
    }

    public static function findByIds($users_ids) {
        if (! $users_ids) {
            return [];
        }

        $users = \DB::select("
            SELECT *
                FROM public.users
                WHERE id IN (" . implode(', ', $users_ids) . ")
        ");

        $result = [];

        foreach ($users as $user) {
            $result[$user->id] = $user;
        }

        return $result;
    }
}

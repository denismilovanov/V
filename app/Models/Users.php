<?php namespace App\Models;

use App\Models\UsersMatches;

class Users
{
    public static function upsertByVkId($vk_id, $sex, $name, $bdate, $about, $avatar_url) {
        $sql = "SELECT * FROM public.upsert_user_by_vk_id(?, ?, ?, ?, ?, ?) AS t(user_id integer, is_new integer);";
        $user = \DB::select($sql, [$vk_id, $sex, $name, $bdate, $about, $avatar_url])[0];
        return $user;
    }

    public static function getAccessKey($user_id, $device_token, $device_type, $soft_version_int) {
        $sql = "SELECT public.get_access_key(?, ?, ?, ?);";
        $key = \DB::select($sql, [$user_id, $device_token, $device_type, $soft_version_int])[0]->get_access_key;
        return $key;
    }

    public static function getLatestSoftVersion($device_type) {
        $sql = "SELECT * FROM public.get_latest_soft_version(?) AS t(version integer, description text);";
        $soft = \DB::select($sql, [$device_type])[0];
        return $soft;
    }

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

    public static function getMySettings($user_id) {
        $user_settings = \DB::select("
            SELECT *
                FROM public.users_settings
                WHERE user_id = ?;
        ", [$user_id]);

        return $user_settings ? $user_settings[0] : null;
    }

    public static function getMyCheckin($user_id) {
        $user_checkin = \DB::select("
            SELECT latitude, longitude
                FROM public.checkins
                WHERE user_id = ?;
        ", [$user_id]);

        return $user_checkin ? $user_checkin[0] : null;
    }

    public static function getMySearchWeightParams($user_id) {
        $user_weight_params = \DB::select("
            SELECT  array_to_string(groups_vk_ids, ',') AS groups_vk_ids,
                    array_to_string(friends_vk_ids, ',') AS friends_vk_ids
                FROM public.users_index
                WHERE user_id = ?;
        ", [$user_id]);

        return $user_weight_params ? $user_weight_params[0] : null;
    }

    public static function getProfile($user_id, $viewer_id) {
        $profile = \DB::select("
            SELECT * FROM public.get_user_profile(?, ?);
        ", [$user_id, $viewer_id]);

        if (! $profile or ! isset($profile[0])) {
            return null;
        }

        return $profile[0];
    }

    public static function isTestUser($user_id) {
        return $user_id >= 100000 and $user_id < 300000;
    }

    public static function findById($user_id) {
        $user = \DB::select("
            SELECT *
                FROM public.users
                WHERE id = ?;
        ", [$user_id]);

        if (! $user or ! isset($user[0])) {
            return null;
        }

        $user = $user[0];
        $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);

        return $user;
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
            $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);
            $result[$user->id] = $user;
        }

        return $result;
    }

    public static function getDevices($user_id) {
        return \DB::select("
            SELECT *
                FROM public.users_devices
                WHERE user_id = ?
                ORDER BY updated_at DESC; -- свежие наверх
        ", [$user_id]);
    }

    public static function getMaxId() {
        return \DB::select("SELECT max(id) AS max FROM public.users;")[0]->max;
    }

    public static function searchAround($me_id) {
        $users = UsersMatches::getMatches($me_id);

        $users_ids = [];

        foreach ($users as $user) {
            $users_ids []= $user->user_id;
        }

        $users_all = self::findByIds($users_ids);

        foreach ($users as $user) {
            $user->name = $users_all[$user->user_id]->name;
            $user->avatar_url = $users_all[$user->user_id]->avatar_url;
        }

        return $users;
    }
}

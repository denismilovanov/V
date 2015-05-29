<?php namespace App\Models;

use \App\Http\Controllers\ApiController;
use App\Models\UsersMatches;

class Users
{
    public static function upsertByVkId($vk_id, $sex, $name, $bdate, $about, $avatar_url, $time_zone) {
        $sql = "SELECT * FROM public.upsert_user_by_vk_id(?, ?, ?, ?, ?, ?, ?) AS t(user_id integer, is_new integer);";
        $user = \DB::select($sql, [$vk_id, $sex, $name, $bdate, $about, $avatar_url, $time_zone])[0];
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

    public static function updateLastActivity($user_id, $need_to_trigger_activity_event) {
        // не имеет смысл делать это на каждый запрос
        if (mt_rand() / mt_getrandmax() <= 0.20) {
            \DB::select("
                UPDATE public.users_index
                    SET last_activity_at = now()
                    WHERE user_id = ?;
            ", [$user_id]);

            if ($need_to_trigger_activity_event) {
                // для заполнения статистики
                \Queue::push('events_for_stats', [
                    'ts' => date("Y-m-d H:i:s"),
                    'type' => 'activity',
                    'user_id' => $user_id,
                ], 'events_for_stats');
            }
        }
    }

    public static function getExistingAccessKey($user_id) {
        if (! self::findById($user_id)) {
            return '';
        }

        $key = \DB::select("
            SELECT key
                FROM public.users_devices
                WHERE user_id = ?
                ORDER BY updated_at DESC
                LIMIT 1;
        ", [$user_id]);

        // может не быть записи, либо ключ может быть сброшен в NULL
        if (isset($key[0]) and $key[0]->key) {
            // если это не так, то выдаем
            return $key[0]->key;
        }

        return self::getAccessKey($user_id, str_repeat('0', 64), 1, 20000);
    }

    public static function syncGroupsVK($user_id, $groups) {
        UsersGroupsVk::setUserGroupsVk(
            $user_id,
            $groups
        );

        return true;
    }

    public static function syncFriendsVK($user_id, $friends) {
        UsersFriendsVk::setUserFriendsVk(
            $user_id,
            $friends
        );

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
            'sex' => self::findById($user_id)->sex,
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

        $old_settings = self::getSettings($user_id);

        if (! $old_settings) {
            return false;
        }

        \DB::select("
            UPDATE public.users_settings
                SET radius = ?, age_from = ?, age_to = ?,
                    is_show_male = ?, is_show_female = ?,
                    is_notification = ?, is_notification_likes = ?, is_notification_messages = ?
                WHERE user_id = ?;

            UPDATE public.users
                SET sex = ?
                WHERE id = ?;

            UPDATE public.users_index
                SET sex = ?
                WHERE user_id = ?;
            ", [
                $radius, $age_from, $age_to, $is_show_male,
                $is_show_female, $is_notification,
                $is_notification_likes, $is_notification_messages,
                $user_id,

                $sex,
                $user_id,

                $sex,
                $user_id
            ]);

        // надо пересчитать подходящих?
        if ($old_settings['sex'] != $sex or
            $old_settings['radius'] != $radius or
            $old_settings['age_from'] != $age_from or
            $old_settings['age_to'] != $age_to or
            $old_settings['is_show_female'] != $is_show_female or
            $old_settings['is_show_male'] != $is_show_male
        ) {
            // надо (делаем это после апдейта базы, кот. был выше)
            UsersMatches::jobFillMatches($user_id);
        }

        return true;
    }

    public static function setAbout($user_id, $about) {
        \DB::select("
            UPDATE public.users
                SET about = ?
                WHERE id = ?;
        ", [$about, $user_id]);

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

    public static function getMyGeography($user_id) {
        $user_geography = \DB::select("
            SELECT geography, city_id, region_id
                FROM public.users_index
                WHERE user_id = ?;
        ", [$user_id]);

        $geography = $city_id = $region_id = null;

        if ($user_geography) {
            $user_geography = $user_geography[0];
            $geography = $user_geography->geography;
            $city_id = $user_geography->city_id;
            $region_id = $user_geography->region_id;
        }

        return [
            'geography' => $geography,
            'city_id' => $city_id,
            'region_id' => $region_id,
        ];
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

    public static function isTestUser($user_id) {
        return $user_id >= 100000 and $user_id < 300000;
    }

    public static function getRandomTestUserId() {
        return 100000 + (int)(200000 * mt_rand() / mt_getrandmax());
    }

    public static function findByKey($key) {
        $user = \DB::select("
            SELECT  u.id, u.sex,
                    u.name,
                    u.about,
                    u.avatar_url,
                    u.vk_id,
                    date_part('year', age(u.bdate)) as age,
                    u.time_zone,
                    ui.geography,
                    date(ui.last_activity_at) != current_date AS need_to_trigger_activity_event
                FROM users_devices AS ud
                INNER JOIN users AS u
                    ON u.id = ud.user_id
                INNER JOIN users_index AS ui
                    ON ui.user_id = ud.user_id
                WHERE ud.key = ?;
        ", [$key]);

        if ($user) {
            $user = $user[0];
            $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);
            return $user;
        }

        return null;
    }

    public static function findByVkId($vk_id) {
        $user = \DB::select("
            SELECT id
                FROM public.users
                WHERE vk_id = ?  -- uniq
                LIMIT 1;
        ", [$vk_id]);

        if ($user) {
            $user = $user[0];
            return self::findById($user->id);
        }

        return null;
    }

    public static function findById($user_id, $area = '') {
        if (! $area) {
            $user = \DB::select("
                SELECT *
                    FROM public.users
                    WHERE id = ?;
            ", [$user_id]);
        } else if ($area == 'getUserProfile') {
            $search_weights_params = Users::getMySearchWeightParams(ApiController::$user->id);

            $friends_vk_ids = $search_weights_params->friends_vk_ids;
            $groups_vk_ids = $search_weights_params->groups_vk_ids;

            $user = \DB::select("
                SELECT  u.*,
                        extract('year' from age(u.bdate)) AS age,
                        public.format_date(i.last_activity_at, ?) AS last_activity,
                        icount(i.groups_vk_ids) AS groups_count,
                        icount(i.friends_vk_ids) AS friends_count,
                        round(ST_Distance(i.geography, ?)::decimal / 1000) AS distance,
                        public.get_weight_level(
                            icount(i.groups_vk_ids & array[$groups_vk_ids]::int[]),
                            icount(i.friends_vk_ids & array[$friends_vk_ids]::int[])
                        ) AS weight_level
                    FROM public.users AS u
                    INNER JOIN public.users_index AS i
                        ON i.user_id = u.id
                    WHERE u.id = ?;
            ", [ApiController::$user->time_zone, ApiController::$user->geography, $user_id]);
        } else if ($area == 'admin') {
            $user = \DB::select("
                SELECT  u.*,
                        CASE WHEN u.sex = 1 THEN 'F' ELSE 'M' END AS gender,
                        extract('year' from age(u.bdate)) AS age,
                        public.format_date(i.last_activity_at) AS last_activity,
                        public.format_date(registered_at) AS registered_at,
                        us.*,
                        public.format_date(i.last_activity_at) AS last_activity_at,
                        (SELECT count(*) FROM public.abuses WHERE to_user_id = u.id) AS abuses_count
                    FROM public.users AS u
                    INNER JOIN public.users_index AS i
                        ON i.user_id = u.id
                    INNER JOIN stats.users_overall AS us
                        ON us.user_id = u.id
                    WHERE u.id = ?;
            ", [$user_id]);
        }

        if (! $user or ! isset($user[0])) {
            return null;
        }

        $user = $user[0];
        $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);

        // идентификаторы тестовых пользователей отрицательные
        if (isset($user->vk_id) and $user->vk_id < 0) {
            $user->vk_id = $user->sex == 1 ? 308890 : 1;
        }

        if ($area == 'admin') {
            $user->abuses = \DB::select("
                SELECT a.*, u.name AS from_name, u.id AS from_id
                    FROM public.abuses AS a
                    INNER JOIN public.users AS u
                        ON a.from_user_id = u.id
                    WHERE a.to_user_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT 50;
            ", [$user->id]);

            $user->photos = UsersPhotos::getUserPhotos($user_id);
        }

        return $user;
    }

    public static function findByIds($users_ids, $area = '', $additional_data = []) {
        if (! $users_ids) {
            return [];
        }

        if (! $area) {
            $users = \DB::select("
                SELECT *
                    FROM public.users
                    WHERE id IN (" . implode(', ', $users_ids) . ")
            ");
        } else if ($area == 'searchAround') {
            $search_weights_params = Users::getMySearchWeightParams(ApiController::$user->id);

            $friends_vk_ids = $search_weights_params->friends_vk_ids;
            $groups_vk_ids = $search_weights_params->groups_vk_ids;

            $users = \DB::select("
                SELECT  u.id,
                        u.name,
                        u.avatar_url,
                        u.vk_id,
                        u.sex,
                        public.format_date(i.last_activity_at, :time_zone) AS last_activity_at,
                        icount(i.groups_vk_ids) AS groups_vk_count,
                        icount(i.friends_vk_ids) AS friends_vk_count,
                        0 AS photos_count,
                        round(ST_Distance(i.geography, :geography)::decimal / 1000) AS distance,
                        :weight_level AS weight_level
                    FROM public.users AS u
                    INNER JOIN public.users_index AS i
                        ON i.user_id = u.id
                    WHERE u.id IN (" . implode(', ', $users_ids) . ")
            ", [
                'time_zone' => ApiController::$user->time_zone,
                'geography' => ApiController::$user->geography,
                'weight_level' => $additional_data['weight_level'],
            ]);
        }

        $result = [];

        foreach ($users as $user) {
            $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);

            // идентификаторы тестовых пользователей отрицательные
            if (isset($user->vk_id) and $user->vk_id < 0) {
                $user->vk_id = $user->sex == 1 ? 308890 : 1;
            }

            $result[$user->id] = $user;
        }

        return $result;
    }

    public static function getUsersIdsBetween($from_id, $to_id, $area = '') {
        if ($area == 'mass_push') {
            return \DB::select("
                SELECT u.id
                    FROM public.users AS u
                    INNER JOIN public.users_settings AS us
                        ON us.user_id = u.id
                    WHERE   u.id BETWEEN ? AND ? AND
                            us.is_notification;
            ", [$from_id, $to_id]);
        }
    }

    public static function getDevices($user_id) {
        return \DB::select("
            SELECT *
                FROM public.users_devices
                WHERE   user_id = ? AND
                        device_token IS NOT NULL
                ORDER BY updated_at DESC; -- свежие наверх
        ", [$user_id]);
    }

    public static function getMaxId() {
        return \DB::select("SELECT max(id) AS max FROM public.users;")[0]->max;
    }

    public static function searchAround($me_id, $limit) {
        $users = UsersMatches::getMatches($me_id, $limit);
        $users_all = self::findByIds($users['users_ids'], 'searchAround', ['weight_level' => $users['weight_level']]);
        return array_values($users_all);
    }

    public static function getUsersForAdmin($action, $limit, $offset) {
        $users = [];

        if ($action == 'all') {
            $users = \DB::select("
                SELECT u.id
                    FROM public.users AS u
                    ORDER BY u.id DESC
                    LIMIT ? OFFSET ?
            ", [$limit, $offset]);
        } else if ($action == 'search_with_abuses') {
            $users = \DB::select("
                WITH a AS (
                    SELECT DISTINCT to_user_id
                        FROM public.abuses
                )
                SELECT u.id
                    FROM public.users AS u
                    WHERE   u.id IN (SELECT to_user_id FROM a) AND
                            NOT is_blocked
                    ORDER BY u.id ASC
                    LIMIT ? OFFSET ?
            ", [$limit, $offset]);
        }

        foreach ($users as $user) {
            $user = self::findById($user->id, 'admin');
            if ($user) {
                yield $user;
            }
        }
    }

    public static function removeOldKeys() {
        return \DB::select("
            UPDATE public.users_devices
                SET key = NULL,
                    updated_at = now()
                WHERE   updated_at < now() - interval '1 day' AND
                        key IS NOT NULL
                RETURNING user_id;
        ");
    }

    public static function block($user_id) {
        return \DB::select("
            UPDATE public.users
                SET is_blocked = 't'
                WHERE id = ?;
        ", [$user_id]);
    }

    public static function unblock($user_id) {
        return \DB::select("
            UPDATE public.users
                SET is_blocked = 'f'
                WHERE id = ?;
        ", [$user_id]);
    }
}

<?php namespace App\Models;

use \App\Http\Controllers\ApiController;
use App\Models\UsersMatches;
use App\Models\UsersIndex;
use App\Models\Likes;


class Users
{
    public static function upsertByVkId($vk_id, $sex, $name, $bdate, $about, $avatar_url, $time_zone, $access_token) {
        $date = @ strtotime($bdate);
        $years = floor((time() - $date) / (60 * 60 * 24 * 365));
        if (! $date or $years <= 1 or $years >= 100) {
            $bdate = null;
        }
        if ($bdate == '1970-01-01') {
            $bdate = null;
        }

        $sql = "SELECT * FROM public.upsert_user_by_vk_id(?, ?, ?, ?, ?, ?, ?) AS t(user_id integer, is_new integer);";
        $user = \DB::select($sql, [$vk_id, $sex, $name, $bdate, $about, $avatar_url, $time_zone])[0];
        $user_record = self::findById($user->user_id);
        $user->is_blocked = $user_record->is_blocked;

        \DB::select("
            UPDATE public.users
                SET vk_access_token = ?,
                    avatar_url = ?
                WHERE id = ?;
        ", [$access_token, $avatar_url, $user->user_id]);

        if ($user->is_new) {
            \Queue::push('get_audio_vk', ['user_id' => $user->user_id], 'get_audio_vk');
        }

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

        // друзья были переданы ранее
        // пересчитаем индекс
        UsersMatches::enqueueFillMatchesJob($user_id);

        return true;
    }

    public static function syncFriendsVK($user_id, $friends) {
        UsersFriendsVk::setUserFriendsVk(
            $user_id,
            $friends
        );

        return true;
    }

    public static function syncProfileVK($user_id, $profile) {
        $keys = ['occupation', 'activities', 'interests', 'music', 'movies', 'tv', 'books', 'games', 'quotes', 'personal'];

        foreach ($keys as $key) {
            if (! isset($profile[$key])) {
                $profile[$key] = null;
            }

            if (! is_scalar($profile[$key]) and $key != 'personal') {
                $profile[$key] = null;
            }

            if (is_scalar($profile[$key]) and ! $profile[$key]) {
                $profile[$key] = null;
            }
        }

        \DB::select("
            UPDATE public.users_profiles_vk
                SET occupation = ?,
                    activities = ?,
                    interests = ?,

                    music = ?,
                    movies = ?,
                    tv = ?,

                    books = ?,
                    games = ?,

                    quotes = ?,
                    personal = ?
                WHERE user_id = ?;
        ", [
            $profile['occupation'], $profile['activities'], $profile['interests'],
            $profile['music'], $profile['movies'], $profile['tv'],
            $profile['books'], $profile['games'],
            $profile['quotes'], json_encode($profile['personal'], JSON_UNESCAPED_UNICODE),
            $user_id
        ]);

        \Queue::push('update_profile_vk', ['user_id' => $user_id], 'update_profile_vk');

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
                SET sex = ?,
                    is_show_male = ?, is_show_female = ?
                WHERE user_id = ?;
        ", [
            $radius, $age_from, $age_to,
            $is_show_male, $is_show_female,
            $is_notification, $is_notification_likes, $is_notification_messages,
            $user_id,

            $sex,
            $user_id,

            $sex,
            $is_show_male, $is_show_female,
            $user_id,
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
            UsersMatches::enqueueFillMatchesJob($user_id);
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

    public static function setBDate($user_id, $bdate) {
        if (! preg_match("~^\d{4}-\d{2}-\d{2}$~uixs", $bdate)) {
            $bdate = intval($bdate);
            if ($bdate < 0 or $bdate > 100) {
                return false;
            }
            $bdate = (date('Y') - $bdate) . '-' . date("m-d");
        }

        try {
            \DB::select("
                UPDATE public.users
                    SET bdate = ?
                    WHERE id = ?;
            ", [$bdate, $user_id]);
        } catch (\Exception $e) {
            return false;
        }

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
                    array_to_string(friends_vk_ids, ',') AS friends_vk_ids,
                    array_to_string(audio_vk_ids, ',') AS audio_vk_ids,
                    array_to_string(universities_vk_ids, ',') AS universities_vk_ids,
                    array_to_string(activities_vk_ids, ',') AS activities_vk_ids,
                    array_to_string(interests_vk_ids, ',') AS interests_vk_ids,
                    array_to_string(books_vk_ids, ',') AS books_vk_ids,
                    array_to_string(games_vk_ids, ',') AS games_vk_ids,
                    array_to_string(movies_vk_ids, ',') AS movies_vk_ids,
                    array_to_string(music_vk_ids, ',') AS music_vk_ids
                FROM public.users_index
                WHERE user_id = ?;
        ", [$user_id]);

        return $user_weight_params ? $user_weight_params[0] : null;
    }

    public static function isTestUser($user_id) {
        return $user_id < self::getMinRealUserId() and $user_id >= 100000;
    }

    public static function getMinRealUserId() {
        // у нас 200000к тестовых пользователей от 100000 до 299999
        return 300000;
    }

    public static function getMinId($me_id) {
        if (env('APP_ENV') == 'test') {
            return 0;
        }
        // разработчикам и тестовым пользователям видны все пользователи начиная с 0
        // для всех остальных видны только настоящие пользователи
        return self::isDeveloperOrTestUser($me_id) ? 0 : self::getMinRealUserId();
    }

    public static function getRandomTestUserId() {
        $magic_lower_limit = 100000;
        return $magic_lower_limit + (int)((self::getMinRealUserId() - $magic_lower_limit) * mt_rand() / mt_getrandmax());
    }

    public static function isDeveloperUser($user_id) {
        return in_array($user_id, explode(',', env('DEVELOPERS_IDS')));
    }

    public static function isDeveloperOrTestUser($user_id) {
        return self::isTestUser($user_id) or self::isDeveloperUser($user_id);
    }

    private static function correctVkId($vk_id, $sex) {
        if ($vk_id < 0) {
            // идентификаторы тестовых пользователей отрицательные
            return $sex == 1 ? env('VK_TEST_FEMALE_ID') : env('VK_TEST_MALE_ID');
        }
        return $vk_id;
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
        if (! $area or $area == 'like' or $area == 'only_user') {
            $user = \DB::select("
                SELECT *
                    FROM public.users
                    WHERE id = ?;
            ", [$user_id]);
        } else if ($area == 'getUserProfile') {
            $w = Users::getMySearchWeightParams(ApiController::$user->id);
            $my_settings = self::getMySettings(ApiController::$user->id);

            $user = \DB::select("
                SELECT  u.id, u.vk_id, u.name, u.sex, u.about, u.is_deleted, u.avatar_url, u.is_blocked,
                        extract('year' from age(u.bdate)) AS age,
                        public.format_date(ui.last_activity_at, :time_zone) AS last_activity_at,
                        icount(ui.groups_vk_ids & array[" . $w->groups_vk_ids . "]::int[]) AS common_groups_vk,
                        icount(ui.friends_vk_ids & array[" . $w->friends_vk_ids . "]::int[]) AS common_friends_vk,
                        round(ST_Distance(ui.geography, :geography)::decimal / 1000) AS distance,
                        :radius, :geography,

                        " . UsersMatches::weightFormula($w, '0', $my_settings->radius) . " AS weight_level

                    FROM public.users AS u
                    INNER JOIN public.users_index AS ui
                        ON ui.user_id = u.id
                    WHERE   u.id = :user_id AND
                            u.id >= :min_user_id
            ", [
                'time_zone' => ApiController::$user->time_zone,
                'geography' => ApiController::$user->geography,
                'radius' => $my_settings->radius,
                'user_id' => $user_id,
                'min_user_id' => self::getMinId(ApiController::$user->id),
            ]);
        } else if ($area == 'admin') {
            $user = \DB::select("
                SELECT  u.*,
                        CASE WHEN u.sex = 1 THEN 'F' ELSE 'M' END AS gender,
                        extract('year' from age(u.bdate)) AS age,
                        public.format_date(i.last_activity_at) AS last_activity,
                        public.format_date(registered_at) AS registered_at,
                        us.*,
                        public.format_date(i.last_activity_at) AS last_activity_at,
                        i.city_id, i.region_id, i.geography,
                        st_x(i.geography::geometry) AS lon,
                        st_y(i.geography::geometry) AS lat,
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
        $user->vk_id = self::correctVkId($user->vk_id, $user->sex);

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

            $user->blocks = Likes::getBlockedUsers($user->id);

            $apps = \DB::select("
                SELECT CASE WHEN device_type = 1 THEN 'ios' ELSE 'android' END || ': ' || soft_version::varchar AS app
                    FROM public.users_devices
                    WHERE user_id = ?;
            ", [$user->id]);

            $user->app = [];
            foreach ($apps as $app) {
                $user->app []= $app->app;
            }
            $user->app = implode(', ', $user->app);

            //
            $city = \DB::connection('gis')->select("
                SELECT name AS geo
                    FROM planet_osm_polygon
                    WHERE osm_id = ?;
            ", [$user->city_id]);
            $region = \DB::connection('gis')->select("
                SELECT name AS geo
                    FROM planet_osm_polygon
                    WHERE osm_id = ?;
            ", [$user->region_id]);

            $user->city = $user->region = '';
            if (isset($city[0])) {
                $user->city = $city[0]->geo;
            }
            if (isset($region[0])) {
                $user->region = $region[0]->geo;
            }

        } else if ($area == 'like') {
            foreach(['updated_at', 'registered_at', 'is_blocked_by_vk', 'is_moderated', 'time_zone'] as $key) {
                unset($user->$key);
            }
        }

        if ($area != 'only_user') {
            $user->photos = UsersPhotos::getUserPhotos($user_id, null, null, $user->sex);
        }

        if ($area == 'getUserProfile' and ! $user->age) {
            $user->age = 0;
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
        } else if ($area == 'dialogs') {
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
                        u.is_deleted,
                        u.is_blocked,
                        u.about,
                        public.format_date(i.last_activity_at, :time_zone) AS last_activity_at,
                        icount(i.groups_vk_ids & array[$groups_vk_ids]::int[]) AS common_groups_vk,
                        icount(i.friends_vk_ids & array[$friends_vk_ids]::int[]) AS common_friends_vk,
                        round(ST_Distance(i.geography, :geography)::decimal / 1000) AS distance,
                        i.age
                    FROM public.users AS u
                    INNER JOIN public.users_index AS i
                        ON i.user_id = u.id
                    WHERE   u.id IN (" . implode(', ', $users_ids) . ") AND
                            u.id >= :max_user_id
            ", [
                'time_zone' => ApiController::$user->time_zone,
                'geography' => ApiController::$user->geography,
                'max_user_id' => self::getMinId(ApiController::$user->id),
            ]);
        }

        // увы, IN не гарантирует порядок, поэтому проведем спецоперацию
        // для начала заиндексируем пользователей
        $users_indexed = [];
        foreach ($users as $user) {
            $users_indexed[$user->id] = $user;
        }
        $users = $users_indexed;

        // результат
        $result = [];

        // проходим в заданном порядке
        foreach ($users_ids as $user_id) {
            if (! isset($users[$user_id])) {
                continue;
            }

            $user = $users[$user_id];

            $user->avatar_url = UsersPhotos::correctAvatar($user->avatar_url, $user->id, $user->sex);
            $user->vk_id = self::correctVkId($user->vk_id, $user->sex);

            if ($area == 'searchAround') {
                $user->photos = UsersPhotos::getUserPhotos($user->id, 1, null, $user->sex);
                # async remove deleted or blocked user from index
                if ($user->is_deleted or $user->is_blocked) {
                    UsersMatches::enqueueRemoveFromIndex($additional_data['for_user_id'], $user->id);
                    $user = null;
                }
                if ($user and ! $user->distance) {
                    $user->distance = 200;
                    UsersMatches::enqueueRemoveFromIndex($additional_data['for_user_id'], $user->id);
                }
            }

            if ($user) {
                $result[$user->id] = $user;
            }
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
        $users_ids = array_keys($users);
        $users_all = self::findByIds($users_ids, 'searchAround', [
            'for_user_id' => $me_id,
        ]);
        foreach ($users_all as $user) {
            $user->weight_level = $users[$user->id]->level_id;
        }
        return array_values($users_all);
    }

    public static function getCountForBadge($user_id) {
        return Messages::getCountDialogsWithNewMessages($user_id);
    }

    public static function getUsersForAdmin($action, $limit, $offset) {
        $users = [];

        if ($action == 'all') {
            $page = $limit;
            $users = \DB::select("
                SELECT u.id
                    FROM public.users AS u
                    WHERE date(registered_at) = (current_date - :days * interval '1 day')::date
                    ORDER BY u.id DESC;
            ", [
                'days' => $page,
            ]);
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

        $result = [];
        foreach ($users as $user) {
            $user = self::findById($user->id, 'admin');
            if ($user) {
                $result []= $user;
            }
        }

        return $result;
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

    public static function block($user_id, $reason) {
        return \DB::select("
            UPDATE public.users
                SET is_blocked = 't',
                    block_reason = ?
                WHERE id = ?;
        ", [$reason, $user_id]);
    }

    public static function unblock($user_id) {
        return \DB::select("
            UPDATE public.users
                SET is_blocked = 'f',
                    block_reason = NULL
                WHERE id = ?;
        ", [$user_id]);
    }

    public static function removeProfile($user_id, $test) {
        \DB::select("
            UPDATE public.users
                SET is_deleted = 't'
                WHERE id = ?;
        ", [$user_id]);

        if (! $test) {
            UsersIndex::removeUser($user_id);
        }

        return true;
    }

    public static function unremove($user_id) {
        \DB::select("
            UPDATE public.users
                SET is_deleted = 'f'
                WHERE id = ?;
        ", [$user_id]);
    }

}

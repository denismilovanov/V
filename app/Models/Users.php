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

    public static function like($from_user_id, $to_user_id, $is_like) {
        \DB::select("
            SELECT public.upsert_like(?, ?, ?);
        ", [$from_user_id, $to_user_id, $is_like]);

        $mutual = 0;

        if ($is_like === "1") {

            $mutual_row = \DB::select("
                SELECT *
                    FROM public.likes
                    WHERE   user1_id = ? AND
                            user2_id = ? AND
                            NOT is_blocked;
            ", [$to_user_id, $from_user_id]);

            if (sizeof($mutual_row)) {
                $mutual = 1;
            }

            // второму посылаем пуш через очередь
            PushQueue::enqueuePush('MATCH', $to_user_id, $from_user_id);
        }

        return [
            'mutual' => $mutual,
        ];
    }

    public static function blockUser($from_user_id, $to_user_id, $reason) {
        return sizeof(\DB::select("
            UPDATE public.likes
                SET is_blocked = 't',
                    reason = ?
                WHERE   user1_id = ? AND
                        user2_id = ?
                RETURNING *;
        ", [$reason, $from_user_id, $to_user_id])) > 0;
    }

    public static function abuse($from_user_id, $to_user_id, $text) {
        return \DB::select("
            SELECT public.add_abuse(?, ?, ?);
        ", [$from_user_id, $to_user_id, $text])[0]->add_abuse;
    }

    public static function getMessages($user_id) {
        return \DB::select("
            SELECT
                likes.user2_id AS id,
                extract(epoch from GREATEST(messages_last.updated_at, messages_last2.updated_at, likes.liked_at)) AS created_at,
                users.avatar_url, users.name,
                CASE
                    WHEN messages_last.updated_at > messages_last2.updated_at OR messages_last2.updated_at IS NULL THEN messages_last.message
                    ELSE messages_last2.message
                END AS last_message,
                CASE
                    WHEN messages_last2.is_new IS NULL THEN likes.is_new
                    ELSE messages_last2.is_new
                END AS is_new
                FROM likes
                INNER JOIN likes l2 ON l2.user1_id = likes.user2_id AND l2.user2_id = likes.user1_id
                LEFT JOIN    messages_last ON
                        messages_last.from_user_id = likes.user1_id AND messages_last.to_user_id = likes.user2_id
                LEFT JOIN    messages_last as messages_last2 ON
                        messages_last2.from_user_id = likes.user2_id AND messages_last2.to_user_id = likes.user1_id
                INNER JOIN users ON users.id = likes.user2_id
                WHERE likes.user1_id = ?
                AND likes.is_liked = TRUE
                AND likes.is_blocked = FALSE
                AND l2.is_liked = true
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
        ", [$user_id, 50, 0]);
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
}

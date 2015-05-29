<?php namespace App\Models;

class Queues {

    public static function push($user_id, $message) {
         \Queue::push('push_system_messages', [
            'user_id' => $user_id,
            'message' => $message,
        ], 'push_system_messages');
    }

    public static function personalPush($user_id, $message) {
        $user = Users::findById($user_id, 'admin');

        if (! $user) {
            return 0;
        }

        $user_settings = Users::getMySettings($user_id);

        if ($user_settings->is_notification) {
            self::push($user_id, $message);
            return 1;
        }

        return 0;
    }

    public static function enqueueMassPush($message) {
        \Queue::push('create_mass_push', [
            'message' => $message,
        ], 'create_mass_push');

        return 1;
    }

    public static function createMassPush($message) {
        $limit = 10000;
        $users_max_id = Users::getMaxId();

        for ($i = 0; $i < ceil($users_max_id / $limit); $i ++) {
            \Log::info('Создаем пачку пушей #' . $i);
            $users = Users::getUsersIdsBetween($i * $limit, ($i + 1) * $limit - 1, 'mass_push');
            foreach ($users as $user) {
                self::push($user->id, $message);
            }
        }

        return 1;
    }

}

<?php namespace App\Models;

class Pusher
{
    private static $apple_pusher = null;
    private static $google_pusher = null;

    public static function getApplePusher() {
        if (self::$apple_pusher) {
            return self::$apple_pusher;
        }

        if (APP_ENV == 'dev' or APP_ENV == 'test') {
            $pusher = new \ApnsPHP_Push(
                \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
                env('APP_PATH') . '/config/ck_develop.pem'
            );
            $pusher->setProviderCertificatePassphrase('qwerty');
        } else if (APP_ENV == 'production') {
            $pusher = new \ApnsPHP_Push(
                \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
                env('APP_PATH') . '/config/ck_production.pem'
            );
            $pusher->setProviderCertificatePassphrase('');
        }

        $pusher->connect();

        return self::$apple_pusher = $pusher;
    }

    public static function disconnect() {
        self::getApplePusher()->disconnect();
    }

    public static function push($data, $type, $given_text = '') {
        if (in_array($type, ['MATCH', 'MESSAGE'])) {
            $from_user = Users::findById($data['from_user_id']);
            $to_user = Users::findById($data['to_user_id']);
        } else if ($type == 'SYSTEM_MESSAGE') {
            $to_user = Users::findById($data['user_id']);
        } else {
            dd($type);
            throw new \Exception('Неподдеживаемый тип пуша ' . $type);
        }

        $devices = Users::getDevices($to_user->id);

        if (! $devices) {
            \Log::info('Нет устройств');
            return true;
        }

        $sent = false;

        foreach ($devices as $device) {
            if ($device->device_type == 1) {
                try {

                    if ($type == 'MATCH') {
                        $text = 'У вас совпадение c ' . Helper::casusInstrumentalis($from_user->name, $from_user->sex);
                    } else if ($type == 'MESSAGE') {
                        $text = 'У вас сообщение от ' . Helper::genitivus($from_user->name, $from_user->sex);
                    } else if ($type == 'SYSTEM_MESSAGE') {
                        $text = $given_text;
                    }

                    \Log::info($type . ': ' . $text . ' -> iOS ' . $device->device_token);

                    if (Users::isTestUser($to_user->id)) {
                        \Log::info('Предназначено тестовому пользователю, пропускаем');
                        return true;
                    }

                    $message = new \ApnsPHP_Message($device->device_token);

                    $message->setText($text);
                    $message->setExpiry(30);
                    $message->setBadge(5);
                    $message->setSound();

                    if (in_array($type, ['MATCH', 'MESSAGE'])) {
                        $message->setCustomProperty('userID', $from_user->id);
                        $message->setCustomProperty('name', $from_user->name);
                        $message->setCustomProperty('avatar_url', $from_user->avatar_url);
                    }

                    $pusher = Pusher::getApplePusher();

                    $pusher->add($message);

                    if (APP_ENV != 'dev') {
                        $pusher->send();
                    } else {
                        \Log::info('Пропускаем (dev)');
                    }

                    $sent = true;
                    \Log::info('Отправлено');

                } catch (\Exception $e) {
                    \Log::error(get_class($e) . ' ' . $e->getMessage());
                    ErrorCollector::addError(
                        'PUSH_LIKES_ERROR',
                        '',
                        json_encode($e->getMessage())
                    );
                }
            } else if ($device->device_type == 2) {
                $sent = true;
                break;
            }
        }

        return $sent;
    }
}

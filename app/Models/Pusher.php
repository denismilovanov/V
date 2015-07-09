<?php namespace App\Models;

use Sly\NotificationPusher\PushManager,
    Sly\NotificationPusher\Adapter\Gcm as GcmAdapter,
    Sly\NotificationPusher\Collection\DeviceCollection,
    Sly\NotificationPusher\Model\Device,
    Sly\NotificationPusher\Model\Message,
    Sly\NotificationPusher\Model\Push;

class GooglePushWrapper extends Push
{
    public static $gcmAdapter = null;

    public function __construct($devices, $message) {
        return parent::__construct(self::$gcmAdapter, $devices, $message);
    }
}

class Pusher
{
    private static $apple_pusher = null;

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
                env('APP_PATH') . '/config/ck_prod.pem'
            );
            $pusher->setProviderCertificatePassphrase('dvtcnt');
        }

        $pusher->connect();

        return self::$apple_pusher = $pusher;
    }

    public static function getGooglePusher() {
        if (! GooglePushWrapper::$gcmAdapter) {
            /*
            $env = null;

            if (APP_ENV == 'dev' or APP_ENV == 'test') {
                $env = PushManager::ENVIRONMENT_DEV;
            } else if (APP_ENV == 'production') {
                $env = PushManager::ENVIRONMENT_PROD;
            }
            */
            GooglePushWrapper::$gcmAdapter = new GcmAdapter([
                'apiKey' => env('GOOGLE_PUSH_API_KEY'),
            ]);
        }

        $pusher = new PushManager();
        return $pusher;
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
            throw new \Exception('Неподдеживаемый тип пуша ' . $type);
        }

        if (! $to_user) {
            throw new \Exception('Нет пользователя ' . $data['user_id']);
        }

        $devices = Users::getDevices($to_user->id);

        if (! $devices) {
            \Log::info('Нет устройств');
            return true;
        }

        $sent = false;

        foreach ($devices as $device) {

            if ($type == 'MATCH') {
                $text = 'У вас совпадение c ' . Helper::casusInstrumentalis($from_user->name, $from_user->sex);
            } else if ($type == 'MESSAGE') {
                $text = 'У вас сообщение от ' . Helper::genitivus($from_user->name, $from_user->sex);
            } else if ($type == 'SYSTEM_MESSAGE') {
                $text = $given_text;
            }

            \Log::info($type . ': ' . $text . ', token = ' . $device->device_token);

            if (Users::isTestUser($to_user->id)) {
                \Log::info('Предназначено тестовому пользователю, пропускаем');
                return true;
            }

            try {

                if ($device->device_type == 1) {

                    $message = new \ApnsPHP_Message($device->device_token);

                    $message->setText($text);
                    $message->setExpiry(30);
                    $badge = Users::getCountForBadge($to_user->id);
                    $message->setBadge((int)$badge);
                    $message->setSound();

                    \Log::info('badge = ' . $badge);

                    if (in_array($type, ['MATCH', 'MESSAGE'])) {
                        $message->setCustomProperty('userId', $from_user->id);
                    }

                    $message->setCustomProperty('type', strtolower($type));

                    $pusher = self::getApplePusher();

                    $pusher->add($message);

                    if (APP_ENV != 'dev') {
                        Helper::closeDBConnections();
                        $pusher->send();
                    } else {
                        \Log::info('Пропускаем (dev)');
                    }

                    \Log::info('Отправлено Apple');
                    $sent = true;

                } else if ($device->device_type == 2) {

                    $bag = [
                        'message' => $text,
                        'type' => strtolower($type),
                    ];
                    if (in_array($type, ['MATCH', 'MESSAGE'])) {
                        $bag['user'] = [
                            'id' => $from_user->id,
                            'name' => $from_user->name,
                            'avatar_url' => $from_user->avatar_url
                        ];
                    }

                    $pusher = self::getGooglePusher();
                    $devices = new DeviceCollection([new Device($device->device_token)]);
                    $message = new Message(json_encode($bag));
                    $push = new GooglePushWrapper($devices, $message);

                    if (APP_ENV != 'dev') {
                        Helper::closeDBConnections();
                        $pusher->add($push);
                        $pusher->push();
                    } else {
                        \Log::info('Пропускаем (dev)');
                    }

                    \Log::info('Отправлено Google');
                    $sent = true;

                    unset($pusher);

                }

            } catch (\Exception $e) {
                \Log::error(get_class($e) . ' ' . $e->getMessage());
                ErrorCollector::addError(
                    'PUSH_LIKES_ERROR',
                    '',
                    json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $sent;
    }
}

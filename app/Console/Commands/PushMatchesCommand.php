<?php namespace App\Console\Commands;

use \App\Models\ErrorCollector;
use \App\Models\Helper;
use \App\Models\Users;


class PushMatchesCommand extends \Illuminate\Console\Command {
    public $name = 'push_matches';

    private static $apple_pusher = null;
    private static $google_pusher = null;

    private static function getApplePusher() {
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

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        do {
            while ($job = \Queue::connection('rabbitmq')->pop('matches')) {
                $json = $job->getRawBody();
                \Log::info($json);

                $data = json_decode($json, 'assoc')['data'];

                $from_user = Users::findById($data['from_user_id']);
                $to_user = Users::findById($data['to_user_id']);

                if (self::push($from_user, $to_user)) {
                    $job->delete();
                } else {
                    $job->release(2);
                }
            }
            usleep(100);
        } while (true);

        self::getApplePusher()->disconnect();

        return 0;
    }

    private static function push($from_user, $to_user) {
        $devices = Users::getDevices($to_user->id);

        $sent = false;

        foreach ($devices as $device) {
            if ($device->device_type == 1) {
                try {

                    $text = 'У вас совпадение c ' . Helper::casusInstrumentalis($from_user->name, $from_user->sex);
                    \Log::info($text . ' -> iOS ' . $device->device_type);

                    $message = new \ApnsPHP_Message($device->device_token);
                    $message->setText($text);
                    $message->setExpiry(30);
                    $message->setBadge(5);
                    $message->setSound();
                    //$message->setCustomProperty('acme1', 'bar');
                    //$message->setCustomProperty('acme2', 42);

                    $pusher = self::getApplePusher();

                    $pusher->add($message);

                    //$pusher->send();

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
            }
        }

        return $sent;
    }
}

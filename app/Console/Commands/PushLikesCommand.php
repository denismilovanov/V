<?php namespace App\Console\Commands;

use \App\Models\ErrorCollector;
use \App\Models\Helper;

class PushLikesCommand extends \Illuminate\Console\Command
{
    public $name = 'push_likes';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $sql = "
            WITH pushes_ids AS (
                SELECT q.id
                    FROM public.push_queue AS q
                    WHERE   q.status_id = 1 AND
                            q.push_type_id = 1
                    ORDER BY q.added_at ASC
                    LIMIT 100
            )

            SELECT  to_user.id AS to_user_id,
                    from_user.name AS from_user_name,
                    from_user.sex AS from_user_sex,
                    ud.device_type,
                    ud.device_token

                FROM public.push_queue AS q
                INNER JOIN public.users AS to_user
                    ON to_user.id = q.to_user_id
                INNER JOIN public.users AS from_user
                    ON from_user.id = q.from_user_id
                INNER JOIN public.users_devices AS ud
                    ON ud.user_id = q.to_user_id

                WHERE q.id IN (SELECT id FROM pushes_ids);
        ";

        $pushes = \DB::select($sql);

        if (! $pushes) {
            return 0;
        }

        try {

            if (APP_ENV == 'dev' or APP_ENV == 'test') {
                $pusher = new \ApnsPHP_Push(
                    \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
                    env('APP_PATH') . '/config/ck_develop.pem'
                );
                $pusher->setProviderCertificatePassphrase('qwerty');
            } else if (APP_ENV == 'production') {
                $pusher = new \ApnsPHP_Push(
                    \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
                    PROTECTED_PATH . '/config/ck_production.pem'
                );
                $pusher->setProviderCertificatePassphrase('');
            }

            $pusher->connect();

        } catch (\Exception $e) {
            \Log::alert($e->getMessage());
            ErrorCollector::addError(
                'PUSH_LIKES_ERROR',
                '',
                json_encode($e->getMessage())
            );
            return 1;
        }

        foreach ($pushes as $push) {
            if ($push->device_type == 1) {
                try {

                    $text = 'У вас совпадение c ' . Helper::casusInstrumentalis($push->from_user_name, $push->from_user_sex);
                    \Log::info($text . ' -> iOS ' . $push->device_type);

                    $message = new \ApnsPHP_Message($push->device_token);
                    $message->setText($text);
                    $message->setExpiry(30);
                    $message->setBadge(5);
                    $message->setSound();
                    //$message->setCustomProperty('acme1', 'bar');
                    //$message->setCustomProperty('acme2', 42);

                    $pusher->add($message);

                    //$pusher->send();
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                    ErrorCollector::addError(
                        'PUSH_LIKES_ERROR',
                        '',
                        json_encode($e->getMessage())
                    );
                }

                $aErrorQueue = $pusher->getErrors();

            }
        }

        $pusher->disconnect();

    }
}

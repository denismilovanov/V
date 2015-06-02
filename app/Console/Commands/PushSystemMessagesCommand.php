<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Pusher;
use \App\Models\Helper;
use \App\Models\Users;
use \App\Models\ErrorCollector;


class PushSystemMessagesCommand extends \App\Console\SingleCommand
{
    public $name = 'push_system_messages';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        parent::run($input, $output);

        $tag = 'push_system_messages' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('push_system_messages', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if ($result = Pusher::push($data, 'SYSTEM_MESSAGE', $data['message'])) {
                \Log::info('Завершили задание');
                $job->delete();
            } else {
                \Log::info('Возвращаем на обработку');
                dd($data);
                $job->release(10);
            }

            self::closeDBConnections();

            if (++ $jobs == 10000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        self::closeDBConnections();

        return 0;
    }

}

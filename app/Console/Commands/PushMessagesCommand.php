<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Pusher;
use \App\Models\Helper;
use \App\Models\Users;
use \App\Models\ErrorCollector;


class PushMessagesCommand extends \App\Console\SingleCommand
{
    public $name = 'push_messages';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        parent::run($input, $output);

        $tag = 'push_messages' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('push_messages', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if ($result = Pusher::push($data, 'MESSAGE')) {
                \Log::info('Завершили задание');
                $job->delete();
            } else {
                \Log::info('Возвращаем на обработку');
                $job->release(10);
            }

            if (++ $jobs == 1000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();

        return 0;
    }

}

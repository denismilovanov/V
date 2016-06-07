<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Pusher;
use \App\Models\Helper;

class PushMessagesCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'push_messages';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->checkInstance($input);

        $tag = 'push_messages' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('push_messages', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info(json_encode(json_decode($json), JSON_UNESCAPED_UNICODE));

            $data = json_decode($json, 'assoc')['data'];

            if (isset($data['attempts']) and $data['attempts'] > 10) {
                \Log::info('Выкидываем');
                $job->delete();
            } else {
                \Log::info('Начали ' . $jobs);

                if ($result = Pusher::push($data, 'MESSAGE')) {
                    \Log::info('Завершили задание');
                    $job->delete();
                } else {
                    \Log::info('Возвращаем на обработку');
                    $job->release(10);
                }
            }

            Helper::closeDBConnections();

            if (++ $jobs == 100) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        Helper::closeDBConnections();

        return 0;
    }

}

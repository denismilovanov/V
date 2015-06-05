<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Queues;
use \App\Models\Helper;

class CreateMassPushCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'create_mass_push';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->checkInstance($input);

        $tag = 'create_mass_push' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('create_mass_push', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if ($result = Queues::createMassPush($data['message'])) {
                \Log::info('Завершили задание');
                $job->delete();
            } else {
                \Log::info('Возвращаем на обработку');
                $job->release(10);
            }

            Helper::closeDBConnections();

            if (++ $jobs == 10) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        Helper::closeDBConnections();

        return 0;
    }

}

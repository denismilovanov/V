<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Stats;

class EventsForStatsListenerCommand extends \App\Console\SingleCommand
{
    public $name = 'events_for_stats';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        parent::run($input, $output);

        $tag = 'events_for_stats' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('events_for_stats', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if (Stats::processEvent($data)) {
                \Log::info('Завершили задание');
                $job->delete();
            } else {
                $job->release(10);
            }

            self::closeDBConnections();

            if (++ $jobs == 1e6) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        self::closeDBConnections();

        return 0;
    }

}

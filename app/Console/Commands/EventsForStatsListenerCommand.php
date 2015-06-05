<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Stats;
use \App\Models\Helper;

class EventsForStatsListenerCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'events_for_stats';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->checkInstance($input);

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

            Helper::closeDBConnections();

            if (++ $jobs == 1e6) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        Helper::closeDBConnections();

        return 0;
    }

}

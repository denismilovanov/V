<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\UsersMatches;

class UpdateWeightsCommand extends \Illuminate\Console\Command
{
    public $name = 'update_weights';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $jobs = 0;
        $tag = 'update_weights_' . mt_rand();

        \Queue::subscribe('update_weights', $tag, function(RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали' . $jobs);

            if (UsersMatches::updateWeights($data['user_id'])) {
                \Log::info('Завершили');
                $job->delete();
            }

            if (++ $jobs == 1000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили ' . $tag);
    }
}

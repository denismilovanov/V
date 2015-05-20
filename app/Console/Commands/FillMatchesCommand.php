<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\UsersMatches;

class FillMatchesCommand extends \App\Console\SingleCommand
{
    public $name = 'fill_matches';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        parent::run($input, $output);

        $tag = 'fill_matches_' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('fill_matches', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали' . $jobs);

            if (\App\Models\UsersMatches::fillMatchesInUsersMatches($data['user_id'])) {
                \Log::info('Завершили');
                $job->delete();
            }

            if (++ $jobs == 100) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили ' . $tag);
    }
}

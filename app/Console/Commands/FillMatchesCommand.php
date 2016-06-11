<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\UsersMatches;
use \App\Models\Helper;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class FillMatchesCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'fill_matches';

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->checkInstance($input);

        $tag = 'fill_matches_' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('fill_matches', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if (\App\Models\UsersMatches::fillMatchesInUsersMatches($data['user_id'], $data['ts'])) {
                \Log::info('Завершили');
                $job->delete();
            }

            Helper::closeDBConnections();

            if (++ $jobs == 100) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);
        Helper::closeDBConnections();

        return 0;
    }
}

<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\UsersMatches;
use \App\Models\Helper;

class CleanIndexesFromDeletedUsersCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'remove_from_index';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        $tag = 'remove_from_index' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('remove_from_index', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            \App\Models\UsersMatches::deleteMatch($data['user_id'], $data['match_user_id'], null);
            \Log::info('Завершили');
            $job->delete();

            Helper::closeDBConnections();

            if (++ $jobs == 1e6) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);
        Helper::closeDBConnections();

        return 0;
    }
}

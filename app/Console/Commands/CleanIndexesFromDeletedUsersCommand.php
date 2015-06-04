<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\UsersMatches;

class CleanIndexesFromDeletedUsersCommand extends \App\Console\SingleCommand
{
    public $name = 'remove_from_index';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        parent::run($input, $output);

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

            self::closeDBConnections();

            if (++ $jobs == 1e6) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);
        self::closeDBConnections();

        return 0;
    }
}

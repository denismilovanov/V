<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Users;
use \App\Models\Stats;
use \App\Models\UsersIndex;
use \App\Models\UsersMatches;


class MaintenanceCommand extends \App\Console\SingleCommand
{
    public $name = 'maintenance';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        parent::run($input, $output);

        $result = Users::removeOldKeys();
        foreach ($result as $user) {
            \Log::info('Сбросили key пользователю ' . $user->user_id);
        }

        $result = UsersIndex::updateBatch();
        if ($result) {
            \Log::info('Обновили индекс пользователям ' . $result);
        }

        $result = UsersMatches::rebuildBatch();
        if ($result) {
            \Log::info('Отправили на построение заново поисковый индекс пользователям ' . $result);
        }

        Stats::createTodayStatsRecord();


    }

}

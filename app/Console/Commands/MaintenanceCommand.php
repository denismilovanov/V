<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Users;
use \App\Models\Stats;
use \App\Models\UsersIndex;
use \App\Models\Helper;

class MaintenanceCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'maintenance';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        GAUGE('fillMatches.enqueued', \Queue::getMessageCount('fill_matches'));

        $result = Users::removeOldKeys();
        foreach ($result as $user) {
            \Log::info('Сбросили key пользователю ' . $user->user_id);
        }

        $result = UsersIndex::updateBatch();
        if ($result) {
            \Log::info('Обновили индекс пользователям ' . $result);
        }

        Stats::createTodayStatsRecord();

        Helper::closeDBConnections();
    }

}

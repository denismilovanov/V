<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Users;

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

    }

}

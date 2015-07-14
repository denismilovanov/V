<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Pusher;

class PushFeedbackCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'push_feedback';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        Pusher::pushFeedbackApple();
    }

}

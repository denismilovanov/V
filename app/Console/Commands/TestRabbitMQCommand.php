<?php namespace App\Console\Commands;

use \App\Models\UsersMatches;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

class TestRabbitMQCommand extends \Illuminate\Console\Command
{
    public $name = 'test_rabbit_mq';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $type = $input->getParameterOption('type');

        if ($type == 'sub') {
            \Queue::subscribe('test', function (RabbitMQJob $job) {
                \Log::info($job->getRawBody());
                $job->delete();
            });
        } else if ($type == 'pub') {
            $i = 0;
            while (++ $i) {
                \Log::info($i);
                \Queue::push('test', ['num' => $i], 'test');
                usleep(10000);
            }
        }
    }
}

<?php namespace App\Console\Commands;

use \App\Models\ErrorCollector;
use \App\Models\Helper;

class MaintainMatchesCommand extends \Illuminate\Console\Command
{
    public $name = 'maintain_matches';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        do {
            while ($job = \Queue::connection('rabbitmq')->pop('matches')) {
                \Log::info($job->getRawBody());
                $job->delete();
            }
            sleep(10);
        } while (true);
    }
}

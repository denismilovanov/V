<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Checkins;
use \App\Models\Users;
use \App\Models\Helper;

class ImitateActivityCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'imitate_activity';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        $min_latitude = 59.7;
        $max_latitude = 60.1;
        $min_longitude = 30.1;
        $max_longitude = 30.5;

        foreach (range(1, $input->getParameterOption('num')) as $count) {

            $user_id = Users::getRandomTestUserId();

            $longitude = $min_longitude + ($max_longitude - $min_longitude) * mt_rand() / mt_getrandmax();
            $latitude = $min_latitude + ($max_latitude - $min_latitude) * mt_rand() / mt_getrandmax();

            \Log::info('user_id = ' . $user_id . ', longitude = ' . $longitude . ', latitude = ' . $latitude);

            $result = Checkins::checkin(
                $user_id,
                $longitude,
                $latitude
            );

            usleep(mt_rand() / mt_getrandmax() * 100);

        }
    }
}

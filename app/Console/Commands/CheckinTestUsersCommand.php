<?php namespace App\Console\Commands;

use \App\Models\Checkins;
use \App\Models\Users;
use \App\Models\Helper;

class CheckinTestUsersCommand extends \Illuminate\Console\Command
{
    public $name = 'checkin_test_users';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $coors = [
            '0.55' => [37.6178, 55.7517], // мск
            '0.85' => [30.3167, 59.9500], // спб
            '1.00' => [60.5833, 56.8333], // екб
        ];

        $users_max_id = Users::getMaxId();

        for ($i = 1; $i <= $users_max_id; $i ++) {
            if (! Users::isTestUser($i)) {
                continue;
            }

            $rand = mt_rand() / mt_getrandmax();

            $latitude = $longitude = 0.0000;

            foreach ($coors as $limit => $coords) {
                if ($rand <= $limit) {
                    $latitude = $coords[1];
                    $longitude = $coords[0];
                    break;
                }
            }

            $shift = 0.4;

            $latitude += - $shift / 2 + $shift * mt_rand() / mt_getrandmax();
            $longitude += - $shift / 2 + $shift * mt_rand() / mt_getrandmax();

            \Log::info($i . ', lat = ' . $latitude . ', long = ' . $longitude);

            Checkins::checkin($i, $longitude, $latitude, false);
        }
    }
}

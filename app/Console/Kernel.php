<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

use App\Console\Commands\PushLikesCommand;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        'App\Console\Commands\StopAllCommand',
        'App\Console\Commands\RemovePidsCommand',
        //
        'App\Console\Commands\PushMatchesCommand',
        'App\Console\Commands\PushMessagesCommand',
        //
        'App\Console\Commands\UpdateWeightsCommand',
        'App\Console\Commands\FillMatchesCommand',
        //
        'App\Console\Commands\EventsForStatsListenerCommand',
        //
        'App\Console\Commands\TestRabbitMQCommand',
        'App\Console\Commands\CheckinTestUsersCommand',
        //
        'App\Console\Commands\EchoMessagesCommand',
        'App\Console\Commands\EchoLikesCommand',
        //
        'App\Console\Commands\SendErrorsCommand',
        'App\Console\Commands\MaintenanceCommand',
        'App\Console\Commands\ImitateActivityCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}

declare(ticks = 1);

class SingleCommand extends \Illuminate\Console\Command
{
    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $p = intval($input->getParameterOption('p', 1));
        $command = $input->getFirstArgument();

        $pid = '/tmp/' . APP_ENV . '/' . $command . '-' . $p . '.pid';

        if (! file_exists($dir = dirname($pid))) {
            mkdir($dir, 0775, true);
        }

        if (file_exists($pid)) {
            // \Log::info('Процесс уже запущен: ' . $pid . ' - ' . file_get_contents($pid));
            exit(1);
        }

        file_put_contents($pid, posix_getpid());

        $shutdown = function() use ($pid) {
            if (file_exists($pid)) {
                \Log::info('Процесс остановлен ' . $pid);
                unlink($pid);
            }
            die;
        };

        pcntl_signal(15, $shutdown);
        register_shutdown_function($shutdown);
    }
}

<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        'App\Console\Commands\PushMatchesCommand',
        'App\Console\Commands\PushMessagesCommand',
        'App\Console\Commands\PushSystemMessagesCommand',
        'App\Console\Commands\CreateMassPushCommand',
        'App\Console\Commands\PushFeedbackCommand',
        //
        'App\Console\Commands\FillMatchesCommand',
        'App\Console\Commands\CleanIndexesFromDeletedUsersCommand',
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

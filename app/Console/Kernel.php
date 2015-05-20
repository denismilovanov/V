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
        'App\Console\Commands\PushMatchesCommand',
        'App\Console\Commands\PushMessagesCommand',
        'App\Console\Commands\UpdateWeightsCommand',
        'App\Console\Commands\FillMatchesCommand',
        'App\Console\Commands\TestRabbitMQCommand',
        'App\Console\Commands\EchoMessagesCommand',
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

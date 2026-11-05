<?php declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $this->apiCommands($schedule);
        $schedule->command('get:indicators')->weeklyOn(7, '03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Команды для апи проекта
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function apiCommands(Schedule $schedule): void
    {
        if (config('app.project') !== 'api') {
            return;
        }

        $schedule->command('get:rates')->dailyAt('21:00');
    }
}

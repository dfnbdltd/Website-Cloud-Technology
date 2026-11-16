<?php

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
    protected function schedule(Schedule $schedule)
    {
        // ResellerClub finishes a transfer-in days after we submit it and tells
        // us nothing, so ask. Needs the one server cron entry:
        //   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('transfers:sync')
            ->everyTenMinutes()
            ->withoutOverlapping(15)
            // Own log of every run (what was checked, how many completed/failed/pending).
            ->appendOutputTo(storage_path('logs/transfers-sync.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

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
        \App\Console\Commands\UpdatePrice::Class,
        \App\Console\Commands\InspectHistoryDetailTable::class,
        \App\Console\Commands\CreateHistoryUsingHistoryDetail::class,
        \App\Console\Commands\InspectHistoryTable::class,
        \App\Console\Commands\UpdateHistoryAsChangingProductStatus::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        
        $schedule->command('updateprice:info')->timezone('Asia/Tokyo')
            ->daily()->withoutOverlapping();
        $schedule->command('history:create')->timezone('Asia/Tokyo')
            ->hourly()->between('1:00', '6:00')->withoutOverlapping();
        /*$schedule->command('snapshot:create')->timezone('Asia/Tokyo')
            ->dailyAt('15:00')->withoutOverlapping();*/
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

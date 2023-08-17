<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use Illuminate\Support\Facades\Storage;

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


        /**
         * Auto-cancel booking
         */
        $schedule->call('\App\Http\Controllers\Booking\AutoCancelBooking@__invoke')
                ->everyFiveMinutes();
                // ->everyMinute();

        /**
         * Auto-cancel vouchers
         */
        $schedule->call('\App\Http\Controllers\Booking\AutoCancelVoucher@__invoke')
                ->everyFiveMinutes();
                // ->everyMinute();

        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
    
            // your schedule code
            $files = Storage::disk('public')->files("reports");
            if (count($files)) {
                Storage::disk('public')->delete($files);
                info(implode(", ",$files).' deleted.');
            }
            
        })->everyFifteenMinutes();
        // ->everyFiveMinutes();

         /**
         * 3 -hrs after boarding - all remaining 'Checked-in' passengers will be tagged as "No Show" (connected on Concierge reports)
         */
        $schedule->call('\App\Http\Controllers\Transportation\AutoNoShowGuest@__invoke')
            ->hourly();
            // ->everyThirtyMinutes();
            // ->everyMinute();

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

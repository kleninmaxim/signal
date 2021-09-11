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
        // $schedule->command('inspire')->hourly();

/*        $schedule->call('\App\Http\Controllers\TinkoffController@loadCandles')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/record_tinkoff_ticker.log'));*/

/*        $schedule->call('\App\Http\Controllers\BinanceController@loadCandles')
            ->everyFifteenMinutes()
            ->appendOutputTo(storage_path('logs/record_binance_pair.log'));*/

        $schedule->call('\App\Http\Controllers\TinkoffController@updateAllCandles')
            ->weekdays()
            ->dailyAt('4:00')
            ->appendOutputTo(storage_path('logs/tinkoff_update_all_candles.log'));

       /* $schedule->call('\App\Http\Controllers\TinkoffController@notifyHourStrategies')
            ->weekdays()
            ->hourlyAt(55)
            ->unlessBetween('00:00', '7:00')
            ->appendOutputTo(storage_path('logs/tinkoff_hour.log'));

        $schedule->call('\App\Http\Controllers\TinkoffController@notifyFourHourStrategies')
            ->weekdays()
            ->everyFourHours()
            ->unlessBetween('00:00', '7:00')
            ->appendOutputTo(storage_path('logs/tinkoff_four_hour.log'));

        $schedule->call('\App\Http\Controllers\TinkoffController@notifyDayStrategies')
            ->weekdays()
            ->dailyAt('23:30')
            ->appendOutputTo(storage_path('logs/tinkoff_day.log'));


        $schedule->call('\App\Http\Controllers\TinkoffController@notifyHourAfterStrategies')
            ->weekdays()
            ->hourlyAt(5)
            ->unlessBetween('00:00', '7:00')
            ->appendOutputTo(storage_path('logs/tinkoff_hour.log'));

        $schedule->call('\App\Http\Controllers\TinkoffController@notifyFourHourAfterStrategies')
            ->weekdays()
            ->everyFourHours()
            ->unlessBetween('00:00', '7:00')
            ->appendOutputTo(storage_path('logs/tinkoff_four_hour.log'));*/


/*        $schedule->call('\App\Http\Controllers\BinanceController@notifyHourStrategies')
            ->hourlyAt(55)
            ->appendOutputTo(storage_path('logs/binance_hour.log'));

        $schedule->call('\App\Http\Controllers\BinanceController@notifyFourHourStrategies')
            ->everyFourHours()
            ->appendOutputTo(storage_path('logs/binance_four_hour.log'));

        $schedule->call('\App\Http\Controllers\BinanceController@notifyDayStrategies')
            ->dailyAt('00:00')
            ->appendOutputTo(storage_path('logs/binance_day.log'));


        $schedule->call('\App\Http\Controllers\BinanceController@notifyHourAfterStrategies')
            ->hourlyAt(5)
            ->appendOutputTo(storage_path('logs/binance_hour.log'));

        $schedule->call('\App\Http\Controllers\BinanceController@notifyFourHourAfterStrategies')
            ->everyFourHours()
            ->appendOutputTo(storage_path('logs/binance_four_hour.log'));

        $schedule->call('\App\Http\Controllers\BinanceController@notifyDayAfterStrategies')
            ->dailyAt('03:05')
            ->appendOutputTo(storage_path('logs/binance_day.log'));*/
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
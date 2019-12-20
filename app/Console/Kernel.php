<?php

namespace App\Console;

use App\Console\Commands\AddBaodan;
use App\Console\Commands\AddInfoCollect;
use App\Console\Commands\AddWalletCollect;
use App\Console\Commands\KuangjiRelease;
use App\Console\Commands\ReleaseEnergy;
use App\Console\Commands\UpdateLevel;
use App\Console\Commands\UpdateWallet;
use App\Console\Commands\UserStat;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\OrderCancel::class,
        Commands\Test::class,
        Commands\AitcTransactionCheck::Class,
        AddInfoCollect::class,
        UpdateWallet::class,
        AddWalletCollect::class,
        AddBaodan::class,
        UpdateLevel::class,
        KuangjiRelease::class,
        ReleaseEnergy::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            Log::info('定时任务执行，时间：' . Carbon::now());
        })->everyFiveMinutes();
        $schedule->command('order_cancel')->everyMinute();
        $schedule->command('create_symbol_history')->everyMinute();
        $schedule->command('create_week_line')->sundays();
        $schedule->command('addInfoCollect')->dailyAt('00:30');
        $schedule->command('updateWallet')->dailyAt('03:30');
        $schedule->command('addWalletCollect')->hourlyAt(5);

        $schedule->command('addBaodan')->dailyAt('05:22');
        $schedule->command('updateLevel')->dailyAt('00:25');
        $schedule->command('kuangjiRelease')->dailyAt('00:01');
        $schedule->command('releaseEnergy')->dailyAt('04:11');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

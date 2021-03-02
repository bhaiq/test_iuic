<?php

namespace App\Console;

use App\Console\Commands\AddBaodan;
use App\Console\Commands\AddInfoCollect;
use App\Console\Commands\AddWalletCollect;
use App\Console\Commands\EcologyCreaditDay;
use App\Console\Commands\EcologyPartner;
use App\Console\Commands\JlKjRelease;
use App\Console\Commands\KuangjiRelease;
use App\Console\Commands\MinusDay;
use App\Console\Commands\NewKuangchiRelease;
use App\Console\Commands\ReleaseEnergy;
use App\Console\Commands\RobotTrade;
use App\Console\Commands\SumServiceCharge;
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
        NewKuangchiRelease::class,
        RobotTrade::class,
        JlKjRelease::class,
        MinusDay::class,
        SumServiceCharge::class,
        EcologyCreaditDay::class,
        EcologyPartner::class,
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
//        $schedule->command('releaseEnergy')->dailyAt('04:11'); //能量释放 周一早上打开
        $schedule->command('newKuangchiRelease')->dailyAt('05:11');
        $schedule->command('SumServiceCharge')->dailyAt('08:10'); //计算手续费给指定用户加上iuic(法币)
//        $schedule->command('newKuangchiRelease')->dailyAt('16:10');

        $schedule->command('robotTrade')->everyFiveMinutes();
      
        $schedule->command('todayReleaseClear')->dailyAt('05:11');
      
      	$schedule->command('CommDivid')->monthlyOn(1,'01:01');
//      	$schedule->command('CommDividMonth')->monthlyOn(5,'02:35'); //月度分红 关闭2021/1/8
//      	$schedule->command('CommDividMonth')->monthlyOn(6,'10:29');
//      	$schedule->command('CommDividMonth')->dailyAt('15:08');

      	$schedule->command('Mytestds')->dailyAt('11:33');
      	$schedule->command('jlkjrelease')->dailyAt('01:35');
//      	$schedule->command('jlkjrelease')->everyMinute();//f
      	$schedule->command('minus_day')->dailyAt('02:00'); //每天处理到期的矿机订单
//      	$schedule->command('minus_day')->everyMinute(); //每天处理到期的矿机订单
//      	$schedule->command('jlkjrelease')->everyMinute();

      	
      	//=====================
    //      $schedule->command('addInfoCollect')->dailyAt('13:03');
    //     $schedule->command('updateWallet')->dailyAt('13:08');
    //     $schedule->command('addBaodan')->dailyAt('13:13');
    //     $schedule->command('updateLevel')->dailyAt('13:18');
    //     $schedule->command('kuangjiRelease')->dailyAt('13:23');
    //     $schedule->command('releaseEnergy')->dailyAt('13:28');
    //     $schedule->command('newKuangchiRelease')->dailyAt('13:31');
    //     $schedule->command('todayReleaseClear')->dailyAt('13:36');

        $schedule->command('ecologycreaditday')->dailyAt('02:00'); //凌晨生成前一天报单总数据信息
        $schedule->command('ecology_yj_bonus')->dailyAt('02:35'); //生态2团队长奖
        $schedule->command('ecologypartner')->dailyAt('03:30'); //生态2合伙人奖
        $schedule->command('ecology_partner_service')->dailyAt('02:40'); //生态2手续费合伙人奖
        $schedule->command('ecology_service')->dailyAt('02:50'); //生态2手续费奖
        $schedule->command('out')->hourly(); //每小时跑一次 当前账号是否出局
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

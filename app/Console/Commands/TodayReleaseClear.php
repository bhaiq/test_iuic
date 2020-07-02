<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
// use App\Models\DayLog;
class TodayReleaseClear extends Command {

    protected $name = 'todayReleaseClear';//命令名称

    protected $description = '清楚每天的释放总量'; // 命令描述，没什么用

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {	
		\Log::info('清楚每日释放总数开始');
		UserInfo::where('today_release','>','0')->update(['today_release'=>0]);
		
		
		\Log::info('清楚每日释放总数结束');
    }
}
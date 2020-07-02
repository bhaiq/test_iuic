<?php

namespace App\Console\Commands;

use App\Models\ExOrderTogetger;
use App\Models\InfoCollect;
use App\Models\ReleaseOrder;
use App\Models\User;
use App\Models\UserInfo;
use Illuminate\Console\Command;

class AddInfoCollect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addInfoCollect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '记录汇总信息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        \Log::info('===== 开始记录汇总信息 =====');

        $this->addInfo();

        \Log::info('===== 记录汇总信息结束 =====');

    }

    private function addInfo(){

        // 判断昨天是否已经记录
        if(InfoCollect::whereDate('cur_date', now()->subDay()->toDateString())->exists()){
            \Log::info('昨天数据已记录，不再进行记录');
            return false;
        }

        $icData = [
            'cur_date' => now()->subDay()->toDateString(),
            'zc_user_count' => User::whereDate('created_at', now()->subDay()->toDateString())->count(),
            'gj_user_count' => UserInfo::where('level', 2)->whereDate('created_at', now()->subDay()->toDateString())->count(),
            'pt_user_count' => UserInfo::where('level', 1)->whereDate('created_at', now()->subDay()->toDateString())->count(),
            'today_release' => ReleaseOrder::whereDate('release_time', now()->subDay()->toDateString())->sum('today_num'),
            'today_trade' => ExOrderTogetger::whereDate('created_at', now()->subDay()->toDateString())->sum('success_amount'),
        ];

        InfoCollect::create($icData);

        return true;

    }
}

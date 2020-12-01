<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\KuangchiServiceCharge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SumServiceCharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SumServiceCharge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算手续费';

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
        //计算手续费总数,给指定用户加上iuic(法币)
        Log::info("计算手续费总数");
        $all_num = KuangchiServiceCharge::where('id','>',0)->whereBetween('created_at',[date("Y-m-d 00:00:00",time()),date("Y-m-d 23:59:59",time())])->sum('all_num');

        // 用户余额增加(917客户指定的账号id)
        Account::addAmount("917", 2, $all_num);
        $exp = " IUIC矿池手续费";
        // 用户余额日志增加
        AccountLog::addLog("917", 2, $all_num, 25, 1, Account::TYPE_LC, $exp);

    }
}

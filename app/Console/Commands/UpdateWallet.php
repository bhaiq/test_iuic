<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class UpdateWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateWallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时更新余额';

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

        \Log::info('==========  定时处理失精度余额  ==========');

        $this->toHandleWallet();

        \Log::info('==========  处理失精度余额完成  ==========');

    }

    private function toHandleWallet()
    {

        // 获取所有冻结余额小于0的账号
        $a = Account::where('amount_freeze', '<', 0)->where('amount_freeze', '>', -1)->get();

        if($a->isEmpty()){
            \Log::info('没有获取冻结为负数的数据');
            return false;
        }

        foreach ($a as $v){

            // 先把负数转出正数
            $amountFreeze = bcmul($v->amount_freeze, -1, 8);

            // 先判断可用余额是否大于冻结余额
            if($v->amount > $amountFreeze){

                // 用户余额减少
                Account::reduceAmount($v->uid, $v->coin_id, $amountFreeze, $v->type);

                // 用户冻结余额增加
                Account::addFrozen($v->uid, $v->coin_id, $amountFreeze, $v->type);

            }

        }

        return true;
    }

}

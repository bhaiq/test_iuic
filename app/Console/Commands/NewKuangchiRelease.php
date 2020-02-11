<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\NewKuangchiReleaseLog;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use Illuminate\Console\Command;

class NewKuangchiRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newKuangchiRelease';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新的矿池释放方式';

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

        $this->toRelease();

    }

    public function toRelease()
    {

        // 获取用户的矿池信息
        $ui = UserInfo::where('buy_total', '>', 0)->get();
        foreach ($ui as $v){

            // 先判断释放释放完全
            if($v->release_total >= $v->buy_total){
                continue;
            }

            // 获取本次释放的比例
            $releaseBl = config('new_kuangchi.new_kuangchi_static_release_bl', 0.0001);

            // 获取本次释放数量
            $oneNum = bcmul($v->buy_total, $releaseBl, 8);

            // 判读本次释放是否超出
            if(bcadd($v->release_total, $oneNum, 8) > $v->buy_total){
                $oneNum = bcsub($v->buy_total, $v->release_total, 8);
            }

            // 判断增加的数量小不小于0
            if($oneNum <= 0){
                continue;
            }

            $data = [
                'uid' => $v->uid,
                'num' => $oneNum,
                'created_at' => now()->toDateTimeString(),
            ];

            $nkrl = NewKuangchiReleaseLog::create($data);

            // 释放矿池数增加
            UserInfo::where('uid', $v->uid)->increment('release_total', $oneNum);

            // 获取那个USDT的币种ID
            $coin = Coin::getCoinByName('IUIC');

            // 用户余额增加
            Account::addAmount($v->uid, $coin->id, $oneNum);

            // 用户余额日志增加
            AccountLog::addLog($v->uid, $coin->id, $oneNum, 25, 1, Account::TYPE_LC, '矿池静态释放');

            // 矿池表信息增加
            UserWalletLog::addLog($v->uid, 'new_kuangchi_release_log', $nkrl->id, '矿池静态释放', '-', $oneNum, 2, 1);

        }

    }

}

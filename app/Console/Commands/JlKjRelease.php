<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\Kuangji;
use App\Models\KuangjiUserPosition;
use App\Models\UserInfo;
use App\Models\UserPartner;
use App\Models\UserWalletLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class JlKjRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jlkjrelease';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新的释放'; //每日执行,将已购买矿机算力加到user_info->release_total

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
        //获取所有用户开通矿位信息
        Log::info("矿机算力开始释放");
        $kuangji_list = KuangjiUserPosition::where('order_id','>',0)->where('kuangji_id','>',0)->get();
        foreach ($kuangji_list as $k => $kuangji){
            //当前矿位矿机算力
            $kuangjis =  Kuangji::where('id',$kuangji->kuangji_id)->first();
            if(empty($kuangjis)){
                Log::info('该矿机已关闭',['kuangji_id'=>$kuangji->kuangji_id,'uid'=>$kuangji->uid,'order_id'=>$kuangji->order_id]);
                continue;
            }
            $suanli = Kuangji::where('id',$kuangji->kuangji_id)->value('suanli');
            $kj_service_charge_rate = config("kuangji.kuangji_release_service_rate"); //矿机手续费比例
            $kj_service_charge = bcmul($suanli,$kj_service_charge_rate,8); //矿机手续费
            $true_suanli = bcsub($suanli,$kj_service_charge,8);
            Log::info("算力",['suanli'=>$true_suanli,'kj_service_charge'=>$kj_service_charge,'kj_service_charge_rate'=>$kj_service_charge_rate]);
            //user_info中增加release_total
            // 释放矿池数增加,不能大于user_info->buy_total
            $user_info = UserInfo::where('uid',$kuangji->uid)->first();
            if( $user_info->release_total >= $user_info->buy_total){
                Log::info("释放数达到最大,停止释放",['uid'=>$kuangji->uid,'order_id'=>$kuangji->order_id,'kuangji_id'=>$kuangji->kuangji_id]);
                continue;
            }
            if(bcadd($suanli,$user_info->release_total,8) >= $user_info->buy_total){
                //实际加的数量
                $true_num = bcsub($user_info->buy_total,$user_info->release_total,8);
            }else{
                $true_num = $suanli;
            }
            // 用户余额增加
            Account::addAmount($kuangji->uid, 2, $true_num);

            // 用户余额日志增加
            AccountLog::addLog($kuangji->uid, 2, $true_num, 20, 1, Account::TYPE_LC,$kuangjis->name.'机释放');

            //矿机释放手续费
            Account::reduceAmount($kuangji->uid,2,$kj_service_charge);
            //日志
            AccountLog::addLog($kuangji->uid, 2, $kj_service_charge, 20, 0, Account::TYPE_LC,$kuangjis->name.'机释放手续费');

            UserInfo::where('uid', $kuangji->uid)->increment('release_total', $true_num);
            UserInfo::where('uid', $kuangji->uid)->decrement('release_total', $kj_service_charge);
            // 矿池表信息增加
            UserWalletLog::addLog($kuangji->uid, 'kuangji_user_position', $kuangji->order_id, $kuangjis->name.'机释放', '-', $true_num, 2, 1);
            UserWalletLog::addLog($kuangji->uid, 'kuangji_user_position', $kuangji->order_id, $kuangjis->name.'机释放手续费', '-', $kj_service_charge, 2, 1);
//            $this->partnerBonus();
        }


        Log::info("矿机算力结束释放");

    }
    // 合伙人收益分红
    private function partnerBonus($num)
    {

        // 获取需要分红的用户
        $up = UserPartner::where('status', 1)->get();
        if($up->isEmpty()){
            \Log::info('没有合伙人需要分红');
            return false;
        }

        // 获取需要分红的用户数量
//        $userCount = config('user_partner.count', 50);
        $userCount = UserPartner::where('status', 1)->sum('count');

        // 获取本次分红的比例
        $tip = config('user_partner.tip_partner', 0.1);

        // 获取本次分红的总数
        $totalNum = bcmul($tip, $num, 8);

        // 获取本次分红一份的分红数量
        $oneNum = bcdiv($totalNum, $userCount, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次分红奖分红');
            return false;
        }

        \Log::info('合伙人分红的数据', ['num' => $num, 'count' => $userCount, 'tip' => $tip, 'one' => $oneNum]);

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');

        foreach ($up as $v){

            $newNum = bcmul($oneNum, $v->count, 8);

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $newNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, $coin->id, $newNum, 18, 1, Account::TYPE_LC, '合伙人分红');

        }

        return true;

    }
}

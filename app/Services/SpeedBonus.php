<?php

namespace App\Services;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\SpeedBonus as SpBs;
use App\Models\PerformanceBonus;
use App\Models\UserInfo;
use Illuminate\Support\Facades\Log;


class SpeedBonus
{
    //团队长加速分红奖
    //购买矿机花费金额(每个人) * 2%(后台可配) / 500(多少人后台配置) * 每人份(新建表,2,8分)
    public function speed_bonus($price)
    {
        $speed_rate = config('senior_admin.speed_rate'); //2%
        $people_num = SpBs::where('num','>','0')->sum('num'); //分红份数
        if($people_num > 0){
            $a_part = $price * $speed_rate / $people_num; //一份奖励
            if($a_part > 0){
                $people = SpBs::where('num','>','0')->get();
                foreach ($people as $k => $v){
                    $get_num = $v->num * $a_part; //单份*每人份
                    $ui = UserInfo::where('uid', $v->id)->first();
                    if(empty($ui)){
                        Log::info("该用户没有矿池");
                        return;
                    }
                    if($get_num >= bcsub($ui->buy_total, $ui->release_total, 4)){
                        $true_num = bcsub($ui->buy_total, $ui->release_total, 4);
                    }else{
                        $true_num = $get_num;
                    }

                    // 用户余额增加
                    Account::addAmount($v->id, 2, $true_num*0.8);

                    // 用户余额日志增加
                    AccountLog::addLog($v->id, 2, $true_num*0.8, 31, 1, Account::TYPE_LC,'团队长加速分红奖');
                    Log::info("用户加余额",['uid'=>$v->id,'get_num'=>$true_num*0.8]);
                    //给公司号把手续费加上
                    Account::addAmount('917', 2, $true_num*0.2);
                    AccountLog::addLog('917', 2, $true_num*0.2, 31, 1, Account::TYPE_LC,'团队长加速分红奖手续费');
                    Log::info("公司号把手续费加上",['uid'=>$v->id,'get_num'=>$true_num*0.8]);
                }
            }
        }
    }


    //团队长业绩分红奖
    //报单u(必买明星单品) x2%(后台可配) x 500(多少人后台配置) x 每人份(新建表) 奖励进入法币u
    public function performance_bonus($price)
    {
        $performance_rate = config('senior_admin.performance_rate'); //2%
        $people_num = PerformanceBonus::where('num','>','0')->sum('num'); //分红份数
        if($people_num > 0){
            $a_part = $price * $performance_rate / $people_num; //一份奖励
            if($a_part > 0){
                $people = PerformanceBonus::where('num','>',0)->get();
                foreach ($people as $k => $v)
                {
                    $get_num = $v->num * $a_part; //单份*每人份
                    // 用户余额增加
                    Account::addAmount($v->id, 1, $get_num);

                    // 用户余额日志增加
                    AccountLog::addLog($v->id, 1, $get_num, 32, 1, Account::TYPE_LC,'团队长业绩分红奖');
                    Log::info("用户加余额",['uid'=>$v->id,'get_num'=>$get_num]);
                }
            }
        }
    }

}
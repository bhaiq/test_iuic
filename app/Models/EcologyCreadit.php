<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcologyCreadit extends Model
{
    //
    protected $table = 'ecology_creadits';

    public  function created_wallet($uid){
        $wallet = New EcologyCreadit();
        $wallet->uid = $uid;
        $wallet->amount = 0;
        $wallet->amount_freeze = 0;
        $wallet->save();
    }

    //增加或扣除积分
    /*
     * uid    用户id
     * amount 操作数量
     * type   1加2减
     * scence 场景
     * remark 备注
     * coin_type 1可用2冻结
     */
    public static function a_o_m($uid,$amount,$type,$scence,$remark,$coin_type)
    {
        if($coin_type == 1){
            if($type == 1){
                EcologyCreadit::where('uid',$uid)->increment('amount',$amount);
            }else if($type == 2){
                EcologyCreadit::where('uid',$uid)->decrement('amount',$amount);
            }
        }else if($coin_type == 2){
            if($type == 1){
                EcologyCreadit::where('uid',$uid)->increment('amount_freeze',$amount);
            }else if($type == 2){
                EcologyCreadit::where('uid',$uid)->decrement('amount_freeze',$amount);
            }
        }
        $log = New EcologyCreaditLog();
        $log->addlog($uid,$amount,$type,$scence,$remark,$coin_type);
    }

    public function getTotalAttribute()
    {
        return bcadd($this->amount, $this->amount_freeze, 8);
    }

    public function getCreaditCnyAttribute()
    {
        return bcmul($this->amount, $this->getCreaditCny(), 8);
    }

    public function getCreaditFreezeCnyAttribute()
    {
        return bcmul($this->amount_freeze, $this->getCreaditCny(), 8);
    }

    public function getTotalCnyAttribute()
    {
        return bcmul($this->total,$this->getCreaditCny(),8);
    }

    //获取积分对人民币的比例(1:1)
    public function getCreaditCny()
    {
        return 1;
    }

    //获取用户生态等级
    public function get_ecology_lv($id)
    {
        return EcologyConfig::where('id',$id)->value('name');
    }

    //获取团队总人数
    public function team_all($uid)
    {
        return User::where('pid_path', 'like', '%,' . $uid . ',%')->count();
    }

    //今日新增人数
    public function new_people($uid)
    {
        return User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    //日业绩(报单积分) 个人
    public function day_yj($uid)
    {
        // 获取用户部门的所有用户ID
        return EcologyCreaditOrder::where('uid', $uid)
            ->whereDate('created_at', now()->toDateString())
            ->sum('creadit_amount');
    }

    //总业绩
    public function  zong_yj($uid)
    {
        // 获取用户部门的所有用户ID
        $lowerIds = User::where('pid_path', 'like', '%,' . $uid . ',%')->pluck('id')->toArray();
        return EcologyCreaditOrder::whereIn('uid', $lowerIds)
            ->whereDate('created_at', now()->toDateString())
            ->sum('creadit_amount');
    }

    //月业绩(个人)
    public function month($uid)
    {
        $time = time();
        $start=date('Y-m-01',strtotime($time));//获取指定月份的第一天
        $end=date('Y-m-t',strtotime($time)); //获取指定月份的最后一天
        return EcologyCreaditOrder::where('uid', $uid)
            ->whereBetween('created_at',[strtotime($start),strtotime($end)])
            ->sum('creadit_amount');
    }

    //一级生态人数
    public function first_ecology($uid)
    {
        return  User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv',3)
            ->count();
    }

    //二级生态人数
    public function two_ecology($uid)
    {
        return  User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv',4)
            ->count();
    }

    //三级生态人数
    public function three_ecology($uid)
    {
        return  User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv',5)
            ->count();
    }

    //四级生态人数
    public function four_ecology($uid)
    {
        return  User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv',6)
            ->count();
    }
    //五级生态人数
    public function five_ecology($uid)
    {
        return  User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv',7)
            ->count();
    }

    //生态2分享奖(例:报单1万 上级得分享奖  从他的冻结积分释放到可用(1万*15%)),别忘记订单
    /**
     * @param $uid 报单用户
     * @param $num 报单数量
     */
    public function ecology_share_reward($uid,$num)
    {
        $pid = User::where('id',$uid)->value('pid');
        //判断上级是否有钱包
        $p_wallet = EcologyCreadit::where('uid',$pid)->first();
        if(empty($p_wallet)){
            Log::info("上级没有购买过积分,冻结积分为0,不释放");
            return;
        }
        //应释放数
        $reward = $num * EcologyConfigPub::where('id',1)->value('rate_direct');
        DB::beginTransaction();
        try{
            if($p_wallet->amount_freeze > $reward){
                $true_num = $reward;
                EcologyCreadit::a_o_m($uid,$true_num,'2','3','生态2分享奖',2);
                EcologyCreadit::a_o_m($uid,$true_num,'1','3','生态2分享奖',1);
            }else{
                if($p_wallet->amount_freeze > 0){
                    $true_num = $p_wallet->amount_freeze;
                    EcologyCreadit::a_o_m($uid,$true_num,'2','3','生态2分享奖',2);
                    EcologyCreadit::a_o_m($uid,$true_num,'1','3','生态2分享奖',1);
                }

            }
            //从最早的订单开始释放
            $lists = EcologyCreaditOrder::where('uid',$pid)
                ->whereNull('end_time')
                ->orderby('id')
                ->get();
            $all = 0;
            foreach ($lists as $k => $v){
                $all+=$v->creadit_amount - $v->already_amount;
                //大于当前说明当前可以释放完,继续释放下一单
                if($true_num >= $all){
                    //订单状态更改,改为已释放完成,插入完成时间
                    EcologyCreaditOrder::where('id',$v->id)->update(['already_amount'=>$v->creadit_amount,
                        'end_time'=>date('Y-m-d H:i:s')]);
                }else{
                    //小于当前此单释放不完,改为释放部分,不插入完成时间,循环终止
                    $now_amount = $v->creadit_amount - $v->already_amount;
                    $sheng = $all - $now_amount;
                    $sheng = $true_num - $sheng;
                    EcologyCreaditOrder::where('id',$v->id)->increment('already_amount',$sheng);
                    break;
                }
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            Log::info("错误".$exception->getMessage());
            return;
        }
    }
}

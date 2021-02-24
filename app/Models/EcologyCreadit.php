<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     */
    public static function a_o_m($uid,$amount,$type,$scence,$remark)
    {
        if($type == 1){
            EcologyCreadit::where('uid',$uid)->increment('amount',$amount);
        }else if($type == 2){
            EcologyCreadit::where('uid',$uid)->decrement('amount',$amount);
        }
        $log = New EcologyCreaditLog();
        $log->addlog($uid,$amount,$type,$scence,$remark);
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
}

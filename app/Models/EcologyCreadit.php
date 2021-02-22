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
}

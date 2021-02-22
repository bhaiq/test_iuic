<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcologyCreaditLog extends Model
{
    //
    protected $table = 'ecology_creadits_log';

    public function getScenceAttributes($value)
    {
        switch ($value){
            case 1:
                return "购买积分"; //兑换积分
        }
    }

    //增加或扣除积分
    /*
     * uid    用户id
     * amount 操作数量
     * type   1加2减
     * scence 场景
     * remark 备注
     */
    public function addlog($uid,$amount,$type,$scence,$remark){
        $data['uid'] = $uid;
        $data['amount'] = $amount;
        $data['scence'] = $scence;
        $data['type'] = $type;
        $data['remark'] = $remark;
        $log = New EcologyCreaditLog();
        $log->save($data);
    }
}

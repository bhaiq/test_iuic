<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcologyCreaditLog extends Model
{
    //
    protected $table = 'ecology_creadits_log';

    public function getScenceAttribute($value)
    {
        switch ($value){
            case 1:
                return "购买积分"; //兑换积分
                break;
            case 2:
                return "积分划转"; //积分划转法币usdt
                break;
            case 3:
                return "生态2分享奖";
                break;
        }
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
    public function addlog($uid,$amount,$type,$scence,$remark,$coin_type){
        $log = New EcologyCreaditLog();
        $log->uid = $uid;
        $log->amount = $amount;
        $log->scence = $scence;
        $log->type = $type;
        $log->remark = $remark;
        $log->coin_type = $coin_type;

        $log->save();
    }
}

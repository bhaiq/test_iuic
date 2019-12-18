<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/17
 * Time: 17:36
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyOrder extends Model
{

    protected $table = 'energy_order';

    protected $guarded = [];

    // 获取用户购买的数量
    public static function geyBuyNum($uid, $goodsId)
    {
        return EnergyOrder::where(['uid' => $uid, 'goods_id' => $goodsId])->count();
    }

}
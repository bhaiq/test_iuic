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

    // 获取用户下级推荐的有效数量
    public static function getEnergyValidNum($uid)
    {

        // 获取用户下级数量
        $ids = User::where('pid', $uid)->pluck('id')->toArray();

        // 获取用户报单用户的数量
        $newIds = EnergyOrder::whereIn('uid', $ids)->pluck('uid')->toArray();

        return count(array_unique($newIds));

    }

}
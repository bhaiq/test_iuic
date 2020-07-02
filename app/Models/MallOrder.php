<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 14:59
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MallOrder extends Model
{

    protected $table = 'mall_order';

    protected $guarded = [];

    public function goods()
    {
        return $this->hasOne(MallGood::class, 'id', 'goods_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'uid');
    }

    // 生成一个订单编号
    public static function getOrderSn()
    {
        $orderSn = time() . rand(10000, 99999);
        if(MallOrder::where('order_sn', $orderSn)->exists()){
            return MallOrder::getOrderSn();
        }

        return $orderSn;
    }

    public function getGoodsImgAttribute($value)
    {
        return explode(',', $value);
    }

}
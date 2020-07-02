<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 10:40
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MallGood extends Model
{

    protected $table = 'mall_goods';

    protected $guarded = [];

    public function getGoodsImgAttribute($value)
    {
        return explode(',', $value);
    }

    // 计算商品返利的IUIC
    public static function getRebate($num)
    {

        // 获取USDT汇率
        $res = ExTeam::getCurPrice(1);

        $newNum = bcmul(bcdiv(bcmul($res['rate'], $num, 8), $res['price_cny'], 8), 0.5, 4);

        return $newNum > 0 ? $newNum : 0;
    }

    public function store()
    {
        return $this->hasOne(MallStore::class, 'id', 'store_id');
    }

}

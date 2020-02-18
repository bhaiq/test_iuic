<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/11/18
 * Time: 10:23
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\Redis;

class KuangjiLinghuo extends Model
{

    protected $table = 'kuangji_linghuo';

    protected $guarded = [];

    public static function getLinghuoRedeemLock($id)
    {
        $redis_key = 'KuangjiLinghuoRedeem' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '操作频繁！');
        Redis::setex($redis_key, 10, 1);
    }

}
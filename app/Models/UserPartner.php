<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/19
 * Time: 10:48
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\Redis;

class UserPartner extends Model
{

    protected $table = 'user_partner';

    protected $guarded = [];

    public static function getSubmitLock($id)
    {
        $redis_key = 'UserPartner' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '操作频繁！');
        Redis::setex($redis_key, 30, 1);
    }

}
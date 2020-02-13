<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/12
 * Time: 14:52
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryLog extends Model
{

    protected $table = 'lottery_log';

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'uid');
    }

    public function goods()
    {
        return $this->hasOne(LotteryGoods::class, 'id', 'goods_id');
    }

}
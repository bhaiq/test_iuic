<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/10/16
 * Time: 11:52
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuangjiUserPosition extends Model
{

    protected $table = 'kuangji_user_position';

    protected $guarded = [];

    public function order()
    {
        return $this->hasOne(KuangjiOrder::class, 'id', 'order_id');
    }

    public function kuangji()
    {
        return $this->hasOne(Kuangji::class, 'id', 'kuangji_id');
    }

}
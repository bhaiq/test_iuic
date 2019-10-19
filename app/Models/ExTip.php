<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExTip extends Model
{

    protected $table = 'ex_tip';

    protected $guarded = [];

    public static function addTip($num, $bonusNum, $orderId = 0)
    {

        $data = [
            'num' => $num,
            'bonus_num' => $bonusNum,
            'order_id' => $orderId,
        ];

        ExTip::create($data);

        \Log::info('新增一条手续费信息', $data);

        return true;

    }

}

<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/18
 * Time: 16:35
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyLog extends Model
{

    protected $table = 'energy_logs';

    protected $guarded = [];

    // 新增一条记录
    public static function addLog($uid, $walletType, $dyTable, $dyId, $exp, $sign, $num, $type, $dyUid = 0)
    {

        $data = [
            'uid' => $uid,
            'wallet_type' => $walletType,
            'dy_table' => $dyTable,
            'dy_id' => $dyId,
            'dy_uid' => $dyUid,
            'exp' => $exp,
            'sign' => $sign,
            'num' => $num,
            'type' => $type,
            'created_at' => now()->toDateTimeString(),
        ];

        EnergyLog::create($data);

        \Log::info('新增一条能量流水记录', $data);

        return true;
    }

}
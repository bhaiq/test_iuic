<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 16:52
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWalletLog extends Model
{

    protected $table = 'user_wallet_logs';

    protected $guarded = [];

    // 新增一条记录
    public static function addLog($uid, $dyTable, $dyId, $exp, $sign, $pay, $walletType, $logType)
    {

        $data = [
            'uid' => $uid,
            'dy_table' => $dyTable,
            'dy_id' => $dyId,
            'exp' => $exp,
            'sign' => $sign,
            'num' => $pay,
            'wallet_type' => $walletType,
            'log_type' => $logType,
            'created_at' => now()->toDateTimeString(),
        ];

        UserWalletLog::create($data);

        \Log::info('新增一条余额流水记录', $data);

        return true;
    }

}
<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\ExTip;
use App\Models\ExtractTip;
use App\Models\Slog;
use App\Models\STip;
use App\Models\Ulog;
use App\Models\UsdtLog;
use App\Models\UsLog;
use App\Models\UsSystem;
use App\Models\UsTotal;

class AccountService
{
    /**
     * @param int $uid
     * @param int $coin_id
     * @param float $amount
     * @param int $scene
     * @param array $extend
     */
    public function createLog(int $uid, int $coin_id, float $amount, int $scene, array $extend = [])
    {
        $data = compact('uid', 'coin_id', 'amount', 'scene', 'extend');
        $data['type'] = AccountLog::getType($scene);
        $data['remark'] = AccountLog::getRemark($scene);
        $data['coin_type'] = in_array($scene, [4, 9, 10, 11, 12, 13, 14, 15]) ? 1 : 0;
        AccountLog::create($data);
    }
}

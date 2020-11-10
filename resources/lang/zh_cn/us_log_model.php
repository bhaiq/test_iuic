<?php

use App\Models\UsLog;

return [
    'scene_remark' => [
        UsLog::SCENE_SPLIT          => '拆存',
        UsLog::SCENE_COMPOUND       => '合成',
        UsLog::SCENE_SHIFT_TO       => '转入',
        UsLog::SCENE_ROLL_OUT       => '转出',
        UsLog::SCENE_OTC_SELL       => '出售',
        UsLog::SCENE_OTC_BUY        => '购买',
        UsLog::SCENE_FROM_USDT      => 'USDT转US',
        UsLog::SCENE_TO_USDT        => 'US转USDT',
        UsLog::SCENE_BUY_S          => '购买S',
        UsLog::SCENE_SELL_S         => '出售S',
        UsLog::SCENE_EX_EXPENDITURE => '交易所花费',
        UsLog::SCENE_EX_INCOME      => '交易所收入',
        UsLog::SCENE_EX_DEL         => '交易取消返还',
        UsLog::SCENE_EX_BACK        => '交易完成返还',
    ]
];

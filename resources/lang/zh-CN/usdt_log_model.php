<?php

use \App\Models\UsdtLog;

return [
    'scene_remark' => [
        UsdtLog::SCENE_SHIFT_TO       => '充值',
        UsdtLog::SCENE_ROLL_OUT       => '提现',
        UsdtLog::SCENE_TO_OTHER       => '转给他人',
        UsdtLog::SCENE_FROM_OTHER     => '他人转入',
        UsdtLog::SCENE_FROM_US        => 'US转化',
        UsdtLog::SCENE_TO_US          => '转为US',
        UsdtLog::SCENE_EXTRACT_BACK   => '提现退回',
        UsdtLog::SCENE_EX_EXPENDITURE => '交易所花费',
        UsdtLog::SCENE_EX_INCOME      => '交易所收入',
        UsdtLog::SCENE_EX_DEL         => '交易返还',
    ]
];

<?php

use \App\Models\UsdtLog;

return [
    'scene_remark' => [
        UsdtLog::SCENE_SHIFT_TO       => 'Transfer in',
        UsdtLog::SCENE_ROLL_OUT       => 'Withdraw',
        UsdtLog::SCENE_TO_OTHER       => 'Transfer to others',
        UsdtLog::SCENE_FROM_OTHER     => 'Transfer in by others',
        UsdtLog::SCENE_FROM_US        => 'US conversion',
        UsdtLog::SCENE_TO_US          => 'Change to US',
        UsdtLog::SCENE_EXTRACT_BACK   => 'Cash back',
        UsdtLog::SCENE_EX_EXPENDITURE => 'Exchange expense',
        UsdtLog::SCENE_EX_INCOME      => 'Exchange revenue',
        UsdtLog::SCENE_EX_DEL         => 'Trading return',
    ]
];

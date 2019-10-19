<?php

use App\Models\UsLog;

return [
    'scene_remark' => [
        UsLog::SCENE_SPLIT     => 'Unpack',
        UsLog::SCENE_COMPOUND  => 'Combine ',
        UsLog::SCENE_SHIFT_TO  => 'Transfer in ',
        UsLog::SCENE_ROLL_OUT  => 'Withdraw',
        UsLog::SCENE_OTC_SELL  => 'Sell',
        UsLog::SCENE_OTC_BUY   => 'Buy',
        UsLog::SCENE_FROM_USDT => 'USDT to US',
        UsLog::SCENE_TO_USDT   => 'US to USDT',
        UsLog::SCENE_BUY_S     => 'Buy S',
        UsLog::SCENE_SELL_S    => 'Sell S',
        UsLog::SCENE_EX_EXPENDITURE => 'Exchange expense',
        UsLog::SCENE_EX_INCOME      => 'Exchange revenue',
        UsLog::SCENE_EX_DEL         => 'Cancellation return',
        UsLog::SCENE_EX_BACK        => 'Return on completion',
    ]
];

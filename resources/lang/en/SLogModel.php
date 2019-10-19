<?php

use \App\Models\Slog;

return [
    'scene_remark' => [
        Slog::SCENE_SPLIT    => 'Unpack',
        Slog::SCENE_COMPOUND => 'Combine',
        Slog::SCENE_FREE     => 'Release',
        Slog::SCENE_SELL     => 'Sell',
        Slog::SCENE_BUY      => 'Buy',
        Slog::SCENE_SUPER    => '超级用户返S',
    ],
    'remark'       => [
        0 => ':uid split :total US ,reward： :rate%',
        1 => ':uid split :total ,Lock S has been rewarded。'
    ]
];

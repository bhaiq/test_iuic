<?php

use \App\Models\Slog;

return [
    'scene_remark' => [
        Slog::SCENE_SPLIT    => '拆存',
        Slog::SCENE_COMPOUND => '合成',
        Slog::SCENE_FREE     => '释放',
        Slog::SCENE_SELL     => '出售',
        Slog::SCENE_BUY      => '购买',
        Slog::SCENE_SUPER    => '超级用户返S',
    ],
    'remark'       => [
        0 => ':uid 拆分 :total US，奖励： :rate%',
        1 => ':uid 拆分 :total ，锁定S已奖励完。'
    ]
];

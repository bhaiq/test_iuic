<?php

use \App\Models\Ulog;

return [
    'scene_remark' => [
        Ulog::SCENE_SPLIT    => 'Unpack',
        Ulog::SCENE_COMPOUND => 'Combine ',
        Ulog::SCENE_FREE     => 'Release',
    ]
];

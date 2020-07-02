<?php
return [
    'create' => [
        'amount' => [
            'max' => 'US 残高が不足する！'
        ]
    ],
    'update' => [
        'amount' => [
            'min' => '購入数量は下らない：',
            'max' => '購入台数がそれを超えてはならない：',
        ]
    ],
    'del'    => [
        'not_all_success' => '存未完成注文が存在し、キャンセルすることはできません!',
        'success'         => '成功をとりとめる!',
    ]
];
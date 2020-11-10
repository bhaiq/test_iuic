<?php
return [
    'create' => [
        'amount' => [
            'max' => '余额不足！'
        ]
    ],
    'update' => [
        'amount'  => [
            'min' => '买入数量不少于：',
            'max' => '买入数量不得大于：',
        ],
        'do_self' => '无法购买自己发布的售卖！',
    ],
    'del'    => [
        'not_all_success' => '存在未完成订单，无法取消!',
        'success'         => '取消成功!',
    ]
];

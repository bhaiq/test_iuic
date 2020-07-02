<?php
return [
    'sell'    => [
        'amount'  => [
            'max' => 'S余额不足！',
            'min' => '出售数量不能少于1 ！'
        ],
        'success' => '挂单成功，订单处理中请耐心等待!',
        'limit_1' => '每天只能挂一单!',
    ],
    'buy'     => [
        'amount'  => [
            'max' => [
                'us'      => 'US 余额不足 ！',
                'balance' => 'S 总量不得多于 U ！'
            ],
            'min' => '购买数量不能少于1 ！'
        ],
        'success' => '挂单成功，订单处理中请耐心等待!',
        'limit_1' => '每天只能挂一单!',
    ],
    'delBuy'  => [
        'not_exit'    => '订单处理中！',
        'is_over_yes' => '订单已完成！',
        'del_success' => '订单取消成功！',
    ],
    'delSell' => [
        'not_exit'    => '订单处理中！',
        'is_over_yes' => '订单已完成！',
        'del_success' => '订单取消成功！',
    ]
];

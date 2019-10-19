<?php
return [
    'sell'    => [
        'amount'  => [
            'max' => 'S lack of balance！',
            'min' => 'No less than 1!'
        ],
        'success' => 'Pending order successfully, please be patient in order processing!',
        'limit_1' => 'Only one order per day!',
    ],
    'buy'     => [
        'amount'  => [
            'max' => [
                'us'      => 'US lack of balance ！',
                'balance' => 'S no more than U ！'
            ],
            'min' => 'No less than 1!'
        ],
        'success' => 'Pending order successfully, please be patient in order processing!',
        'limit_1' => 'Only one order per day!',
    ],
    'delBuy'  => [
        'not_exit'    => 'Order processing in progress ！',
        'is_over_yes' => 'The order has been completed ！',
        'del_success' => 'The order cancelled successfully！',
    ],
    'delSell' => [
        'not_exit'    => 'Order processing in progress ！',
        'is_over_yes' => 'The order has been completed ！',
        'del_success' => 'The order cancelled successfully！',
    ]
];

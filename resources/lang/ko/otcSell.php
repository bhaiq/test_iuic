<?php
return [
    'create' => [
        'amount' => [
            'max' => 'US 잔고가 부족하다！'
        ]
    ],
    'update' => [
        'amount' => [
            'min' => '매입량이 적지 않다：',
            'max' => '매입량이 많으면 안 된다.：',
        ]
    ],
    'del'    => [
        'not_all_success' => '완료되지 않은 주문이 존재하므로 취소할 수 없습니다!',
        'success' => '취소 성공!',
    ]
];
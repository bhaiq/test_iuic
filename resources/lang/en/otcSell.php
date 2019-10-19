<?php
return [
    'create' => [
        'amount' => [
            'max' => 'US lack of balance！'
        ]
    ],
    'update' => [
        'amount'  => [
            'min' => 'Buy no less：',
            'max' => 'The purchase quantity shall not be greater than：',
        ],
        'do_self' => 'Unable to purchase self-published sales！',
    ],
    'del'    => [
        'not_all_success' => 'There is an unfinished order that cannot be canceled!',
        'success'         => 'Cancel the success!',
    ]
];

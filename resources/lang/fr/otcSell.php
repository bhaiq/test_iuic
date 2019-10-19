<?php
return [
    'create' => [
        'amount' => [
            'max' => 'US Solde insuffisant！'
        ]
    ],
    'update' => [
        'amount' => [
            'min' => 'Pas moins d’acheter：',
            'max' => 'Les quantités achetées ne doivent pas être supérieures：',
        ]
    ],
    'del'    => [
        'not_all_success' => 'Il y a des ordres inachevés, impossible d’annuler!',
        'success' => 'Annulation réussie!',
    ]
];

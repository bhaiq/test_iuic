<?php

use App\Models\User;

return [
    'auth'     => [
        'exist'                     => 'User already exists！',
        'not_login'                 => 'Please login first！',
        'not_find'                  => 'User does not exist！',
        'is_auth_not'               => 'Not verified by real name！',
        'is_auth_has_done'          => 'Has been verified by real name！',
        'is_auth_apply_for'         => 'Real-name authentication has been applied！',
        'is_auth_apply_for_success' => 'Application is successful！',
        'invite_code_not_exist'     => 'The invitation code does not exist！',
    ],
    'password' => [
        'password_error'     => 'Password mistake！',
        'pay_password_error' => 'Payment password error！',
        'old_password_error' => 'Original password error！',
        'set_success'        => 'Password set successfully！',
        'change_success'     => 'Password changed successfully！',
    ],
    'level'    => [
        User::LEVEL_PRIMARY => 'Primary',
        User::LEVEL_MIDDLE  => 'Intermediate',
        User::LEVEL_HIGH    => 'Advanced',
        User::LEVEL_VIP     => 'VIP',
    ]
];

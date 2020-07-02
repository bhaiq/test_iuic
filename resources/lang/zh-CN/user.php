<?php

use App\Models\User;

return [
    'auth'     => [
        'exist'                     => '用户已存在！',
        'not_login'                 => '请先登录！',
        'not_find'                  => '用户不存在！',
        'is_auth_not'               => '未实名验证！',
        'is_auth_has_done'          => '已实名验证！',
        'is_auth_apply_for'         => '实名认证已经申请！',
        'is_auth_apply_for_success' => '申请成功！',
        'invite_code_not_exist'     => '邀请码不存在！',
    ],
    'password' => [
        'password_error'     => '密码错误！',
        'pay_password_error' => '支付密码错误！',
        'old_password_error' => '原密码错误！',
        'set_success'        => '密码设置成功！',
        'change_success'     => '密码修改成功！',
    ],
    'level'    => [
        User::LEVEL_PRIMARY => '初级',
        User::LEVEL_MIDDLE  => '中级',
        User::LEVEL_HIGH    => '高级',
        User::LEVEL_VIP     => 'VIP',
    ]
];

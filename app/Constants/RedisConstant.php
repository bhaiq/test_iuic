<?php

namespace App\Constants;


class RedisConstant {

    /**
     * Redis数据KEY前缀
     */
    const K_PHONE_VC                    = "phone_vc:"; // 手机验证码
    const K_ACCESS_TOKEN                = "access_token:"; // 授权Token
    const K_FREQUENCY                   = "frequency:"; // 频率

}
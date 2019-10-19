<?php

namespace App\Constants;


class HttpConstant {

    /**
     * Http Status Code
     */
    const CODE_200_OK                   = 200; // 请求成功
    const CODE_202_ACCEPTED             = 202; // 数据已接收
    const CODE_302_REDIRECT             = 302; // 重定向
    const CODE_400_BAD_REQUEST          = 400; // 请求错误, 主要是请求参数有误
    const CODE_401_UNAUTHORIZED         = 401; // 未授权
    const CODE_500_SERVER_ERROR         = 500; // 服务器错误

}
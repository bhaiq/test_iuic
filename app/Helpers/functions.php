<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 9:53
 */

// 返回封装
function returnJson($code = '', $msg = '', $data = [])
{
    $returnArr['code'] = $code;
    $returnArr['msg'] = $msg;
    $returnArr['data'] = $data;
    return response()->json($returnArr);
}
<?php

namespace App\Libs;

use PHPUnit\Framework\MockObject\Builder\Match;

class StringLib
{

    public static function isPhone($string)
    {
        return preg_match('/^1[34578]{1}\d{9}$/', $string);
    }

    public static function isEmail($string)
    {
        return preg_match('/^([0-9A-Za-z\-_\.]+)@([0-9a-z]+\.[a-z]{2,3}(\.[a-z]{2})?)$/i', $string);
    }

    public static function password($string)
    {
        return sha1(md5($string) . env('APP_KEY'));
    }

    public static function randomString()
    {
        return sha1(microtime()) . md5(rand(0, 100000));
    }

    public static function uuid()
    {
        $uuid = strtoupper(md5(uniqid(mt_rand(), true)));

        return $uuid;
    }

    public static function base64url_encode($string)
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    public static function base64url_decode($string)
    {
        return base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', STR_PAD_RIGHT));
    }

    public static function randomInteger()
    {
        return rand(10000, 99999);
    }

    /**
     * @param     $num
     * @param int $n
     * @return string
     */
    public static function sprintN($num, int $n = 2): string
    {
        return bcsub($num, 0, $n);
    }

    public static function tranAttr($val)
    {
        $val = json_decode($val, true);
        if (request()->header('locale', 'zh-CN') == 'en') return $val['en'] ?? 0;
        return $val['zh'] ?? 0;
    }
}

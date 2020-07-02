<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/3/14
 * Time: 16:47
 */

namespace App\Services;


use Illuminate\Support\Facades\Redis;
use Mrgoon\AliSms\AliSms;

class SmsService
{

    const REDIS_KEY = 'mobile';

    /**
     * @param $mobile
     */
    public function send($mobile)
    {

        $code = rand(1000, 9999);

        $template = config('aliyunsms.template');

        // 阿里云发送短信
        $aliSms = new AliSms();
        $response = $aliSms->sendSms($mobile, $template, ['code'=> $code]);
        \Log::info('发送验证码返回的数据', [$response]);
        if($response->Code != 'OK'){
            abort(400, trans('communication.send_fail'));
        }

        Redis::setex($this->redisKey($mobile), 1800, $code);

    }


    /**
     * @param $mobile
     * @param $code
     * @return null
     */
    public function verifyCode($mobile, $code)
    {
        //if (env('APP_DEBUG') && $code == 1234) return null;
        if ($code != $this->getCode($mobile)) abort(400, trans('communication.code_error'));
    }

    /**
     * @param $mobile
     * @return mixed
     */
    public function getCode($mobile)
    {
        return Redis::get($this->redisKey($mobile));
    }

    /**
     * @param $mobile
     * @return string
     */
    public function redisKey($mobile)
    {
        return self::REDIS_KEY . $mobile;
    }
}
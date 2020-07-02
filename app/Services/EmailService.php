<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use \Illuminate\Support\Facades\Mail;

class EmailService
{
    const REDIS_KEY = 'email';

    public function sendByType($to, $type)
    {
        $action = [1 => '注册', 2 => '找回密码', 3 => '修改支付密码'];
        $code = rand(1000, 9999);
        Redis::setex($this->redisKey($to), 1800, $code);
        $content['code'] = $code;
        $content['action'] = $action[$type];
        $this->send($to, $content, 'emails.tongzhi');
    }

    public function sendNotice($to, $amount)
    {
        $subject = 'Hulk';
        Mail::send(
            'emails.action',
            ['amount' => $amount],
            function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
    }

    public function sendForChangePayPassword($to)
    {
        $code = rand(1000, 9999);
        Redis::setex($this->redisKey($to), 1800, $code);
        $content['code'] = $code;
        $content['action'] = '修改二级密码';
        $this->send($to, $content, 'emails.tongzhi');
    }

    public function send($to, array $content, $view = 'emails.test')
    {
        $subject = 'Hulk';
        Mail::send(
            $view,
            ['action' => $content['action'],
             'code'   => $content['code']],
            function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
    }

    /**
     * @param $email
     * @param $code
     */
    public function verifyCode($email, $code)
    {
        if (env('APP_DEBUG') && $code == 1234) return null;
        if ($code != $this->getCode($email)) abort(400, trans('communication.code_error'));
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getCode($email)
    {
        return Redis::get($this->redisKey($email));
    }

    /**
     * @param $email
     * @return string
     */
    public function redisKey($email)
    {
        return self::REDIS_KEY . $email;
    }


}

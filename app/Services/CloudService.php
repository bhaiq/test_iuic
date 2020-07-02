<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Lance\Cloud\Facades\Cloud;

class CloudService
{
    const REDIS_KEY = 'phone';

    public function sendForReg($to)
    {
        $code = rand(1000, 9999);
        Redis::setex($this->redisKey($to), 600, $code);
        $this->send($to, $code);
    }

    /**
     * @param        $to 接收号码，被叫为座机时需要添加区号
     * @param        $verifyCode 验证码内容，为数字和英文字母，不区分大小写，长度4-8位
     * @param int    $playTimes 循环播放次数，1－3次，默认播放1次
     * @param int    $displayNum 来电显示的号码，根据平台侧显号规则控制
     * @param string $respUrl 语音验证码状态通知回调地址
     * @param string $lang 语言类型。取值en（英文）、zh（中文）
     * @param string $userData 第三方私有数据
     */
    public function send($to, $verifyCode, $playTimes = 3, $displayNum = 1, $respUrl = '', $lang = 'zh', $userData = '')
    {
        (new JuheSmsServer)->sendCodeSms($to, $verifyCode);
//        Cloud::voiceVerify($verifyCode, $to, $playTimes, $displayNum, $respUrl, $lang, $userData);
    }

    /**
     * @param $phone
     * @param $code
     */
    public function verifyCode($phone, $code)
    {
        if (env('APP_DEBUG') && $code == 1234) return null;
        if ($code != $this->getCode($phone)) abort(400, trans('communication.code_error'));
    }

    /**
     * @param $phone
     * @return mixed
     */
    public function getCode($phone)
    {
        return Redis::get($this->redisKey($phone));
    }

    /**
     * @param $phone
     * @return string
     */
    public function redisKey($phone)
    {
        return self::REDIS_KEY . $phone;
    }

    public function notice($to, $type, $amount = 0)
    {
        $content_type = $this->fromType($type, $amount);
        $this->noticeServer($to, $content_type);
    }

    public function fromType($type, $amount)
    {
        switch ($type) {
            //usdt到账通知
            case 1:
                return '【USCOIN】你所充值的' . $amount . 'usdt已到账。请登录App查看。';
            //Otc 下单通知
            case 2:
                return '【USCOIN】你的订单有新的交易，请及时登录App查看';

        }
    }

    public function noticeServer($to, $content)
    {
        $url = 'http://api.sms.cn/sms/?ac=send&uid=llzy01&pwd=e2f4bd678bbff6d1ccd8cfbf221fd22b&mobile=' . $to . '&content=' . $content;
        $cli = new Client();
        $cli->request('GET', $url);
    }


}

<?php

namespace App\Services;
use GuzzleHttp\Client;

class JuheSmsServer
{

    protected $url = 'http://v.juhe.cn/sms/send';
    protected $appKey = '8c494a1ec6803fbeb37656e91e29b74b';


    public function sendCodeSms($mobile, $code, $tpl_id = '170180') {
        $params = [
            'key' => $this->appKey,
            'mobile' => $mobile,
            'tpl_id' => $tpl_id,
            'tpl_value' => '#code#='.$code
        ];

        $res = $this->juheCurl(http_build_query($params));

        return json_decode($res, true);
    }

    function juheCurl($params = false, $ispost = 0)
    {
        $httpInfo = array();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $this->url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $this->url.'?'.$params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $this->url);
            }
        }
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }




}

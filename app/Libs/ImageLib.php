<?php

namespace App\Libs;

class ImageLib
{

    public static function verifyCode()
    {

        $imgwidth =100; //图片宽度
        $imgheight =40; //图片高度
        $codelen =4; //验证码长度
        $fontsize =20; //字体大小
        $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';
        $font = 'resource/static/font/ArchitectsDaughter.ttf';
        $im=imagecreatetruecolor($imgwidth,$imgheight);
        $while=imageColorAllocate($im,255,255,255);
        imagefill($im,0,0,$while); //填充图像

        $authstr='';
        $_len = strlen($charset)-1;
        for ($i=0;$i<$codelen;$i++) {
            $authstr .= $charset[mt_rand(0,$_len)];
        }
        session_start();
        $_SESSION[SESSION_IMAGE_VERIFY_CODE]=strtolower($authstr);

        for ($i=0;$i<$imgwidth;$i++){
            $randcolor=imageColorallocate($im,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
            imagestring($im,mt_rand(1,5), mt_rand(0,$imgwidth),mt_rand(0,$imgheight), '*',$randcolor);
        }

        for($i=0;$i<$codelen;$i++)
        {
            $randcolor=imagecolorallocate($im,mt_rand(0,255),mt_rand(0,255),mt_rand(0,255));
            imageline($im,0,mt_rand(0,$imgheight),$imgwidth,mt_rand(0,$imgheight),$randcolor);
        }
        $_x=intval($imgwidth/$codelen);
        $_y=intval($imgheight*0.7);
        for($i=0;$i<strlen($authstr);$i++){
            $randcolor=imagecolorallocate($im,mt_rand(0,150),mt_rand(0,150),mt_rand(0,150));
            imagettftext($im,$fontsize,mt_rand(-30,30),$i*$_x+3,$_y,$randcolor,$font,$authstr[$i]);
        }

        return $im;

    }

}
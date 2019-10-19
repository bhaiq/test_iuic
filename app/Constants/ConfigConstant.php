<?php

namespace App\Constants;


class ConfigConstant {

    /**
     * 当前运行环境
     * @return mixed
     */
    public static function APP_ENV()
    {
        return env("APP_ENV");
    }

    /**
     * DEBUG 开关
     * @return mixed
     */
    public static function APP_DEBUG()
    {
        return env("APP_DEBUG");
    }

    /**
     * APP KEY
     * @return mixed
     */
    public static function APP_KEY()
    {
        return env("APP_KEY");
    }

    /**
     * APP密钥
     * @return mixed
     */
    public static function APP_SECRET()
    {
        return env("APP_SECRET");
    }

    /**
     * APP HOST
     * @return mixed
     */
    public static function APP_HOST()
    {
        return env("APP_HOST");
    }

    /**
     * 阿里云KEY
     * @return mixed
     */
    public static function ALI_KEY()
    {
        return env("ALI_KEY");
    }

    /**
     * 阿里云密钥
     * @return mixed
     */
    public static function ALI_SECRET()
    {
        return env("ALI_SECRET");
    }

    /**
     * 阿里云OSS Bucket
     * @return mixed
     */
    public static function ALI_BUCKET()
    {
        return env("ALI_BUCKET");
    }

    /**
     * 阿里云OSS URL
     * @return mixed
     */
    public static function ALI_OSS_URL()
    {
        return env("ALI_OSS_URL");
    }

    /**
     * 阿里云OSS内网地址
     * @return mixed
     */
    public static function ALI_OSS_INTERNAL_URL()
    {
        return env("ALI_OSS_INTERNAL_URL");
    }

    /**
     * 阿里云OSS下载指定地址（XP下IE8不支持https, 所以下载用专用的http地址）
     * @return mixed
     */
    public static function ALI_OSS_DOWNLOAD_URL()
    {
        return env("ALI_OSS_DOWNLOAD_URL");
    }

    /**
     * 鸿合云网关地址
     * @return mixed
     */
    public static function HITE_HOST_URL()
    {
        return env("HITE_HOST_URL");
    }

    /**
     * 鸿合云Key
     * @return mixed
     */
    public static function HITE_HOST_KEY()
    {
        return env("HITE_HOST_KEY");
    }

    /**
     * 鸿合云秘钥
     * @return mixed
     */
    public static function HITE_HOST_SECRET()
    {
        return env("HITE_HOST_SEC");
    }

    /**
     * 微信APPID
     * @return mixed
     */
    public static function WECHAT_APPID()
    {
        return env("WECHAT_APPID");
    }

    /**
     * 微信APP_SECRET
     * @return mixed
     */
    public static function WECHAT_SECRET()
    {
        return env("WECHAT_SECRET");
    }

}
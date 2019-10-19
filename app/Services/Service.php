<?php
/**
 * Project: server
 * User: yongsheng.li
 * Date: 12/01/2017
 * Time: 12:38 PM
 */

namespace App\Services;

use App\Jobs\ProfitSharing;

/**
 * Class Service
 *
 * @package App\Services
 * @property \App\Services\AuthService    $Auth          授权服务;
 * @property \App\Services\SOrderService  $SOrder        S订单处理;
 * @property \App\Services\AccountService $Account       账户管理;
 * @property \App\Services\EmailService   $Email         发送邮件;
 * @property \App\Services\CloudService   $Cloud         发送短信;
 * @property \App\Services\AdminService   $Admin         管理员;
 * @property \App\Services\EthService     $Wallet        钱包服务;
 * @property \App\Services\CountryService $Country       国家;
 * @property \App\Services\S3Service      $_s3           aws S3;
 * @property \App\Services\PusherServer   $_pusher       推送服务;
 */
class Service
{
    private static $_auth;
    private static $_s_order;
    private static $_account;
    private static $_email;
    private static $_mobile;
    private static $_cloud;
    private static $_admin;
    private static $_wallet;
    private static $_country;
    private static $_sTree;
    private static $_pusher;

    public static function auth()
    {
        self::$_auth || self::$_auth = new AuthService();
        return self::$_auth;
    }

    public static function sOrder()
    {
        self::$_s_order || self::$_s_order = new SOrderService();

        return self::$_s_order;
    }

    public static function account()
    {
        self::$_account || self::$_account = new AccountService();
        return self::$_account;
    }

    public static function email()
    {
        self::$_email || self::$_email = new EmailService();
        return self::$_email;
    }

    public static function mobile()
    {
        self::$_mobile || self::$_mobile = new SmsService();
        return self::$_mobile;
    }

    public static function cloud()
    {
        self::$_cloud || self::$_cloud = new CloudService();
        return self::$_cloud;
    }

    public static function admin()
    {
        self::$_admin || self::$_admin = new AdminService();
        return self::$_admin;
    }

    public static function Wallet()
    {
        self::$_wallet || self::$_wallet = new EthService();
        return self::$_wallet;
    }

    public static function country($ip)
    {
        self::$_country || self::$_country = new CountryService($ip);
        return self::$_country;
    }

    public static function s3()
    {
        self::$_sTree || self::$_sTree = new S3Service();
        return self::$_sTree;
    }

    public static function pusher()
    {
        self::$_pusher || self::$_pusher = new PusherServer();
        return self::$_pusher;
    }
}

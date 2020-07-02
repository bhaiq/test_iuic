<?php
/**
 * Project: server
 * User: yongsheng.li
 * Date: 12/01/2017
 * Time: 12:38 PM
 */

namespace App\Services;

use App\Constants\HttpConstant;

/**
 * Class Service
 * @package App\Services
 * @property \App\Services\AuthService           $Auth              授权服务;
 * @property \App\Services\HiteService           $Hite              鸿合云服务;
 * @property \App\Services\PageService           $Page              分页服务;
 */
class ServiceProvider
{

    private static $instance;

    /**
     * @return \App\Services\ServiceProvider
     */
    public static function instance()
    {
        static::$instance || (static::$instance = new ServiceProvider());
        return static::$instance;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {

        if (!isset($this->$name)) {

            $class_name = "\\App\\Services\\{$name}Service";

            if (class_exists($class_name)) {
                $this->$name = new $class_name;
            }
            else {
                abort(HttpConstant::CODE_500_SERVER_ERROR, "class {$class_name} not found");
            }

        }

        return $this->$name;

    }

}
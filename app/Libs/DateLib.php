<?php

namespace App\Libs;

class DateLib
{

    public static function getNow()
    {
        return Date('Y-m-d H:i:s');
    }

    public static function getDay()
    {
        return Date('Y-m-d');
    }

}
<?php

namespace App\Libs;

class DBLib
{

    public static function orderByField($builder, $field, $values)
    {
        return $builder->orderByRaw("FIELD(".$field.", '".join("','", $values)."')");
    }

}
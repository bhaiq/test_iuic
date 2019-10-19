<?php

namespace App\Libs;

class ArrayLib
{

    public static function pick(array $array, array $keys)
    {
        return array_map(function($m) use ($keys) {
            $result = [];
            foreach($keys as $key) $result[$key] = $m[$key];
            return $result;
        }, $array);
    }

    public static function map($array, $fn)
    {
        $result = [];
        foreach($array as $k=> $a){
            $result[] = $fn($a, $k);
        }
        return $result;
    }

    public static function sigcol_arrsort($data,$col,$type=SORT_DESC){
        if(is_array($data)){
            $i=0;
            foreach($data as $k=>$v){
                if(key_exists($col,$v)){
                    $arr[$i] = $v[$col];
                    $i++;
                }else{
                    continue;
                }
            }
        }else{
            return false;
        }
        array_multisort($arr,$type,$data);
        return $data;
    }

}
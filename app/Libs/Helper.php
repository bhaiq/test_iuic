<?php
/**
 * Created by PhpStorm.
 * User: LiYongsheng
 * Date: 26/12/2017
 * Time: 1:32 PM
 */

namespace App\Libs;


use App\Constants\ConfigConstant;
use Illuminate\Database\Eloquent\Builder;

class Helper
{

    /**
     * @param $builder
     *
     * @return \Eloquent
     */
    static function DB_cloneBuilder($builder)
    {
        return clone $builder;
    }

    /**
     * 计算百分比行数
     *
     * @param $inc
     * @param $total
     *
     * @return float|int
     */
    static function Math_ratio($inc, $total)
    {
        return $total > 0 ? ceil($inc * 1000 / $total) / 10 : 0;
    }

    /**
     * 计算从一个时间开始的差值
     *
     * @param int $diff_count
     * @param     $date
     * 
     * @return false|string
     */
    static function Date_withDiff(int $diff_count, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        $diff = $diff_count - (strtotime(date('Y-m-d')) - strtotime($date)) / 86400;
        
        return date('Y-m-d', strtotime("{$diff} day"));
    }

    /**
     * 根据天数差值计算时间区间
     *
     * @param int $date 开始日期
     * @param int $start 开始的差值
     * @param int $end 结束的差值
     *
     * @return array
     */
    static function Date_timeRange(int $start, int $end, $date = null)
    {
        $date = $date ?? date('Y-m-d');

        return [
            static::Date_withDiff($start, $date) . ' 00:00:00',
            static::Date_withDiff($end, $date)   . ' 23:59:59'
        ];
    }

    static function DB_paginate(Builder $builder, $page, $page_size)
    {

        $page = intval($page) < 1 ? 1: intval($page);
        $page_size = intval($page_size) < 1 ? 10: intval($page_size);

        $count = $builder->count();
        $items = $builder->skip(($page-1)*$page_size)->take($page_size)->get();

        return [
            "page"=> $page,
            "page_size"=> $page_size,
            "page_count"=> ceil($count/$page_size),
            "count"=> $count,
            "items"=> $items
        ];

    }

    static function Array_map($items, $handle)
    {
        $result = [];
        foreach($items as $k => $item){
            $result[] = $handle($item, $k);
        }

        return $result;
    }

    static function String_password($string)
    {
        return sha1(md5($string) . ConfigConstant::APP_KEY());
    }

}
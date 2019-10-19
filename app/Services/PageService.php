<?php
/**
 * Project: server
 * User: yongsheng.li
 * Date: 12/01/2017
 * Time: 12:38 PM
 */

namespace App\Services;

use App\Libs\ArrayLib;

class PageService
{

    public function paginate($builder, $page, $page_size, $handle, $count=null)
    {

        $page = intval($page) < 1 ? 1: intval($page);
        $page_size = intval($page_size) < 1 ? 10: intval($page_size);

        $count || $count = $builder->count();

        $items = ArrayLib::map(
            $builder->skip(($page-1)*$page_size)->take($page_size)->get(),
            function($m) use($handle){
                return $handle($m);
            }
        );

        return [
            "page"=> $page,
            "page_size"=> $page_size,
            "page_count"=> ceil($count/$page_size),
            "count"=> $count,
            "items"=> $items
        ];

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/26
 * Time: 14:30
 */

namespace App\Http\Controllers;

use App\Models\MallGood;
use App\Services\Service;
use Illuminate\Http\Request;

class MallCategoryController extends Controller
{

    // 根据商品类别查询商品列表
    public function goods(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $categoryId = $request->get('category_id', 0);

        if($categoryId == 0){

            $res = MallGood::with('store')->where(['type' => 0, 'status' => 1, 'is_affirm' => 1])->latest('top')->latest('sale_num')->paginate($request->get('per_page', 10));

        }else{

            $res = MallGood::with('store')->where(['type' => 0, 'status' => 1, 'is_affirm' => 1])->where('category_id', $categoryId)->latest('top')->latest('sale_num')->paginate($request->get('per_page', 10));
        }

        $result = $res->toArray();

        foreach ($result['data'] as $k => $v){

            $result['data'][$k]['store_mobile'] = $v['store']['mobile'] ?? '';

            unset($result['data'][$k]['store']);

        }

        return $this->response($result);

    }


}
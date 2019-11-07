<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 10:23
 */

namespace App\Http\Controllers;

use App\Models\MallBanner;
use App\Models\MallGood;
use App\Models\MallStore;
use App\Services\Service;
use Illuminate\Http\Request;

class MallController extends Controller
{

    // 获取商城首页信息
    public function index(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $res = MallGood::where(['status' => 1, 'is_affirm' => 1])->latest('top')->latest('sale_num')->paginate($request->get('per_page', 10));

        $isBusiness = 0;
        $remark = '';

        $data = MallStore::where('uid', Service::auth()->getUser()->id)->first();
        if($data){

            if($data->status == 0){
                $isBusiness = 2;
            }else if($data->status == 1){
                $isBusiness = 1;
            }else{
                $isBusiness = 9;
            }
            $remark = $data->remark;

        }

        $result = [
            'is_business' => $isBusiness,
            'remark' => $remark,
            'banner' => MallBanner::latest('top')->pluck('img_url')->toArray(),
        ];

        return $this->response(array_merge(['data' => $res->toArray()], $result));

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/27
 * Time: 11:47
 */

namespace App\Http\Controllers;

use App\Models\MallOrder;
use App\Models\MallStore;
use App\Services\Service;
use Illuminate\Http\Request;

class MallUserController extends Controller
{

    // 用户订单管理页
    public function admin(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = [
            'total_order' => MallOrder::where('uid', Service::auth()->getUser()->id)->count(),
            'dfh_order' => MallOrder::where('uid', Service::auth()->getUser()->id)->where('status', 0)->count(),
            'dsh_order' => MallOrder::where('uid', Service::auth()->getUser()->id)->where('status', 1)->count(),
            'ywc_order' => MallOrder::where('uid', Service::auth()->getUser()->id)->where('status', 2)->count(),
        ];

        return $this->response($result);

    }

    // 用户订单列表页
    public function order(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'type' => 'required|in:1,2,3,4',
        ], [
            'type.required' => trans('api.category_cannot_empty'),
            'type.in' => trans('api.incorrect_type'),
        ]);

        $p = MallOrder::where('uid', Service::auth()->getUser()->id);

        switch ($request->get('type')){

            case 2:
                $p->where('status', 0);
                break;

            case 3:
                $p->where('status', 1);
                break;

            case 4:
                $p->where('status', 2);
                break;

            default:
                break;
        }

        $res = $p->latest('id')->paginate($request->get('per_page', 10))->toArray();

        foreach ($res['data'] as $k => $v){
            $res['data'][$k]['store_name'] = MallStore::find($v['store_id'])->name;
        }

        return $this->response($res);

    }

    // 用户确认收货
    public function confirm(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'order_id' => 'required',
        ], [
            'order_id.required' => trans('api.order_information_cannot_empty'),
        ]);

        $p = MallOrder::where(['uid' => Service::auth()->getUser()->id, 'status' => 1])->find($request->get('order_id'));
        if(!$p){
            $this->responseError(trans('api.parameter_is_wrong'));
        }

        $p->status = 2;
        $p->save();

        $this->responseSuccess(trans('api.operate_successfully'));

    }

}
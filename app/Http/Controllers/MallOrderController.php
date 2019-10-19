<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/26
 * Time: 11:24
 */

namespace App\Http\Controllers;

use App\Models\MallIncomel;
use App\Models\MallOrder;
use App\Models\MallStore;
use App\Services\Service;
use Illuminate\Http\Request;

class MallOrderController extends Controller
{

    // 订单管理页
    public function admin(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id'   => 'required',
        ], [
            'store_id.required' => '店铺信息不能为空',
        ]);

        $store = MallStore::where(['uid' => Service::auth()->getUser()->id])->find($request->get('store_id'));
        if(!$store){
            $this->responseError('数据有误');
        }

        $result = [
            'today_order' => MallOrder::where('store_id', $request->get('store_id'))->whereDate('created_at', now()->toDateString())->count(),
            'today_income' => bcmul(MallIncomel::where('store_id', $request->get('store_id'))->whereDate('created_at', now()->toDateString())->sum('sj_num'), 1, 4),
            'total_order' => MallOrder::where('store_id', $request->get('store_id'))->count(),
            'dfh_order' => MallOrder::where('store_id', $request->get('store_id'))->where('status', 0)->count(),
            'dsh_order' => MallOrder::where('store_id', $request->get('store_id'))->where('status', 1)->count(),
            'ywc_order' => MallOrder::where('store_id', $request->get('store_id'))->where('status', 2)->count(),
        ];

        return $this->response($result);

    }

    // 订单列表
    public function index(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id' => 'required',
            'type' => 'required|in:1,2,3,4',
        ], [
            'store_id.required' => '店铺信息不能为空',
            'type.required' => '类型不能为空',
            'type.in' => '类型不能正确',
        ]);

        $p = MallOrder::where('store_id', $request->get('store_id'));

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

        $res = $p->latest('id')->paginate($request->get('per_page', 10));

        return $this->response($res->toArray());

    }

    // 订单详情
    public function info(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'order_id' => 'required',
        ], [
            'order_id.required' => '订单信息不能为空',
        ]);

        $order = MallOrder::find($request->get('order_id'));
        if(!$order){
            $this->responseError('数据有误');
        }

        $result = $order->toArray();

        $newAddress = '';
        $i = 0;

        $arr = explode(',', $result['to_address']);
        foreach ($arr as $val) {
            if ($i > 0) {
                $newAddress .= $val;
            }
            $i++;
        }

        $result['to_address'] = $newAddress;
        $result['store_name'] = MallStore::find($result['store_id'])->name ?? '';

        return $this->response($result);

    }

    // 订单填写
    public function write(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'order_id' => 'required',
            'kd_name' => 'required',
            'kd_no' => 'required',
        ], [
            'order_id.required' => '订单信息不能为空',
            'kd_name.required' => '快递名称',
            'kd_no.required' => '快递单号',
        ]);

        $order = MallOrder::whereIn('status', [0, 1])->find($request->get('order_id'));
        if(!$order){
            $this->responseError('数据有误');
        }

        \DB::beginTransaction();
        try {

            $order->kd_name = $request->get('kd_name');
            $order->kd_no = $request->get('kd_no');
            $order->status = 1;
            $order->save();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('订单填写异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }


}
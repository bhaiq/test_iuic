<?php

namespace App\Http\Controllers;

use App\Models\OtcOrder;
use App\Services\OtcOrderService;
use App\Services\Service;
use Illuminate\Http\Request;

class OtcOrderController extends Controller
{
    public function _list(Request $request)
    {
        Service::auth()->isLoginOrFail();

        $order = OtcOrder::whereUid(Service::auth()->getUser()->id)->with('otcPublishSell.user', 'otcPublishBuy.user');
        $this->select($order, [
            'type' => 'between:0,1',
        ], $request);

        if ($request->has('status')) {
            // 0 未支付 1 已支付 2 已完成 3 已取消 4 申诉中
            switch ($request->get('status')) {
                case 0:
                    $order->where('is_pay', false)->where([
                        'status' => OtcOrder::STATUS_INIT
                    ]);
                    break;
                case 1:
                    $order->where('is_pay', true)->where([
                        'status' => OtcOrder::STATUS_INIT
                    ]);
                    break;
                case 2:
                    $order->where('status', OtcOrder::STATUS_OVER);
                    break;
                case 3:
                    $order->where('status', OtcOrder::STATUS_CANCEL);
                    break;
                case 4:
                    $order->where('appeal_uid', '>', 0);
                    break;
            }
        }

        $data = $order->orderBy('id', 'desc')->paginate($request->get('per_page', 10))->toArray();

        return $this->response($data);

    }

    public function update($id, Request $request)
    {
        $order = new OtcOrderService($id);
    }

    public function pay($id)
    {
        Service::auth()->isLoginOrFail();
        $order = new OtcOrderService($id);
        $order->pay();
        return $this->responseSuccess();
    }

    public function payCoin($id, Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'password' => 'required|digits:6'
        ],[
            'password.required' => trans('api.trade_password_cannot_empty'),
            'password.digits' => trans('api.trading_password_must_6_digits'),
        ]);

        Service::auth()->isTransactionPasswordYesOrFail($request->input('password'));

        $order = new OtcOrderService($id);
        $order->payCoin();
        return $this->responseSuccess();
    }

    public function appeal($id)
    {
        Service::auth()->isLoginOrFail();
        $order = new OtcOrderService($id);
        $order->appeal();
        return $this->responseSuccess();
    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();
        $order = new OtcOrderService($id);
        $order->del();
        return $this->responseSuccess();
    }
}

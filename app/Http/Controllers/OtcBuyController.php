<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\OtcOrder;
use App\Models\OtcPublishBuy;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtcBuyController extends Controller
{
    public function _list($coin_id, Request $request)
    {
        $buy = OtcPublishBuy::whereIsOver(OtcPublishBuy::IS_OVER_NOT)->whereCoinId($coin_id)->with('user');

        $this->select($buy, [
            'pay_wechat' => 'boolean',
            'pay_alipay' => 'boolean',
            'pay_bank'   => 'boolean',
            'currency'   => 'integer|between:0,1'
        ], $request);

        $data = $buy->orderBy('price', 'desc')->paginate($request->get('per_page'))->toArray();

        return $this->response($data);
    }

    public function create($coin_id, Request $request)
    {
        Service::auth()->isLoginOrFail();

        $lcMinTime = config('trade.lc_min_time', '00:00:00');
        $lcMaxTime = config('trade.lc_max_time', '23:59:59');
        // 增加时间限制
        if(!Carbon::now()->between(Carbon::create(now()->toDateString(). ' ' . $lcMinTime),Carbon::create(now()->toDateString(). ' ' . $lcMaxTime))){
            $this->responseError('该时间段不能交易');
        }

        Service::auth()->isAuthOrFail();
        $user = Service::auth()->getUser();

        // 判断是否是商家
        if($user->is_business != 1){
            $this->responseError('发布需要商家认证');
        }

        $this->validate($request->all(), [
            'amount'     => 'numeric|required|min:1',
            'amount_min' => 'numeric|required',
            'amount_max' => 'required|numeric|gt:amount_min|lte:amount',
            'price'      => 'numeric|required|between:0.1,100',
            'currency'   => 'integer|required|between:0,1',
            'pay_wechat' => 'boolean|required',
            'pay_alipay' => 'boolean|required',
            'pay_bank'   => 'boolean|required',
            'remark'     => 'string|max:100',
        ], [], [

        ]);

        $data                = $request->only('amount', 'amount_min', 'amount_max', 'pay_wechat', 'pay_alipay', 'pay_bank', 'price', 'currency');
        $data['amount_lost'] = $request->input('amount');
        $data['remark']      = $request->input('remark', '未填写');
        $data['coin_id']     = $coin_id;
        $res                 = $user->otcBuy()->create($data);

        return $this->response($res->toArray());


    }

    public function update($id, Request $request)
    {
        Service::auth()->isLoginOrFail();
        $uid = Service::auth()->getUser()->id;
        DB::transaction(function () use ($id, $uid, $request, &$order) {
            $buy = OtcPublishBuy::whereId($id)->lockForUpdate()->first();
            if ($buy->uid == $uid) return $this->responseError('otcBuy.update.do_self');
            $amount = Service::auth()->account($buy->coin_id, Account::TYPE_LC)->amount;
            $max    = min($amount, $buy->amount_lost, $buy->amount_max);

            $this->validate($request->all(), [
                'amount' => 'required|numeric|min:1|max:' . $max,
            ], [
                'amount.min' => trans('otcBuy.update.amount.min') . 1,
                'amount.max' => trans('otcBuy.update.amount.max') . $max,
            ]);

            $amount              = StringLib::sprintN($request->get('amount'));
            $data['amount']      = $amount;
            $data['otc_id']      = $request->get('otc_id');
            $data['price']       = $buy->price;
            $data['total_price'] = $amount * $buy->price;
            $data['uid']         = $uid;
            $data['status']      = OtcOrder::STATUS_INIT;
            $data['type']        = OtcOrder::TYPE_BUY;
            $data['coin_id']     = $buy->coin_id;
            $data['seller_id']   = $uid;
            $data['buyer_id']    = $buy->uid;
            $order               = $buy->order()->create($data);
            $buy->decrement('amount_lost', $amount);
            $buy->refresh();
            if ($buy->amount_lost <= 0.00001) {
                $buy->amount_lost = 0;
                $buy->is_over     = OtcPublishBuy::IS_OVER_YES;
                $buy->save();
            }
            Service::auth()->account($buy->coin_id, Account::TYPE_LC)->increment('amount_freeze', $amount);
            Service::auth()->account($buy->coin_id, Account::TYPE_LC)->decrement('amount', $amount);
        });

        $order->load('otcPublishSell.user', 'otcPublishBuy.user');

        return $this->response($order->toArray());

    }

    public function selfList(Request $request)
    {
        Service::auth()->isLoginOrFail();

        $publish = OtcPublishBuy::whereUid(Service::auth()->getUser()->id);

        switch ($request->get('is_over', 2)) {
            case 0:
                $publish = $publish->where('is_over', OtcPublishBuy::IS_OVER_NOT);
                break;
            case 1:
                $publish = $publish->where('is_over', '>', OtcPublishBuy::IS_OVER_NOT);
                break;
            default;
        }

        $publish                = $publish->orderBy('id', 'desc')->paginate($request->get('per_page'));
        $data                   = $publish->toArray();
        $data['total']          = OtcPublishBuy::whereUid(Service::auth()->getUser()->id)->count();
        $data['total_not_over'] = OtcPublishBuy::whereUid(Service::auth()->getUser()->id)->where('is_over', OtcPublishBuy::IS_OVER_NOT)->count();
        $data['total_over']     = $data['total'] - $data['total_not_over'];

        return $this->response($data);
    }

    public function info($id)
    {
        Service::auth()->isLoginOrFail();
        $sell = OtcPublishBuy::with('order.user')->find($id);
        return $this->response($sell->toArray());
    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();

        $publish = OtcPublishBuy::findOrFail($id);

        if ($publish->is_over != OtcPublishBuy::IS_OVER_NOT) $this->responseError('system.illegal');
        if ($publish->uid != Service::auth()->getUser()->id) $this->responseError('system.illegal');

        DB::transaction(function () use ($publish) {
            $order = $publish->order()->where('status', OtcOrder::STATUS_INIT)->lockForUpdate()->get();
            if (!$order->isEmpty()) $this->responseError('otcBuy.del.not_all_success');
            $publish->is_over = OtcPublishBuy::IS_OVER_CANCEL;
            $publish->save();
        });

        return $this->responseSuccess('otcBuy.del.success');

    }
}

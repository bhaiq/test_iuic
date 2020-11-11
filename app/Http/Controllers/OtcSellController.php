<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\OtcOrder;
use App\Models\OtcPublishSell;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OtcSellController extends Controller
{
    public function _list($coin_id, Request $request)
    {
        $sell = OtcPublishSell::whereIsOver(OtcPublishSell::IS_OVER_NOT)->whereCoinId($coin_id)->with('user');

        $this->select($sell, [
            'pay_wechat' => 'boolean',
            'pay_alipay' => 'boolean',
            'pay_bank'   => 'boolean',
            'currency'   => 'integer|between:0,1'
        ], $request);

        $data = $sell->orderBy('price', 'asc')->paginate($request->get('per_page'))->toArray();
        return $this->response($data);
    }

    public function create($coin_id, Request $request)
    {

        $lcMinTime = config('trade.lc_min_time', '00:00:00');
        $lcMaxTime = config('trade.lc_max_time', '23:59:59');
        // 增加时间限制
        if(!Carbon::now()->between(Carbon::create(now()->toDateString(). ' ' . $lcMinTime),Carbon::create(now()->toDateString(). ' ' . $lcMaxTime))){
            $this->responseError(trans('api.cannot_tradeduring_this_period'));
        }

        Service::auth()->isLoginOrFail();
        $user    = Service::auth()->getUser();
        $account = Service::auth()->account($coin_id, Account::TYPE_LC);

        // 判断是否是商家
        if($user->is_business != 1){
            $this->responseError(trans('api.publishing_requires_merchant_certification'));
        }

        $validator = Validator::make($request->all(), [
            'amount'     => 'numeric|required|min:1|max:' . $account->amount,
            'amount_min' => 'numeric|required',
            'amount_max' => 'numeric|required|gt:amount_min|lte:amount|max:' . $account->amount,
            'price'      => 'numeric|required|between:0.1,100',
            'currency'   => 'integer|required|between:0,1',
            'pay_wechat' => 'boolean|required',
            'pay_alipay' => 'boolean|required',
            'pay_bank'   => 'boolean|required',
            'remark'     => 'string|max:100',
        ], [
            'amount.numeric' => trans('api.requirement_quantity_format_incorrect'),
            'amount.required' => trans('api.quantity_cannot_empty'),
            'amount.min' => trans('api.quantity_cannot_less_than_1'),
            'amount.max' => trans('api.quantity_demanded_must_not_greater_than_balance'),
            'amount_max.gt' => trans('api.maximum_quantity_greater_than_minimum_quantity'),
            'amount_max.lte' => trans('api.maximum_quantity_shall_not_greater_than_quantity_required'),
            'amount_max.max' => trans('api.maximum_quantity_must_exceed_balance'),
        ]);


        if ($validator->fails($validator->errors()->first())) {
            $this->responseError($validator->errors()->first());
        }


        $data = $request->only('pay_wechat', 'pay_alipay', 'pay_bank');
        if (!array_sum($data)) {
            return $this->responseError(trans('api.payment_method_optional'));
        }

        Service::auth()->isAuthOrFail();

        $data                = $request->only('amount', 'amount_min', 'amount_max', 'pay_wechat', 'pay_alipay', 'pay_bank', 'price', 'currency');
        $data['amount_lost'] = $request->input('amount');
        $data['remark']      = $request->input('remark', '未填写');
        $data['coin_id']     = $coin_id;
        $res                 = $user->otcSell()->create($data);
        $account->decrement('amount', $request->input('amount'));
        $account->increment('amount_freeze', $request->input('amount'));
        $account->save();

        return $this->response($res->toArray());


    }

    public function update($id, Request $request)
    {
        Service::auth()->isLoginOrFail();
        $uid = Service::auth()->getUser()->id;
        DB::transaction(function () use ($id, $uid, $request, &$order) {
            $sell = OtcPublishSell::whereId($id)->lockForUpdate()->first();
            if ($sell->uid == $uid) return $this->responseError('otcSell.update.do_self');
            $max = min($sell->amount_max, $sell->amount_lost);
            $min = max(0.1, $sell->amount_min);
            $this->validate($request->all(), [
                'amount' => 'required|numeric|min:' . $min . '|max:' . $max,
            ], [
                'amount.min' => trans('api.otcsell_min') . $min,
                'amount.max' => trans('otcSell.otcsell_max') . $max,
            ]);

            $amount = StringLib::sprintN($request->get('amount'));

            $data['otc_id']      = $request->get('otc_id');
            $data['amount']      = $amount;
            $data['price']       = $sell->price;
            $data['total_price'] = $amount * $sell->price;
            $data['uid']         = $uid;
            $data['status']      = OtcOrder::STATUS_INIT;
            $data['type']        = OtcOrder::TYPE_SELL;
            $data['coin_id']     = $sell->coin_id;
            $data['seller_id']   = $sell->uid;
            $data['buyer_id']    = $uid;
            $order               = $sell->order()->create($data);
            $sell->decrement('amount_lost', $amount);
            $sell->refresh();
            if ($sell->amount_lost <= 0.00001) {
                $sell->amount_lost = 0;
                $sell->is_over     = OtcPublishSell::IS_OVER_YES;
                $sell->save();
            }
        });

        $order->load('otcPublishSell.user', 'otcPublishBuy.user');

        return $this->response($order->toArray());

    }

    public function selfList(Request $request)
    {
        Service::auth()->isLoginOrFail();

        $publish = OtcPublishSell::whereUid(Service::auth()->getUser()->id);

        switch ($request->get('is_over', 2)) {
            case 0:
                $publish = $publish->where('is_over', OtcPublishSell::IS_OVER_NOT);
                break;
            case 1:
                $publish = $publish->where('is_over', '>', OtcPublishSell::IS_OVER_NOT);
                break;
            default;
        }

        $publish                = $publish->orderBy('id', 'desc')->paginate($request->get('per_page'));
        $data                   = $publish->toArray();
        $data['total']          = OtcPublishSell::whereUid(Service::auth()->getUser()->id)->count();
        $data['total_not_over'] = OtcPublishSell::whereUid(Service::auth()->getUser()->id)->where('is_over', OtcPublishSell::IS_OVER_NOT)->count();
        $data['total_over']     = $data['total'] - $data['total_not_over'];

        return $this->response($data);
    }

    public function info($id)
    {
        Service::auth()->isLoginOrFail();
        $sell = OtcPublishSell::with('order.user')->find($id);
        return $this->response($sell->toArray());
    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();

        $publish = OtcPublishSell::findOrFail($id);

        if ($publish->is_over != OtcPublishSell::IS_OVER_NOT) $this->responseError(trans('api.otc_illeagl'));
        if ($publish->uid != Service::auth()->getUser()->id) $this->responseError(trans('api.otc_illeagl'));

        DB::transaction(function () use ($publish) {
            $order = $publish->order()->where('status', OtcOrder::STATUS_INIT)->lockForUpdate()->get();
            if (!$order->isEmpty()) $this->responseError(trans('api.otc_sell_not_all_success'));
            if ($publish->amount_lost) {
                Service::auth()->account($publish->coin_id, Account::TYPE_LC)->increment('amount', $publish->amount_lost);
                Service::auth()->account($publish->coin_id, Account::TYPE_LC)->decrement('amount_freeze', $publish->amount_lost);
            }
            $publish->amount_lost = 0;
            $publish->is_over     = OtcPublishSell::IS_OVER_CANCEL;
            $publish->save();
        });

        return $this->responseSuccess(trans('api.otcsell_success'));

    }
}

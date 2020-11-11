<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/24
 * Time: 15:02
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\PledgeLevel;
use App\Models\PledgeLog;
use App\Models\User;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PledgeController extends Controller
{

    // 质押页面信息
    public function start()
    {

        Service::auth()->isLoginOrFail();

        // 获取质押列表信息
        $pl = PledgeLevel::latest('num')->get();

        $result = [];
        $isDefault = true;
        foreach ($pl as $k => $v){

            if($isDefault && Service::auth()->getUser()->pledge_num >= $v->num){

                $result[] = [
                    'num' => bcmul($v->num, 1),
                    'sort' => $k+1,
                    'bl' => bcmul($v->pledge_bl, 100) . '%',
                    'is_default' => 1,
                ];

                $isDefault = false;

            }else{

                $result[] = [
                    'num' => bcmul($v->num, 1),
                    'sort' => $k+1,
                    'bl' => bcmul($v->pledge_bl, 100) . '%',
                    'is_default' => 0,
                ];

            }

        }

        return $this->response(['data' => $result, 'pledge_num' => bcmul(Service::auth()->getUser()->pledge_num, 1)]);

    }

    // 质押提交
    public function submit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $validator = Validator::make($request->all(), [
            'num' => 'required|integer|min:5000',
            'type' => 'required|in:1,2',
            'paypass' => 'required',
        ], [
            'num.required' => trans('api.quantity_cannot_empty'),
            'num.integer' => trans('api.quantity_must_integer'),
            'num.min' =>trans('api.purchase_quantity_cannot_be_less_than').'5000',
            'type.required' => trans('api.type_cannot_empty'),
            'type.in' => trans('api.incorrect_type'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->first());
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户余额是否充足
        if($request->get('type') == 2){

            // 验证用户是否有已申请的订单
            if(PledgeLog::where(['uid' => Service::auth()->getUser()->id, 'status' => 0])->exists()){
                return $this->responseError(trans('api.applications_have_not_reviewed'));
            }

            if($request->get('num') > Service::auth()->getUser()->pledge_num){
                return $this->responseError(trans('api.insufficient_user_balance'));
            }

            $status = 0;
        }else{

            // 获取那个USDT的币种ID
            $coin = Coin::getCoinByName('IUIC');
            $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

            // 判断用户余额是否充足
            if($request->get('num') > $coinAccount->amount){
                return $this->responseError(trans('api.insufficient_user_balance'));
            }
            $status = 1;
        }

        $plData = [
            'uid' => Service::auth()->getUser()->id,
            'num' => $request->get('num'),
            'type' => $request->get('type'),
            'status' => $status,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 生成日志
            PledgeLog::create($plData);

            if($request->get('type') == 1){

                // 用户质押金额增加
                User::where('id', Service::auth()->getUser()->id)->increment('pledge_num', $request->get('num'));

                // 用户余额减少
                Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $request->get('num'));

                // 用户日志增加
                AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $request->get('num'), 23, 0, Account::TYPE_LC, '质押');

            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('用户质押提交或质押取出出现异常');

            return $this->responseError(trans('api.wrong_operation'));

        }

        return $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 质押日志
    public function log(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $res = PledgeLog::where('uid', Service::auth()->getUser()->id)->latest()->paginate($request->get('per_page', 10))->toArray();

        foreach ($res['data'] as $k => $v){

            $result['type'] = $v['type'];
            $result['status'] = $v['status'];
            $result['num'] = $v['num'];
            $result['coin_name'] = 'IUIC';
            $result['exp'] = $v['type'] == 1 ? '质押' : '取出';
            $result['status_name'] = PledgeLog::STATUS_NAME[$v['status']];
            $result['created_at'] = date('Y/m/d H:i', strtotime($v['created_at']));

            $res['data'][$k] = $result;

        }

        return $this->response($res);

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/19
 * Time: 17:14
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\AuthBusiness;
use App\Models\Coin;
use App\Models\User;
use App\Services\Service;
use Illuminate\Http\Request;

class BusinessController extends Controller
{

    // 获取认证商家信息
    public function start()
    {

        Service::auth()->isLoginOrFail();

        $res = config('business');

        if(Service::auth()->getUser()->is_business == 1){

            $ab = AuthBusiness::where('uid', Service::auth()->getUser()->id)->first();
            if(!$ab){
                $this->responseError(trans('api.authentication_incorrect'));
            }

            $res['coin_name'] = $ab->coin_name;
            $res['coin_type'] = $ab->coin_type;
            $res['coin_num'] = $ab->amount;

        }

        return $this->response($res);

    }

    // 提交认证商家申请
    public function submit()
    {

        Service::auth()->isLoginOrFail();

        $user = Service::auth()->getUser();

        // 判断用户认证商家状态
        if($user->is_business != 0){
            $this->responseError(trans('api.been_authenticated'));
        }

        // 判断用户是否实名认证
        if($user->is_auth != 1){
            $this->responseError(trans('api.dont_authenticated'));
        }

        // 判断用户有木有提交
        if(AuthBusiness::where('uid', Service::auth()->getUser()->id)->exists()){
            $this->responseError(trans('api.wrong_operation'));
        }

        // 获取认证商家需要的信息
        $coinName = config('business.coin_name', 'USDT');
        $coinType = config('business.coin_type', 0);
        $coinNum = config('business.coin_num', 0);

        // 获取币种ID
        $coin = Coin::getCoinByName($coinName);
        $coinAccount = Service::auth()->account($coin->id, $coinType);

        // 判断用户余额是否充足
        if($coinAccount->amount < $coinNum){
            $this->responseError(trans('api.insufficient_user_balance'));
        }

        $bData = [
            'uid' => $user->id,
            'coin_id' => $coin->id,
            'coin_type' => $coinType,
            'amount' => $coinNum,
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 认证表新增
            AuthBusiness::create($bData);

            // 用户表更新
            User::where('id', $user->id)->update(['is_business' => 2]);

            // 用户余额表减少
            Account::reduceAmount($user->id, $coin->id, $coinNum, $coinType);

            // 用户余额减少日志
            //Service::account()->createLog($user->id, $coin->id, $coinNum, AccountLog::SCENE_AUTH_BUSINESS);
            AccountLog::addLog($user->id, $coin->id, $coinNum, AccountLog::SCENE_AUTH_BUSINESS, 0, $coinType, '商家认证');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('认证商家提交出现异常');

            $this->responseError(trans('api.authentication_exception'));

        }

        $this->responseSuccess(trans('api.submit_successfully'));

    }

    // 退出商家认证
    public function quit()
    {

        Service::auth()->isLoginOrFail();

        $ab = AuthBusiness::where('uid', Service::auth()->getUser()->id)->first();
        if(!$ab){
            $this->responseError(trans('api.user_has_not_authenticated'));
        }

        if($ab->status != 1){
            $this->responseError(trans('api.user_business_status'));
        }

        $ab->status = 2;
        $ab->save();

        User::where('id', Service::auth()->getUser()->id)->update(['is_business' => 2]);

        $this->responseSuccess(trans('api.operate_successfully'));

    }

}
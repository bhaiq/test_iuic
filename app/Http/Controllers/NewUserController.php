<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/24
 * Time: 11:45
 */

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\User;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewUserController extends Controller
{

    // 验证用户密码
    public function checkPass(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $validator = Validator::make($request->all(), [
            'pass' => 'required',
            'paypass' => 'required',
        ], [
            'pass.required' => trans('api.login_password_cannot_empty'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->first());
        }

        if(Service::auth()->getUser()->password != StringLib::password($request->get('pass'))){
            return $this->responseError(trans('api.incorrect_login_password'));
        }

        if(Service::auth()->getUser()->transaction_password != StringLib::password($request->get('paypass'))){
            return $this->responseError(trans('api.incorrect_transaction_password'));
        }

        return $this->responseSuccess(trans('api.successful_authentication'));

    }

    // 更改用户手机
    public function updateMobile(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'captcha' => 'required',
        ], [
            'mobile.required' => trans('api.phone_number_cannot_empty'),
            'captcha.required' => trans('api.captcha_cannot_empty'),
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->first());
        }

        // 验证验证码是否有误
        Service::mobile()->verifyCode($request->get('mobile'), $request->get('captcha'));

        User::where('id', Service::auth()->getUser()->id)->update(['mobile' => $request->get('mobile')]);

        return $this->responseSuccess(trans('api.operate_successfully'));

    }

}
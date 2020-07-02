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
            'pass.required' => '登录密码不能为空',
            'paypass.required' => '交易密码不能为空',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->first());
        }

        if(Service::auth()->getUser()->password != StringLib::password($request->get('pass'))){
            return $this->responseError('登录密码有误');
        }

        if(Service::auth()->getUser()->transaction_password != StringLib::password($request->get('paypass'))){
            return $this->responseError('交易密码有误');
        }

        return $this->responseSuccess('验证成功');

    }

    // 更改用户手机
    public function updateMobile(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'captcha' => 'required',
        ], [
            'mobile.required' => '手机号不能为空',
            'captcha.required' => '验证码不能为空',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->first());
        }

        // 验证验证码是否有误
        Service::mobile()->verifyCode($request->get('mobile'), $request->get('captcha'));

        User::where('id', Service::auth()->getUser()->id)->update(['mobile' => $request->get('mobile')]);

        return $this->responseSuccess('操作成功');

    }

}
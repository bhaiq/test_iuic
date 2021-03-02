<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\Authentication;
use App\Models\EcologyCreadit;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\Wallet;
use App\Services\EmailService;
use App\Services\CloudService;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ReleaseOrder;
use App\Models\UserWalletLog;

class UserController extends Controller
{
    public function info()
    {
        Service::auth()->isLoginOrFail();

        $user = User::with('user_info')->where('id', Service::auth()->getUser()->id)->first();
        if(!$user){
            $this->responseError(trans('api.parameter_is_wrong'));
        }

        $data = $user->toArray();

        $data['level'] = 0;
        $data['level_name'] = trans('api.not_have');
        $data['is_node'] = 0;

        if(isset($data['user_info'])){

            $data['level'] = $data['user_info']['level'];
            $data['level_name'] = UserInfo::LEVEL[$data['user_info']['level']];
            $data['is_node'] = (isset($data['user_info']['is_bonus']) && $data['user_info']['is_bonus'] == 1) ? 1 : 0;

        }

        unset($data['user_info']);

        return $this->response($data);
    }

    public function login(Request $request)
    {
//    	 return $this->responseError('服务器维护中');
        $this->validate($request->all(), [
            'username' => 'string|required',
            'password' => 'string|required|between:6,18'
            ],[
             'username.required' => trans('api.name_cannot_be_empty'),
             'password.required' => trans('api.login_password_cannot_empty'),
        ]);

        $user = User::where('new_account', $request->input('username'))->first();
        if (!$user) return $this->responseError(trans('api.not_find_user'));
        if ($user->password == StringLib::password($request->input('password'))) {
          
            // 再验证用户的状态
            if($user->status != 1){
                $this->responseError(trans('api.user_disabled'));
            }
          
            $data          = $user->toArray();
            $data['token'] = Service::auth()->createToken($user->id);

            //生成积分钱包
            $is_has = EcologyCreadit::where('uid',$user->id)->first();
            if(empty($is_has)){
                $creadits = New EcologyCreadit();
                $creadits->created_wallet($user->id);
            }
            return response()->json($data);
        }

        $this->responseError(trans('api.incorrect_login_password'));
    }

    public function logout()
    {
        Service::auth()->logout();
        return $this->responseSuccess();
    }
    
    //注册提交按钮
    public function create(Request $request)
    {
        $rules = [
            'password'    => 'string|required|between:6,18',
            're_password' => 'string|required|same:password',
            'invite_code' => 'string|required',
            'code'        => 'required',
            'type'        => 'integer|required|between:1,2',
            'new_account'     => 'required|between:6,18',
        ];
         \Log::info('info',['code'=> $request->get('int_code')]);
        // return;
        //1为手机2为邮件
        $rules['username'] = $request->input('type') == 1 ? 'required|digits_between:8,16' : 'string|required|max:50|email';

        $this->validate($request->all(), $rules);

        if(!preg_match('/^[a-zA-Z0-9]+$/', $request->get('new_account'))){
            return $this->responseError(trans('api.accounts_can_etters_numbers'));
        }

        // 验证用户账户是否存在
        if(User::where('new_account', $request->get('new_account'))->exists()){
            return $this->responseError(trans('api.account_already_exists'));
        }

        // 获取注册时手机开关
        $registerSwitch = config('extract.register_mobile_switch', 0);
        if($registerSwitch){
            // 验证手机是否已经注册
            $mobileBool = User::where('mobile', $request->get('username'))->exists();
            if($mobileBool){
                return $this->responseError(trans('api.phone_number_has_registered'));
            }
        }else{
            // 验证手机是否超过10个
            $mobileCount = User::where('mobile', $request->get('username'))->count();
            if($mobileCount > 100){
                return $this->responseError(trans('api.accounts_can_registered_exceeded_limit'));
            }
        }

        $pid = 0;
        $pid_path = '';
        if ($request->has('invite_code')) {
            $p_user = User::whereInviteCode(strtoupper($request->input('invite_code')))->first();
            if (!$p_user) return $this->responseError(trans('api.invitation_code_does_not_exist'));
            $pid = $p_user->id;
            $pid_path = $p_user->pid_path . $pid .',';
        }

        if ($request->input('type') == 1) {
            $data['mobile'] = $request->input('username');
//            Service::cloud()->verifyCode($request->input('username'), $request->input('code'));
            // Service::mobile()->verifyCode($request->get('int_code','86').$request->input('username'), $request->input('code'));
        } else {
            $data['email'] = $request->input('username');
            Service::email()->verifyCode($request->input('username'), $request->input('code'));
        }

        $data['int_code'] = $request->get('int_code',86);//区号
        $data['new_account'] = $request->get('new_account');
        $data['nickname'] = $request->get('new_account');
        $data['password'] = $request->input('password');
        $data['pid']      = $pid;
        $data['pid_path'] = $pid_path;
        \Log::info('info',['code'=> $request->get('int_code')]);
        $user = User::create($data);
        $user->refresh();

        return response()->json(array_merge($user->toArray(), [
            'token' => Service::auth()->createToken($user->id)
        ]));
    }

    public function update(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'nickname' => 'required|string|between:2,12',
            'avatar'   => 'required|string',
        ]);

        $user           = Service::auth()->getUser();
        $user->nickname = $request->input('nickname');
        $user->avatar   = $request->input('avatar');

        $user->save();

        return $this->response($user->toArray());

    }
    
    //发送验证码
    public function getCode(Request $request)
    {
        // \Log::info('测试', [$request->get('type'),$request->get('username'),$request->get('int_code')]);
        $username = 0;
        switch ($request->get('type')) {
            //用户注册
            case 1:
                $this->validate($request->all(), [
                    'username' => 'required',
                    'type'     => 'required|integer',
                ]);
//                $user = User::whereEmail($request->input('username'))->orWhere('mobile', $request->input('username'))->first();
//                if ($user) return $this->responseError('user.auth.exist');

                // 验证手机是否超过10个
                $mobileCount = User::where('mobile', $request->get('username'))->count();
                if($mobileCount > 10){
                    return $this->responseError(trans('api.accounts_can_registered_exceeded_limit'));
                }

                $username = $request->get('username');
                break;
            //找回密码
            case 2:
                $this->validate($request->all(), [
                    'username' => 'required',
                    'type'     => 'required|integer',
                ]);
                $user = User::whereEmail($request->input('username'))->orWhere('mobile', $request->input('username'))->first();
                if (!$user) return $this->responseError(trans('api.not_find_user'));
                $username = $request->get('username');
                break;
            //修改支付密码
            case 3:
                Service::auth()->isLoginOrFail();
                $username = Service::auth()->getUser()->mobile ? Service::auth()->getUser()->mobile : Service::auth()->getUser()->email;
                break;

            // 改绑手机号
            case 4:
                $this->validate($request->all(), [
                    'username' => 'required',
                    'type'     => 'required|integer',
                ]);
                $username = $request->get('username');
                break;
        }

        //判断是否是email
        if (strpos($username, '@') !== false) {
            (new EmailService())->sendByType($username, $request->get('type'));
            return $this->responseSuccess('api.send_success_email');
        } else {
            $qint_code = $request->get('int_code','86');
            if($request->get('type') == 3){
                \Log::info('测试', [Service::auth()->getUser()->int_code]);
                $qint_code = Service::auth()->getUser()->int_code;
            }
            Service::mobile()->send($qint_code.$username, $qint_code);
            
            
            return $this->responseSuccess(trans('api.send_success_phone'));
        }
    }
    
    //忘记密码提交按钮
    public function forgetPassword(Request $request)
    {
        $this->validate($request->all(), [
            'new_account'     => 'required',
            'username'    => 'required',
            'password'    => 'string|required|between:6,18',
            're_password' => 'string|required|same:password',
            'code'        => 'integer|required',
        ]);
        $password = $request->input('password');
//        $user     = User::whereEmail($username)->orWhere('mobile', $username)->first();
        $user = User::where('new_account', $request->get('new_account'))->first();
        if (!$user) return $this->responseError(trans('api.not_find_user'));

        // 验证用户账号和手机是否匹配
        if($user->mobile != $request->username){
            return $this->responseError(trans('api.account_and_phone_dont_match'));
        }

        //判断是否是email
        if (strpos($request->input('username'), '@') !== false) {
            Service::email()->verifyCode($user->email, $request->input('code'));
        } else {
            //Service::cloud()->verifyCode($request->input('username'), $request->input('code'));
            Service::mobile()->verifyCode($user->int_code.$user->mobile, $request->input('code'));
        }
        $user->password = StringLib::password($password);
        $user->save();
        \Log::info('忘记密码提交',$request->all());

        return $this->responseSuccess(trans('api.operate_successfully'));
    }

    public function setPassword(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'old_password' => 'string|required|between:6,18',
            'password'     => 'string|required|between:6,18',
            're_password'  => 'string|required|same:password',
        ]);
        $password = $request->input('password');
        $user     = Service::auth()->getUser();
        if ($user->password != StringLib::password($request->input('old_password'))) return $this->responseError(trans('api.old_password_error'));

        $user->password = StringLib::password($password);
        $user->save();
        \Log::info('修改登陆密码',$request->all());
        return $this->responseSuccess(trans('api.operate_successfully'));
    }
    
    //修改二级密码提交按钮
    public function payPassword(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'password'    => 'string|required|digits:6',
            're_password' => 'string|required|same:password',
            'code'        => 'integer|required',
        ]);
        $password = $request->input('password');
        $user     = Service::auth()->getUser();
        //判断是否是email

        if ($user->email) {
            Service::email()->verifyCode($user->email, $request->input('code'));
        } else {
//            Service::cloud()->verifyCode($user->mobile, $request->input('code'));
            Service::mobile()->verifyCode($user->int_code.$user->mobile, $request->input('code'));
        }
        $user->transaction_password = StringLib::password($password);
        $user->save();
        \Log::info('修改二级密码',$request->all());
        return $this->responseSuccess(trans('api.operate_successfully'));
    }

    public function find($username)
    {
        $user = User::whereEmail($username)->orWhere('mobile', $username)->select(['id', 'nickname', 'email', 'mobile', 'avatar'])->first();

        if (!$user) $this->responseError(trans('api.not_find_user'));

        $data           = $user->toArray();
        $data['wallet'] = Wallet::whereUid($user->id)->get()->toArray();

        return $this->response($data);
    }

    public function auth(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'name'      => 'required|string|between:2,20',
            'number'    => 'required|between:10,20',
            'img_front' => 'required|image',
            'img_back'  => 'required|image',
        ]);

        Service::auth()->getUser()->is_auth && $this->responseError(trans('api.is_auth_has_done'));

        $path = $request->file('img_front')->store('us');
        Storage::setVisibility($path, 'public');
        $img_front = Storage::url($path);

        $path = $request->file('img_back')->store('us');
        Storage::setVisibility($path, 'public');
        $img_back = Storage::url($path);

        $user          = Service::auth()->getUser();

        \DB::beginTransaction();
        try {
            // 获取用户验证开关
            $userAuthSwitch = config('extract.user_auth_switch', 0);
            if($userAuthSwitch){
                $user->is_auth = User::AUTH_ON;
            }else{
                $user->is_auth = User::AUTH_SUCCESS;

                // 赠送客户矿池
                $uid = Service::auth()->getUser()->id;
                $reward = config('reward.auth_reward', 100);
                // $reward = 200;
                
                if(UserInfo::where('uid', $uid)->exists()){
                    UserInfo::where('uid', $uid)->increment('buy_total', $reward);
                }else{
                    $userArr = User::where('id', $uid)->first();
                    $ulData = [
                        'uid' => $userArr->id,
                        'pid' => $userArr->pid,
                        'pid_path' => $userArr->pid_path,
                        'level' => 0,
                        'buy_total' => $reward,
                        'buy_count' => 0,
                        'created_at' => now()->toDateTimeString(),
                    ];
                    UserInfo::create($ulData);
                }
                
                // 释放订单表增加
                $reoData = [
                    'uid' => $uid,
                    'total_num' => $reward,
                    'today_max' => bcmul($reward, 0.01, 2),
                    'release_time' => now()->subDay()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ];
                ReleaseOrder::create($reoData);

                // 奖励日志
                UserWalletLog::addLog($uid, 0, 0, '实名认证奖励', '+', $reward, 2, 1);

            }

            $user->save();

            $auth            = Authentication::firstOrCreate(['uid' => Service::auth()->getUser()->id]);
            $auth->name      = $request->input('name');
            $auth->number    = $request->input('number');
            $auth->img_front = $img_front;
            $auth->img_back  = $img_back;
            $auth->save();
            $auth->refresh();

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
        }

        return $this->responseSuccess(trans('api.is_auth_apply_for_success'));
    }

}

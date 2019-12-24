<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\Authentication;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\Wallet;
use App\Services\EmailService;
use App\Services\CloudService;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function info()
    {
        Service::auth()->isLoginOrFail();

        $user = User::with('user_info')->where('id', Service::auth()->getUser()->id)->first();
        if(!$user){
            $this->responseError('数据有误');
        }

        $data = $user->toArray();

        $data['level'] = 0;
        $data['level_name'] = '无';
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
        $this->validate($request->all(), [
            'username' => 'string|required',
            'password' => 'string|required|between:6,18'
        ]);

        $user = User::whereEmail($request->input('username'))->orWhere('mobile', $request->input('username'))->first();
        if (!$user) return $this->responseError('user.auth.not_find');
        if ($user->password == StringLib::password($request->input('password'))) {
          
            // 再验证用户的状态
            if($user->status != 1){
                $this->responseError('用户被禁用');
            }
          
            $data          = $user->toArray();
            $data['token'] = Service::auth()->createToken($user->id);
            return response()->json($data);
        }

        $this->responseError('user.password.password_error');
    }

    public function logout()
    {
        Service::auth()->logout();
        return $this->responseSuccess();
    }

    public function create(Request $request)
    {
        $rules = [
            'password'    => 'string|required|between:6,18',
            're_password' => 'string|required|same:password',
            'invite_code' => 'string|required',
            'code'        => 'required',
            'type'        => 'integer|required|between:1,2',
            'account'     => 'string|required|between:6,18',
        ];

        //1为手机2为邮件
        $rules['username'] = $request->input('type') == 1 ? 'required|digits_between:8,16|unique:user,mobile' : 'string|required|max:50|email|unique:user,email';

        $this->validate($request->all(), $rules);

        // 验证用户账户是否存在
        if(User::where('account')->exists()){
            return $this->responseError('账号已经存在');
        }

        $pid = 0;
        $pid_path = '';
        if ($request->has('invite_code')) {
            $p_user = User::whereInviteCode(strtoupper($request->input('invite_code')))->first();
            if (!$p_user) return $this->responseError('user.auth.invite_code_not_exist');
            $pid = $p_user->id;
            $pid_path = $p_user->pid_path . $pid .',';
        }

        if ($request->input('type') == 1) {
            $data['mobile'] = $request->input('username');
//            Service::cloud()->verifyCode($request->input('username'), $request->input('code'));
            Service::mobile()->verifyCode($request->input('username'), $request->input('code'));
        } else {
            $data['email'] = $request->input('username');
            Service::email()->verifyCode($request->input('username'), $request->input('code'));
        }

        $data['account'] = $request->get('account');
        $data['nickname'] = $request->input('username');
        $data['password'] = $request->input('password');
        $data['pid']      = $pid;
        $data['pid_path'] = $pid_path;

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

    public function getCode(Request $request)
    {
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
                $username = $request->get('username');
                break;
            //找回密码
            case 2:
                $this->validate($request->all(), [
                    'username' => 'required',
                    'type'     => 'required|integer',
                ]);
                $user = User::whereEmail($request->input('username'))->orWhere('mobile', $request->input('username'))->first();
                if (!$user) return $this->responseError('user.auth.not_find');
                $username = $request->get('username');
                break;
            //修改支付密码
            case 3:
                Service::auth()->isLoginOrFail();
                $username = Service::auth()->getUser()->mobile ? Service::auth()->getUser()->mobile : Service::auth()->getUser()->email;
                break;
        }


        //判断是否是email
        if (strpos($username, '@') !== false) {
            (new EmailService())->sendByType($username, $request->get('type'));
            return $this->responseSuccess('communication.email.send_success');
        } else {
//            (new CloudService())->sendForReg($username);
            Service::mobile()->send($username);
            return $this->responseSuccess('communication.mobile.send_success');
        }
    }

    public function forgetPassword(Request $request)
    {
        $this->validate($request->all(), [
            'account'     => 'required',
            'username'    => 'required',
            'password'    => 'string|required|between:6,18',
            're_password' => 'string|required|same:password',
            'code'        => 'integer|required',
        ]);
        $username = $request->input('username');
        $password = $request->input('password');
//        $user     = User::whereEmail($username)->orWhere('mobile', $username)->first();
        $user = User::where('account', $username)->first();
        if (!$user) return $this->responseError('user.auth.not_find');
        //判断是否是email
        if (strpos($request->input('username'), '@') !== false) {
            Service::email()->verifyCode($request->input('username'), $request->input('code'));
        } else {
            //Service::cloud()->verifyCode($request->input('username'), $request->input('code'));
            Service::mobile()->verifyCode($request->input('username'), $request->input('code'));
        }
        $user->password = StringLib::password($password);
        $user->save();


        return $this->responseSuccess('user.password.set_success');
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
        if ($user->password != StringLib::password($request->input('old_password'))) return $this->responseError('user.password.old_password_error');

        $user->password = StringLib::password($password);
        $user->save();

        return $this->responseSuccess('user.password.change_success');
    }

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
            Service::mobile()->verifyCode($user->mobile, $request->input('code'));
        }
        $user->transaction_password = StringLib::password($password);
        $user->save();

        return $this->responseSuccess('user.password.change_success');
    }

    public function find($username)
    {
        $user = User::whereEmail($username)->orWhere('mobile', $username)->select(['id', 'nickname', 'email', 'mobile', 'avatar'])->first();

        if (!$user) $this->responseError('user.auth.not_find');

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

        Service::auth()->getUser()->is_auth && $this->responseError('user.auth.is_auth_has_done');

        $path = $request->file('img_front')->store('us');
        Storage::setVisibility($path, 'public');
        $img_front = Storage::url($path);

        $path = $request->file('img_back')->store('us');
        Storage::setVisibility($path, 'public');
        $img_back = Storage::url($path);

        $user          = Service::auth()->getUser();
        $user->is_auth = User::AUTH_ON;
        $user->save();

        $auth            = Authentication::firstOrCreate(['uid' => Service::auth()->getUser()->id]);
        $auth->name      = $request->input('name');
        $auth->number    = $request->input('number');
        $auth->img_front = $img_front;
        $auth->img_back  = $img_back;
        $auth->save();
        $auth->refresh();

        return $this->responseSuccess('user.auth.is_auth_apply_for_success');
    }

}

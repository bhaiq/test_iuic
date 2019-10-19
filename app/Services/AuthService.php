<?php

namespace App\Services;

use App\Constants\AccountConstant;
use App\Constants\ConfigConstant;
use App\Constants\HeaderConstant;
use App\Constants\HttpConstant;
use App\Libs\StringLib;
use App\Models\AccessToken;
use App\Models\Account;
use App\Models\Admin\RBAC\Access;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AuthService
{

    const REDIS_KEY_LOGIN_TOKEN = 'login_token_';
    const REDIS_KEY = 'auth_';

    private $client;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Request
     */
    private $request;


    function init(Request $request)
    {
        $this->user    = null;
        $this->request = $request;
        $this->client  = $request->header(HeaderConstant::AUTH_CLIENT, AccountConstant::CLIENT_WEB);
        $access_token  = $request->header(HeaderConstant::AUTH_TOKEN);
		
        if (!$access_token) {
            return null;
        }

        $access_token = AccessToken::whereToken($access_token)->first();

        empty($access_token) && abort(HttpConstant::CODE_401_UNAUTHORIZED, trans('user.auth.not_login'));

        //            $attributes = explode(":", StringLib::base64url_decode($access_token));

        //            $user_id    = $attributes[1];
        $user_id    = $access_token->uid;
        $this->user = User::findOrFail($user_id);

    }

    /**
     * 设置当前授权用户
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * 获取当前用户
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $coin_id
     * @param int $type
     * @return Account|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function account(int $coin_id, $type = 0)
    {
        return Account::whereUid($this->user->id)->whereCoinId($coin_id)->whereType($type)->first() ?? abort(HttpConstant::CODE_400_BAD_REQUEST, '钱包数据有误');
    }

    /**
     * 获取当前访问平台
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * 判断平台是否在平台列表中
     *
     * @param array $clients
     * @return bool
     */
    public function clientIn(array $clients)
    {
        return in_array($this->client, $clients, false);
    }

    public function isAuthOrFail()
    {
        $this->isAuth() || abort(400, trans('user.auth.is_auth_not'));
    }

    public function isAuth()
    {
        return $this->user->is_auth;
    }

    public function isTransactionPasswordYesOrFail($password)
    {
        $this->isTransactionPasswordYes($password) || abort(400, trans('user.password.pay_password_error'));
    }

    /**
     * @param $password
     * @return bool
     */
    public function isTransactionPasswordYes($password)
    {
        return $this->getUser()->transaction_password == StringLib::password($password);
    }

    /**
     * 验证用户是否能登录某平台
     *
     * @param        $user_type
     * @param string $fail_msg
     */
    public function clientOnlyOrFail($user_type, $fail_msg = "当前用户不允许登录此平台")
    {

        $client_config = [
            AccountConstant::CLIENT_WEB_CREATION => [
                AccountConstant::TYPE_ADMIN, AccountConstant::TYPE_CREATION
            ],
            AccountConstant::CLIENT_WEB_ADMIN    => [AccountConstant::TYPE_ADMIN],
            AccountConstant::CLIENT_WEB_TEACHER  => [AccountConstant::TYPE_TEACHER],
            AccountConstant::CLIENT_APP_STUDENT  => [AccountConstant::TYPE_STUDENT]
        ];

        if (!in_array($user_type, $client_config[intval($this->client)])) {
            abort(HttpConstant::CODE_400_BAD_REQUEST, $fail_msg);
        }

    }

    public function createToken($user_id)
    {
        //        $key   = $this->redisLoginKey($user_id);
        $token = self::REDIS_KEY . hash_hmac("sha256", microtime(), env("APP_KEY"));
        AccessToken::updateOrCreate(['uid' => $user_id], ['token' => $token, 'ip' => $this->request->getClientIp()]);
        //        Redis::setex($token, $this->tokenExpire(), $key);
        return $token;
    }

    public function redisLoginKey($user_id)
    {
        return StringLib::base64url_encode(self::REDIS_KEY_LOGIN_TOKEN . $this->client . ":" . $user_id);
    }

    public function tokenExpire()
    {

        if ($this->clientIn([AccountConstant::CLIENT_APP_STUDENT])) {
            return 86400 * 30 * 3;
        } else {
            return 86400;
        }

    }

    public function logout()
    {
        $this->user && Redis::del($this->redisLoginKey($this->user->id));
    }

    // -----------
    // 权限访问列表
    /**
     * 判断是否登录
     *
     * @return bool 是否登录
     */
    public function isLogin()
    {
        return isset($this->user);
    }

    /**
     * 判断是否登录, 如果未登录则直接退出程序并给出相应的错误
     *
     * @param string $msg 需要提示的错误信息
     * @return $this
     */
    public function isLoginOrFail()
    {
        $this->isLogin() || abort(HttpConstant::CODE_401_UNAUTHORIZED, trans('user.auth.not_login'));

        return $this;
    }

    /**
     * 判断用户类型是否有效
     *
     * @param int $type 用户类型
     * @return bool
     */
    public function isUserType(int $type)
    {
        return $this->user && ($this->user->type == AccountConstant::TYPE_ADMIN || $this->user->type == $type);
    }

    /**
     * 判断用户类型是否有效, 如果无效则直接退出程序并给出相应的错误
     *
     * @param int    $type 用户类型
     * @param string $msg 需要提示的错误信息
     * @return $this
     */
    public function isUserTypeOrFail(int $type, $msg = "您没有操作权限")
    {
        $this->isUserType($type) || abort(HttpConstant::CODE_401_UNAUTHORIZED, $msg);

        return $this;
    }


    public function setPassword($username, $password)
    {
        $user           = User::whereEmail($username)->orWhere('mobile', $username)->firstOrFail();
        $user->password = StringLib::password($password);
        $user->save();
    }

    public function getIp(int $uid)
    {
        $access = AccessToken::whereUid($uid)->first();
        return $access->ip;
    }

}

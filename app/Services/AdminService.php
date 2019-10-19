<?php

namespace App\Services;

use App\Constants\AccountConstant;
use App\Constants\HeaderConstant;
use App\Constants\HttpConstant;
use App\Libs\StringLib;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdminService
{

    const REDIS_KEY_LOGIN_TOKEN = 'admin_login_token_';
    const REDIS_KEY = 'auth_admin_';

    private $client;

    /**
     * @var Member
     */
    private $member;

    /**
     * @var Request
     */
    private $request;
    private $white_list = [
        '/admin/memberLogin'
    ];


    function init(Request $request)
    {
        $this->request = $request;
        $this->member  = null;
        $this->client  = $request->header(HeaderConstant::AUTH_CLIENT, AccountConstant::CLIENT_WEB);
        $access_token  = $request->header(HeaderConstant::AUTH_TOKEN);

        if ($this->whiteList()) return null;

        $access_token = Redis::get($access_token);

        $attributes = explode(":", StringLib::base64url_decode($access_token));

        $member_id    = $attributes[1];
        $this->member = Member::findOrFail($member_id);

        $this->can($request->getRequestUri());


    }

    public function whiteList()
    {
        $uri = $this->request->getRequestUri();
        return in_array($uri, $this->white_list);
    }

    /**
     * 设置当前授权用户
     *
     * @param Member $member
     */
    public function setUser(Member $member)
    {
        $this->member = $member;
    }

    /**
     * 获取当前用户
     *
     * @return Member
     */
    public function getUser()
    {
        return $this->member;
    }

    public function can($uri)
    {
        if ($this->isRole(Member::ROLE_SUPER)) return;
        $uris = $this->member->access->pluck('uri')->toArray();
        in_array($uri, $uris) || abort(400, '您没有操作权限！');
    }

    /**
     * @param int $role_id
     * @return bool
     */
    public function isRole(int $role_id)
    {
        return $this->member->role_id == $role_id;
    }

    public function createToken($user_id)
    {
        $key   = $this->redisLoginKey($user_id);
        $token = self::REDIS_KEY . hash_hmac("sha256", microtime(), env("APP_KEY"));
        Redis::setex($token, 86400 * 30 * 3, $key);
        return $token;
    }

    public function redisLoginKey($user_id)
    {
        return StringLib::base64url_encode(self::REDIS_KEY_LOGIN_TOKEN . $this->client . ":" . $user_id);
    }

    public function logout()
    {
        $this->member && Redis::del($this->redisLoginKey($this->member->id));
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
        return isset($this->member);
    }

    /**
     * 判断是否登录, 如果未登录则直接退出程序并给出相应的错误
     *
     * @param string $msg 需要提示的错误信息
     * @return $this
     */
    public function isLoginOrFail($msg = "请先登录")
    {
        $this->isLogin() || abort(HttpConstant::CODE_401_UNAUTHORIZED, $msg);

        return $this;
    }


    public function setPassword($email, $password)
    {
        $user           = Member::whereEmail($email)->firstOrFail();
        $user->password = StringLib::password($password);
        $user->save();
    }

}

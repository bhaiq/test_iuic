<?php

namespace App\Models;

use App\Libs\StringLib;
use Carbon\Carbon;


/**
 * App\Models\User
 *
 * @property int                                                                        $id
 * @property string                                                                     $nickname 昵称
 * @property string                                                                     $email 电子邮箱
 * @property string                                                                     $avatar 头像
 * @property string                                                                     $mobile 手机号码
 * @property string                                                                     $int_code 国际冠码
 * @property string                                                                     $password 密码
 * @property string                                                                     $transaction_password 交易密码
 * @property int                                                                        $status 0:禁用;1启用
 * @property int                                                                        $pid 上级ID
 * @property string                                                                     $invite_code 邀请码
 * @property int                                                                        $type 用户类型 0：普通用户 1:管理员
 * @property int|null                                                                   $created_at
 * @property int|null                                                                   $updated_at
 * @property int                                                                        $is_auth 实名认证：0 未实名认证 1 实名认证 , 2 申请中
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[]        $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ModePay[]        $modePay
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OtcPublishBuy[]  $otcBuy
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OtcPublishSell[] $otcSell
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Wallet[]         $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIntCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIsAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereTransactionPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read mixed                                                                 $has_pay_password
 * @property-read mixed                                                                 $invite_url
 * @property string|null $pid_path 上级路径
 * @property int $is_business 商家认证：0为未认证, 1为认证, 2为申请中
 * @property-read \App\Models\UserInfo $user_info
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIsBusiness($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePidPath($value)
 */
class User extends Model
{
    protected $table = 'user';

    protected $fillable = ['mobile', 'email', 'password', 'pid', 'invite_code', 'type', 'path', 'pid_path', 'nickname', 'new_account','int_code'];

    protected $hidden = ['password', 'transaction_password'];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'is_vip'     => 'integer',
    ];

    protected $appends = ['invite_url', 'has_pay_password'];

    const TYPE_CONSUMER = 0;
    const TYPE_LEVEL_HIGH = self::LEVEL_HIGH;
    const TYPE_LEVEL_VIP = self::LEVEL_VIP;
    const TYPE_LEVEL_SPECIAL = [
        self::TYPE_LEVEL_HIGH, self::LEVEL_VIP
    ];

    const AUTH_ON = 2;
    const AUTH_SUCCESS = 1;

    const LEVEL_PRIMARY = 1;
    const LEVEL_MIDDLE = 2;
    const LEVEL_HIGH = 3;
    const LEVEL_VIP = 4;

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $model) {
            $model->update([
                'invite_code' => strtoupper(base_convert(substr(time() * 1.3, 0, 6) + $model->id, 10, 36)),
                'password'    => StringLib::password($model->password),
            ]);
            $coins = Coin::all()->pluck('coin_type', 'id')->toArray();

            $account = [];
            foreach ($coins as $coin => $coin_type) {
                $account[] = ['uid' => $model->id, 'coin_id' => $coin, 'created_at' => Carbon::now(), 'type' => 0];
                $account[] = ['uid' => $model->id, 'coin_id' => $coin, 'created_at' => Carbon::now(), 'type' => 1];
            }

            Account::insert($account);

//            HoldCoin::create(['uid'=>$model->id]);
            //            Service::Wallet()->getAddress($model->id, Wallet::TYPE_ETH);
        });
    }

    public function getHasPayPasswordAttribute()
    {
        return boolval($this->transaction_password);
    }

    public function getInviteUrlAttribute()
    {
        return url('/register');
    }

    //    public function setPassword($password)
    //    {
    //        return StringLib::password($password);
    //    }

    public function getNicknameAttribute($nickname)
    {
        return $nickname ? $nickname : '佚名';
    }

    //    public function getMobileAttribute($mobile)
    //    {
    //        return $mobile ? $mobile : '未填写';
    //        //        return $mobile ? substr($mobile, 0, 3) . '****' . substr($mobile, -4) : '';
    //    }


    public function wallet()
    {
        return $this->hasMany('App\Models\Wallet', 'uid', 'id');
    }

    public function account()
    {
        return $this->hasMany('App\Models\Account', 'uid', 'id');
    }

    public function otcSell()
    {
        return $this->hasMany('App\Models\OtcPublishSell', 'uid', 'id');
    }

    public function otcBuy()
    {
        return $this->hasMany('App\Models\OtcPublishBuy', 'uid', 'id');
    }

    public function modePay()
    {
        return $this->hasMany('App\Models\ModePay', 'uid', 'id');
    }

    public function createUserWallet($uid)
    {
        $walletObj = [
            'uid'     => $uid,
            'type'    => Wallet::TYPE_ETH,
            'address' => $this->getUserEthAddress($uid)
        ];
        Wallet::insert($walletObj);
    }

    public function getUserEthAddress($uid)
    {
        $url     = 'http://127.0.0.1:8080/eth/getUserAddress/' . $uid;
        $address = $this->post($url);
        return $address;
    }

    function post($url, $post = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    public function user_info()
    {
        return $this->hasOne(UserInfo::class, 'uid', 'id');
    }

}

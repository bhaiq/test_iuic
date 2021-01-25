<?php

namespace App\Models;

/**
 * App\Models\AccountLog
 *
 * @property int                   $id
 * @property int                   $uid
 * @property int                   $coin_id
 * @property float                 $amount
 * @property int                   $scene 1 充值 2 提币 3 币币转法币 4 法币转币币 5 交易划出 6 交易收入 7 交易取消 8 交易返还 9 法币划出 10 法币买入 11 法币取消
 * @property int                   $type 0 减少 1 增加
 * @property string                $remark
 * @property array|null            $extend
 * @property int|null              $created_at
 * @property int|null              $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereCoinId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereExtend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereScene($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountLog whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Coin $coin
 */
class AccountLog extends Model
{
    protected $table = 'account_log';
    protected $fillable = ['uid', 'coin_id', 'scene', 'amount', 'type', 'remark', 'extend', 'coin_type'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'extend'     => 'array',
    ];

    const SCENE_RECHARGE = 1;
    const SCENE_EXTRACT = 2;
    const SCENE_TO_LEGAL_COIN = 3;
    const SCENE_TO_COIN_COIN = 4;
    const SCENE_EX_OUT = 5;
    const SCENE_EX_IN = 6;
    const SCENE_EX_DEL = 7;
    const SCENE_EX_BACK = 8;
    const SCENE_LEGAL_OUT = 9;
    const SCENE_LEGAL_IN = 10;
    const SCENE_LEGAL_DEL = 11;
    const SCENE_GOODS_BUY = 12;
    const SCENE_Trade_RELEASE = 13;
    const SCENE_SCENE_BONUS = 14;
    const SCENE_SCENE_ADMIN_BONUS = 15;
    const SCENE_AUTH_BUSINESS = 16;
    const SCENE_REMOVE_BUSINESS = 17;
    const SCENE_EXTRA_BONUS = 18;
  
  	const SCENE_CAPTAIN_AWARD = 100;
  
  	const SCENE_INDE_HEAD = 26;
  	const SCENE_INDE_MANA = 27;
  	const SCENE_ALL_FIRST = 28;
  	const SCENE_COMM_SANXIA = 29;
  	const SCENE_SPEED_RELEASE = 30;
  	const SCENE_SPEED_BONUS = 31;
  	const SCENCE_PERFORMANCE_BONUS = 32;

    const SCENE_IN = [
        self::SCENE_RECHARGE, self::SCENE_EX_IN, self::SCENE_EX_BACK, self::SCENE_LEGAL_IN, self::SCENE_EX_DEL,
        self::SCENE_Trade_RELEASE, self::SCENE_SCENE_BONUS, self::SCENE_SCENE_ADMIN_BONUS,self::SCENE_REMOVE_BUSINESS
    ];

    public static function getType(int $scene)
    {
        return in_array($scene, self::SCENE_IN) ? 1 : 0;
    }

    public static function getRemark(int $scene)
    {
        switch ($scene) {
            case self::SCENE_RECHARGE:
                return '充值';
            case self::SCENE_EXTRACT:
                return '提币';
            case self::SCENE_TO_LEGAL_COIN:
                return '币币转法币';
            case self::SCENE_TO_COIN_COIN:
                return '法币转币币';
            case self::SCENE_EX_OUT:
                return '交易划出';
            case self::SCENE_EX_IN:
                return '交易收入';
            case self::SCENE_EX_DEL:
                return '交易取消';
            case self::SCENE_EX_BACK:
                return '交易返还';
            case self::SCENE_LEGAL_OUT:
                return '法币划出';
            case self::SCENE_LEGAL_IN:
                return '法币买入';
            case self::SCENE_LEGAL_DEL:
                return '法币取消';
            case self::SCENE_GOODS_BUY:
                return '商品购买';
            case self::SCENE_Trade_RELEASE:
                return '交易释放';
            case self::SCENE_SCENE_BONUS:
                return '实时节点奖';
            case self::SCENE_SCENE_ADMIN_BONUS:
                return '实时管理奖';
            case self::SCENE_AUTH_BUSINESS:
                return '商家认证';
            case self::SCENE_REMOVE_BUSINESS:
                return '取消商家认证';
            case self::SCENE_EXTRA_BONUS:
                return '额外的奖励';
            case self::SCENE_CAPTAIN_AWARD:
                return '能量团队长奖';
            case self::SCENE_INDE_HEAD:
                return '独立团队长奖';
            case self::SCENE_INDE_MANA:
                return '独立管理奖';
            case self::SCENE_ALL_FIRST:
                return '全网首次能量报单合伙人奖';
            case self::SCENE_COMM_SANXIA:
//                return '社群分享奖-伞下';
                return '运营中心分享奖-伞下';
            case self::SCENE_SPEED_RELEASE:
                return '加速奖';
            case self::SCENE_SPEED_BONUS:
                return '团队长加速分红奖';
            case self:: SCENCE_PERFORMANCE_BONUS:
                return '团队长业绩分红奖';
        }
    }

    public function coin()
    {
        return $this->belongsTo('App\Models\Coin');
    }

    public static function addLog($uid, $coinId, $amount, $scene, $type, $coinType, $remark = '', $extend = '[]')
    {

        $data = [
            'uid' => $uid,
            'coin_id' => $coinId,
            'amount' => $amount,
            'scene' => $scene,
            'type' => $type,
            'coin_type' => $coinType,
            'remark' => $remark,
            'extend' => $extend
        ];

        AccountLog::create($data);

        \Log::info('增加一条用户币种余额日志', $data);

        return true;

    }
}

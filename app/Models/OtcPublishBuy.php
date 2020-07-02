<?php

namespace App\Models;

/**
 * App\Models\OtcPublishBuy
 *
 * @property int                                                                  $id
 * @property int                                                                  $uid
 * @property mixed                                                                $amount 总购买数
 * @property int                                                                  $amount_min 最小购买数
 * @property int                                                                  $amount_max 最大购买数
 * @property mixed                                                                $amount_lost 剩余部分
 * @property mixed                                                                $price 单价
 * @property int                                                                  $pay_wechat 微信支付
 * @property int                                                                  $pay_alipay 支付宝
 * @property int                                                                  $pay_bank 银行卡
 * @property int                                                                  $coin_id 币种ID
 * @property int                                                                  $is_over 是否已完成 0 未完成 1 已完成 2 已取消
 * @property int                                                                  $currency 币种 0 人民币 1 美元
 * @property string                                                               $remark 备注
 * @property int|null                                                             $created_at
 * @property int|null                                                             $updated_at
 * @property-read float                                                           $amount_success
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OtcOrder[] $order
 * @property-read \App\Models\User                                                $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereCoinId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereIsOver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy wherePayAlipay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy wherePayBank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy wherePayWechat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishBuy whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read mixed $coin_name
 */
class OtcPublishBuy extends Model
{
    protected $table = 'otc_publish_buy';
    protected $fillable = ['uid', 'amount', 'amount_min', 'amount_max', 'amount_lost', 'pay_wechat', 'pay_alipay', 'pay_bank', 'is_over', 'price', 'currency', 'remark', 'coin_id'];
    protected $casts = [
        'created_at'     => 'timestamp',
        'updated_at'     => 'timestamp',
        'amount_min'     => 'decimal:2',
        'amount_max'     => 'decimal:2',
        'amount'         => 'decimal:2',
        'amount_lost'    => 'decimal:2',
        'amount_success' => 'decimal:2',
        'price'          => 'decimal:2',
    ];

    protected $appends = ['amount_success', 'coin_name'];

    const IS_OVER_NOT = 0;
    const IS_OVER_YES = 1;
    const IS_OVER_CANCEL = 2;

    const CUR_CNY = 0;
    const CUR_USD = 1;

    public function getCoinNameAttribute()
    {
        return Coin::find($this->coin_id)->name;
    }

    public function order()
    {
        return $this->hasMany('App\Models\OtcOrder', 'buy_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'uid', 'id');
    }

    /**
     * @return float
     */
    public function getAmountSuccessAttribute()
    {
        return bcsub($this->amount, $this->amount_lost, 2);
    }
}

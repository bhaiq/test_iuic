<?php

namespace App\Models;

/**
 * App\Models\OtcPublishSell
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereCoinId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereIsOver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell wherePayAlipay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell wherePayBank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell wherePayWechat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcPublishSell whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read mixed $coin_name
 */
class OtcPublishSell extends Model
{
    protected $table = 'otc_publish_sell';
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
        return $this->hasMany('App\Models\OtcOrder', 'sell_id', 'id');
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

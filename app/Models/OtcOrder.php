<?php

namespace App\Models;

/**
 * App\Models\OtcOrder
 *
 * @property int                             $id
 * @property int                             $sell_id
 * @property int                             $buy_id
 * @property mixed                           $amount 交易数量
 * @property mixed                           $price 单价
 * @property mixed                           $total_price 总价
 * @property int                             $status 状态 0 进行中 1 已完成 2 取消
 * @property int                             $is_pay 是否已支付
 * @property int                             $is_pay_coin 是否已发币
 * @property int                             $appeal_uid 申诉人id
 * @property int                             $uid 下单人id
 * @property int                             $type 交易类型 0出售,1购买
 * @property int                             $coin_id 交易币ID
 * @property string                          $expansion
 * @property int|null                        $created_at
 * @property int|null                        $updated_at
 * @property-read mixed                      $is_appeal
 * @property-read \App\Models\OtcPublishBuy  $otcPublishBuy
 * @property-read \App\Models\OtcPublishSell $otcPublishSell
 * @property-read \App\Models\User           $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereAppealUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereBuyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereCoinId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereExpansion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereIsPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereIsPayCoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereSellId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\User           $buyer
 * @property-read \App\Models\User           $seller
 * @property int                             $seller_id 出售人
 * @property int                             $buyer_id 购买人
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OtcOrder whereSellerId($value)
 * @property-read mixed $coin_name
 */
class OtcOrder extends Model
{
    protected $table = 'otc_order';
    protected $fillable = ['sell_id', 'buy_id', 'amount', 'price', 'total_price', 'uid', 'type', 'coin_id', 'seller_id', 'buyer_id'];
    protected $appends = ['is_appeal', 'coin_name'];
    protected $casts = [
        'created_at'  => 'timestamp',
        'updated_at'  => 'timestamp',
        'amount'      => 'decimal:2',
        'price'       => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
    const TYPE_SELL = 0;
    const TYPE_BUY = 1;

    const STATUS_INIT = 0;
    const STATUS_OVER = 1;
    const STATUS_CANCEL = 2;

    public function getCoinNameAttribute()
    {
        return Coin::find($this->coin_id)->name;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'uid', 'id');
    }

    public function seller()
    {
        return $this->belongsTo('App\Models\User', 'seller_id', 'id');
    }

    public function buyer()
    {
        return $this->belongsTo('App\Models\User', 'buyer_id', 'id');
    }

    public function getIsAppealAttribute()
    {
        return boolval($this->appeal_uid);
    }

    public function otcPublishSell()
    {
        return $this->belongsTo('App\Models\OtcPublishSell', 'sell_id', 'id');
    }

    public function otcPublishBuy()
    {
        return $this->belongsTo('App\Models\OtcPublishBuy', 'buy_id', 'id');
    }
}

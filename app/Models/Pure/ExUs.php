<?php

namespace App\Models\Pure;

/**
 * App\Models\Pure\ExUs
 *
 * @property int        $id
 * @property int        $uid 用户ID
 * @property int        $team_id 交易对ID
 * @property float      $price 单价
 * @property mixed      $amount 挂单数量
 * @property float      $amount_lost 剩余数量
 * @property float      $amount_deal 花费数量
 * @property int        $type 交易类型：0 卖，1 买
 * @property int        $status 状态：0 未完成，1 已完成，2取消
 * @property int|null   $created_at
 * @property int|null   $updated_at
 * @property-read mixed $lost_us
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereAmountDeal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pure\ExUs whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExUs extends Model
{
    protected $table = 'ex_us_legal';
    protected $fillable = ['uid', 'team_id', 'price', 'amount', 'deal_us', 'amount_lost', 'status', 'type'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'amount'     => 'decimal:2',
    ];
    protected $appends = ['lost_us'];
    const STATUS_INIT = 0;
    const STATUS_FINISHED = 1;
    const STATUS_DEL = 2;
    const TYPE_SELL = 0;
    const TYPE_BUY = 1;

    public function getLostUsAttribute()
    {
        return bcsub(bcmul($this->amount, $this->price, 8), $this->amount_deal, 8);
    }

}

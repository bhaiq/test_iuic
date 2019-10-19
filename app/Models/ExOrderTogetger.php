<?php

namespace App\Models;

/**
 * App\Models\ExOrderTogetger
 *
 * @property int      $id
 * @property int      $team_id 交易对ID
 * @property int      $sell_id 售卖表ID
 * @property int      $buy_id 购买表ID
 * @property int      $seller_id 售卖人ID
 * @property int      $buyer_id 购买人ID
 * @property mixed    $success_amount 成交数量
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereBuyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereSellId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereSuccessAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property float    $price 成交价格
 * @property int      $type 成交方向 1 买单匹配 0 卖单匹配
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrderTogetger whereType($value)
 */
class ExOrderTogetger extends Model
{
    protected $table = 'ex_order_together';
    protected $fillable = ['team_id', 'sell_id', 'buy_id', 'seller_id', 'buyer_id', 'success_amount', 'price', 'type'];
    protected $casts = [
        'created_at'     => 'timestamp',
        'updated_at'     => 'timestamp',
        'success_amount' => 'decimal:2',
    ];
    protected $hidden = [
        'sell_id', 'buy_id', 'seller_id', 'buyer_id'
    ];

    /**
     * @param int $team_id
     * @param int $time
     * @return array
     */
    public static function historyPrice(int $team_id, $time = 2)
    {
        return Redis::get(Redis::KEY_EX_HIST0RY_PRICE, $team_id, $time, true);
    }
}

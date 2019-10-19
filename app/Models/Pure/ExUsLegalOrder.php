<?php

namespace App\Models\Pure;

/**
 * App\Models\ExUsLegalOrder
 *
 * @property int                             $id
 * @property int                             $team_id 交易对ID
 * @property int                             $sell_id 售卖表ID
 * @property int                             $buy_id 购买表ID
 * @property int                             $seller_id 售卖人ID
 * @property int                             $buyer_id 购买人ID
 * @property float                           $success_amount 成交数量
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereBuyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereSellId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereSuccessAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExUsLegalOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExUsLegalOrder extends Model
{
    protected $table = 'ex_us_legal_order';
    protected $fillable = ['team_id', 'sell_id', 'buy_id', 'seller_id', 'buyer_id', 'success_amount'];
    protected $casts = [
        'created_at'     => 'timestamp',
        'updated_at'     => 'timestamp',
    ];
}

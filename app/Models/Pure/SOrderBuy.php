<?php

namespace App\Models\Pure;

use Illuminate\Support\Facades\DB;

/**
 * App\Models\SOrderBuy
 *
 * @property int      $id
 * @property int      $uid 买s人ID
 * @property float    $amount 挂卖数量
 * @property float    $amount_done 完成的数量
 * @property float    $amount_lost 剩下的数量
 * @property int      $is_over 0 未完成；1 已完成
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereAmountDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereIsOver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderBuy whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SOrderBuy extends Model
{
    protected $table = 's_order_buy';
    protected $fillable = ['amount', 'uid', 'amount_lost'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];


    public static function mathLostAmount($amount, $id)
    {
        DB::update('UPDATE s_order_buy SET amount_lost=' . $amount . ' WHERE id=?', [$id]);
    }

}

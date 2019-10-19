<?php

namespace App\Models\Pure;

use Illuminate\Support\Facades\DB;

/**
 * App\Models\SOrderSell
 *
 * @property int      $id
 * @property int      $uid 卖s人ID
 * @property float    $amount 挂卖数量
 * @property float    $amount_done 完成的数量
 * @property float    $amount_lost 剩下的数量
 * @property int      $is_over 0 未完成；1 已完成
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereAmountDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereIsOver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderSell whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SOrderSell extends Model
{
    protected $table = 's_order_sell';
    protected $fillable = ['uid', 'amount', 'amount_done', 'amount_lost'];

    public static function mathLostAmount($amount, $id)
    {
        DB::update('UPDATE s_order_sell SET amount_lost=' . $amount . ' WHERE id=?', [$id]);
    }

}

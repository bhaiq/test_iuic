<?php

namespace App\Models\Pure;


/**
 * App\Models\SOrderLog
 *
 * @property int      $id
 * @property int      $sell_id 售卖表id
 * @property int      $buy_id 购买表id
 * @property float    $amount
 * @property string   $expansion
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereBuyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereExpansion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereSellId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SOrderLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SOrderLog extends Model
{
    protected $table = 's_order_log';
    protected $fillable = ['sell_id', 'buy_id', 'amount', 'expansion'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'expansion'  => 'array',
    ];
}

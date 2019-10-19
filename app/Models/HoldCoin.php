<?php

namespace App\Models;

/**
 * App\Models\HoldCoin
 *
 * @property int $uid
 * @property float $amount 持币数量
 * @property float $price 持币价格
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HoldCoin whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HoldCoin extends Model
{
    protected $table='hold_coin';
    protected $fillable = ['uid','amount','price'];
    protected $primaryKey='uid';
}

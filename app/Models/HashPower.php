<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\HashPower
 *
 * @property int                             $id
 * @property int                             $uid
 * @property float                           $amount 当前输入算力
 * @property float                           $total 当前总算力
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int                             $smelt_log_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HashPower whereSmeltLogId($value)
 */
class HashPower extends Model
{
    protected $table = 'hash_power';
    protected $fillable = ['uid', 'amount', 'total', 'smelt_log_id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

}

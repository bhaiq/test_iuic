<?php

namespace App\Models;

/**
 * App\Models\Wallet
 *
 * @property int                             $id
 * @property int                             $uid 用户ID
 * @property int                             $type 基于区块类型:1 ERC20;2 omini ;3 cosmos;50 aitc.9;
 * @property string                          $address 地址
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wallet whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Wallet extends Model
{
    protected $table = 'wallet';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    protected $fillable = ['uid', 'address', 'type'];

    const TYPE_ETH = 1;
    const TYPE_OMINI = 2;
    const TYPE_COSMOS = 3;

    const TYPE_AITC9 = 50;
}

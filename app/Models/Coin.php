<?php

namespace App\Models;

use App\Constants\HttpConstant;

/**
 * App\Models\Coin
 *
 * @property int                             $id
 * @property string                          $name 币名
 * @property int                             $coin_type 基于区块类型:1 ERC20;2 omini ;3 cosmos
 * @property int                             $is_legal 是否为法币:0 否；1 是
 * @property int                             $status 状态：0正常，1 维护中，2关闭
 * @property string                          $contract 合约
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereCoinType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereContract($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereIsLegal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null                     $coin_types 币信息
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coin whereCoinTypes($value)
 */
class Coin extends Model
{
    protected $table = 'coin';
    protected $casts = [
        'coin_types' => 'array'
    ];

    /**
     * @param string $name
     * @return mixed|\App\Models\Coin
     */
    public static function getCoinByName(string $name)
    {
        return Redis::get(Redis::KEY_EX_COIN, strtoupper($name), 3600 * 24) ?? abort(HttpConstant::CODE_400_BAD_REQUEST, '非法操作');
    }

}

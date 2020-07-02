<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EthTransaction
 *
 * @property int                             $id
 * @property string                          $hash hash
 * @property string                          $block 区块高度
 * @property string                          $from 发送地址
 * @property string                          $to 接手地址
 * @property string                          $amount 接收数量
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereBlock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $coin_id 币种id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EthTransaction whereCoinId($value)
 */
class EthTransaction extends Model
{
    protected $table = 'eth_transaction';
    protected $fillable = ['hash', 'block', 'from', 'to', 'amount', 'coin_id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}

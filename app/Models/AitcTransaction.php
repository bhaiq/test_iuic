<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AitcTransaction
 *
 * @property int $unit 交易唯一值
 * @property string $action 交易类型，invalid 无效交易, received 接受, moved 内部转移, sent 发送
 * @property float $amount 数量
 * @property string $my_address 本钱包地址
 * @property string $address_to 接受地址
 * @property string $extends
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereAddressTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereExtends($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereMyAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AitcTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AitcTransaction extends Model
{
    protected $table = 'aitc_transaction';
    protected $fillable = ['unit', 'action', 'amount', 'my_address', 'address_to', 'extends'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];


}

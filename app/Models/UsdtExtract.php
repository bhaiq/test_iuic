<?php

namespace App\Models;

/**
 * App\Models\UsdtExtract
 *
 * @property int         $id
 * @property int         $uid
 * @property int         $type 类型 1 usdt
 * @property int         $handle_type 操作类型 0 未操作 1 手动 2 自动
 * @property float       $amount 数量
 * @property string      $address 提币地址
 * @property float       $charge 手续费
 * @property int         $state 类型 0 审核中 1进行中 2: 已完成 3: 已退回
 * @property int         $status 类型 0 未操作 1: 打包中 2: 已完成 3: 失败
 * @property string|null $transaction_message 区块交易信息
 * @property string|null $remark 说明
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereHandleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereTransactionMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $extend 拓展字段
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtExtract whereExtend($value)
 */
class UsdtExtract extends Model
{
    protected $table = 'usdt_extract';
    protected $fillable = ['uid', 'amount', 'state', 'status', 'type', 'handle_type', 'address', 'charge',
        'transaction_message', 'remark'];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'extend'     => 'array'
    ];

}

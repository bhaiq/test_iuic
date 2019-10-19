<?php

namespace App\Models;

/**
 * App\Models\UsdtPusher
 *
 * @property int                             $id
 * @property int                             $type usdt充值推送类型
 * @property int                             $status 推送状态
 * @property string                          $content 推送内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UsdtPusher whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UsdtPusher extends Model
{
    protected $table = 'usdt_pusher';
    protected $fillable = ['type', 'status', 'content'];
    protected $casts = [
        'content'    => 'array',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

}

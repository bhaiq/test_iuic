<?php

namespace App\Models;

/**
 * App\Models\FAQ
 *
 * @property int                             $id
 * @property string                          $title 标题
 * @property string|null                     $remark 说明
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $index 排序
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FAQ whereIndex($value)
 */
class FAQ extends Model
{
    protected $table = 'faq';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}

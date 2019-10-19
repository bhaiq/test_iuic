<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\ExtractTip
 *
 * @property int                             $id
 * @property float                           $day_total 当日统计
 * @property float                           $total 统计全部
 * @property int                             $type 类型：0 usdt
 * @property int                             $date 日期
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereDayTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExtractTip whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExtractTip extends Model
{
    protected $table = 'extract_tip';
    protected $fillable = ['day_total', 'total', 'date', 'type'];
    const TYPE_USDT = 0;

    public static function getDay()
    {
        $dt = Carbon::now();
        return $dt->year . $dt->month . $dt->day;
    }
}

<?php

namespace App\Models;

/**
 * App\Models\Version
 *
 * @property int                             $id
 * @property string                          $current_version
 * @property int                             $is_force
 * @property int                             $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereCurrentVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereIsForce($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string                          $url
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereUrl($value)
 * @property string|null                     $remark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Version whereRemark($value)
 */
class Version extends Model
{
    protected $table = 'version';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function getUrlAttribute($val)
    {
        if ($this->type == 1) {
//            return 'itms-services://?action=download-manifest&url=' . $val;
            return $val;
        }
        return $val;
    }

    public function getRemarkAttribute($val)
    {
        $val = json_decode($val, true);
        if (request()->header('locale', 'zh-CN') == 'en') return $val['en'];
        return $val['zh'];
    }
}

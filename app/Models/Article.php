<?php

namespace App\Models;

/**
 * App\Models\Article
 *
 * @property int      $id
 * @property int      $type 类型
 * @property string   $url 来源
 * @property string   $thumbnail 缩略图
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereThumbnail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereUrl($value)
 * @mixin \Eloquent
 * @property string   $title 标题
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereTitle($value)
 */
class Article extends Model
{
    protected $table = 'article';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function getUrlAttribute($val)
    {
        $val = json_decode($val, true);
        if (request()->header('locale', 'zh-CN') == 'en') return $val['en'];
        return $val['zh'];
    }
}

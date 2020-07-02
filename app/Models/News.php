<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\News
 *
 * @property int         $id
 * @property string      $title 标题
 * @property string      $thumbnail 标题图
 * @property string|null $content 内容
 * @property string      $type_name 分类名称
 * @property int         $type 分类 0 新闻 1 公告
 * @property int         $language 语言 0 中文 1英文
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereThumbnail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\News whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class News extends Model
{
    protected $table = 'news';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    const TYPE_NEW = 0;
    const TYPE_PUB = 1;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('language', function (Builder $builder) {
            $builder->where('language', self::getLanguage());
        });
    }

    public static function getLanguage()
    {
        switch (request()->header('locale', 'zh-CN')) {
            case 'zh-CN':
                return 0;
            case 'en':
                return 1;
            default:
                return 0;
        }
    }
}

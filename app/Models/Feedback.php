<?php

namespace App\Models;

/**
 * App\Models\Feedback
 *
 * @property int                                                                         $id
 * @property int                                                                         $uid 用户ID
 * @property string                                                                      $title 标题
 * @property string|null                                                                 $description 描述
 * @property int                                                                         $status 状态：0未解决，1已解决
 * @property array|null                                                                  $img 图片
 * @property int|null                                                                    $created_at
 * @property int|null                                                                    $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FeedbackComment[] $comment
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Feedback whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Feedback extends Model
{
    protected $table = 'feedback';
    protected $fillable = ['uid', 'title', 'description', 'status', 'img'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'img'        => 'array',
    ];

    const STATUS_STATUS_ON = 0;

    public function comment()
    {
        return $this->hasMany('App\Models\FeedbackComment', 'feedback_id', 'id')->orderBy('id');
    }
}

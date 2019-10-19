<?php

namespace App\Models;

/**
 * App\Models\FeedbackComment
 *
 * @property int                             $id
 * @property int                             $feedback_id 反馈ID
 * @property int                             $uid 用户ID
 * @property int                             $service_id 用户ID
 * @property int                             $type 类型：0 用户，1 客服
 * @property string|null                     $description 描述
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereFeedbackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedbackComment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FeedbackComment extends Model
{
    protected $table = 'feedback_comment';
    protected $fillable = ['feedback_id', 'uid', 'service_id', 'type', 'description'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    const TYPE_CUS = 0;
    const TYPE_SERVE = 1;
}

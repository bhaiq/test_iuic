<?php

namespace App\Models;

/**
 * App\Models\UserStat
 *
 * @property int                             $uid
 * @property int                             $invite_total 总邀请
 * @property int                             $invite_today 当天邀请
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat whereInviteToday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat whereInviteTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserStat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserStat extends Model
{
    protected $table = 'user_stat';
    protected $primaryKey = 'uid';
    protected $fillable = ['uid', 'invite_total', 'invite_today'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}

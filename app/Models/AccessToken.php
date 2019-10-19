<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccessToken
 *
 * @property int                             $id
 * @property string                          $token
 * @property int                             $uid 用户id
 * @property string                          $uuid 设备ID
 * @property ip                              $ip 登录时ip
 * @property int                             $type 设备类型 1 iOS 2 Android
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereUuid($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessToken whereIp($value)
 */
class AccessToken extends Model
{
    protected $table = 'access_token';
    protected $fillable = ['token', 'uid', 'uuid', 'type', 'ip'];
}

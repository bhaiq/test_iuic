<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Authentication
 *
 * @property int                             $uid
 * @property string                          $name 真实姓名
 * @property string                          $number 身份证号
 * @property string                          $img_front 身份正面照
 * @property string                          $img_back 身份反面照
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereImgBack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereImgFront($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Authentication whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Authentication extends Model
{
    protected $table = 'authentication';
    protected $primaryKey = 'uid';
    protected $fillable = ['uid', 'name', 'number', 'img_front', 'img_back'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}

<?php

namespace App\Models\Admin\RBAC;

use App\Models\Model;

/**
 * App\Models\Admin\RBAC\Access
 *
 * @property int                             $id
 * @property string                          $title 权限名称
 * @property string                          $uri uri
 * @property int                             $status 状态 1：有效 0：无效
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Access whereUri($value)
 * @mixin \Eloquent
 */
class Access extends Model
{
    protected $table = 'access';
    protected $fillable = ['title', 'uri'];
}

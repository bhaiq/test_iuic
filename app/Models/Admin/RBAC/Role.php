<?php

namespace App\Models\Admin\RBAC;

use App\Models\Model;

/**
 * App\Models\Admin\RBAC\Role
 *
 * @property int                             $id
 * @property string                          $name 角色名
 * @property int                             $status 状态 1：有效 0：无效
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin\RBAC\Access[] $access
 */
class Role extends Model
{
    protected $table = 'role';
    protected $fillable = ['name'];

    public function access()
    {
        return $this->belongsToMany('App\Models\Admin\RBAC\Access');
    }
}

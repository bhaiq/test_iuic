<?php

namespace App\Models\Admin\RBAC;

use App\Models\Model;

/**
 * App\Models\Admin\RBAC\RoleAccess
 *
 * @property int                             $id
 * @property int                             $role_id 角色ID
 * @property int                             $access_id 权限ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess whereAccessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin\RBAC\RoleAccess whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RoleAccess extends Model
{
    protected $table = 'role_access';
}

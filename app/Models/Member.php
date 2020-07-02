<?php

namespace App\Models;

/**
 * App\Models\Member
 *
 * @property int                             $id
 * @property string                          $name
 * @property string                          $email
 * @property string|null                     $email_verified_at
 * @property string                          $password
 * @property string|null                     $remember_token
 * @property int|null                        $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin\RBAC\Access[] $access
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Member whereRoleId($value)
 */
class Member extends Model
{
    protected $table = 'users';
    protected $fillable = ['email', 'name', 'password'];
    const ROLE_SUPER = 127;

    public function access()
    {
        return $this->belongsToMany('App\Models\Admin\RBAC\Access', 'role_access', 'role_id', 'access_id', 'role_id', 'id');
    }
}

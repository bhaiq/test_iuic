<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/22
 * Time: 9:40
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraBonus extends Model
{

    protected $table = 'extra_bonus';

    protected $guarded = [];

    public function getUsersAttribute($value)
    {
        return explode(',', $value);
    }

}
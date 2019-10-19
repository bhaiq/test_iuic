<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 15:58
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBonus extends Model
{

    protected $table = 'user_bonus';

    protected $guarded = [];

//    public function scopeBouns($query)
//    {
//        return $query->where('type', '=', 1);
//    }
//
//    public function scopeAdmin($query)
//    {
//        return $query->where('type', '=', 2);
//    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2020/1/19
 * Time: 18:19
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeniorAdmin extends Model
{

    protected $table = 'senior_admin';

    protected $guarded = [];

    // 获取手下某一个级别的按线算的数量
    public static function getUserLineCount($uid, $level)
    {

        // 获取用户下级的ID
        $lowerUser = User::where('pid', $uid)->get();
        if($lowerUser->isEmpty()){
            return 0;
        }

        $result = 0;
        foreach ($lowerUser as $v){

            // 获取用户下级的所有用户ID
            $lowers = User::where('pid_path', 'like', '%,' . $v->id . ',%')->orwhere('id', $v->id)->pluck('id')->toArray();

            // 获取这条线有没有满足级别的
            if(SeniorAdmin::whereIn('uid', $lowers)->where('type', $level)->exists()){
                $result++;
            }

        }

        return $result;

    }

}
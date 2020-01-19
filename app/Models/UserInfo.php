<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 14:13
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{

    protected $table = 'user_info';

    protected $guarded = [];

    const LEVEL = [
        0 => '无',
        1 => '普通',
        2 => '高级'
    ];

    public function scopeGeneral($query)
    {
        return $query->where('level', '=', 1);
    }

    public function scopeSenior($query)
    {
        return $query->where('level', '=', 2);
    }

    public function scopeValid($query)
    {
        return $query->whereIn('level', [1, 2]);
    }

    // 矿池总量增加
    public static function addBuyTotal($uid, $num)
    {

        UserInfo::where('uid', $uid)->increment('buy_total', $num);

        \Log::info('ID为' . $uid . '的用户增加矿池总量'. $num);

        return true;

    }

    // 矿池释放总量增加
    public static function addReleaseTotal($uid, $num)
    {

        UserInfo::where('uid', $uid)->increment('release_total', $num);

        \Log::info('ID为' . $uid . '的用户增加矿池释放总量'. $num);

        return true;

    }

    // 验证用户是否报过单
    public static function checkUserValid($uid)
    {

        if(User::where('id', $uid)->whereIn('level', [1, 2])->exists()){
            return 1;
        }

        return 0;

    }


}
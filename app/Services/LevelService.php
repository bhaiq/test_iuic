<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/10/7
 * Time: 11:44
 */

namespace App\Services;

use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserInfo;

class LevelService
{

    // 判断用户是否满足管理奖条件
    public static function checkAdmin($uid)
    {

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return 0;
        }

        // 判断当前管理分红权限有没有超过500
        $userSum = UserBonus::where('type', 2)->count();
        if($userSum >= config('shop.admin_bonus_num')){
            \Log::info('分红数量超过限制数量');
            return 0;
        }

        // 判断当前用户是否是有效用户
        $userInfo = UserInfo::where('uid', $uid)->where('level', '!=', 0)->first();
        if(!$userInfo){
            \Log::info('用户不是有效用户');
            return 0;
        }

        // 判断推荐的用户是否有5个高级
        $count5 = UserInfo::senior()->where('pid', $uid)->count();
        if($count5 < 5){
            \Log::info('用户高级用户数量未达到', ['uid' => $uid, 'count' => $count5]);
            return 0;
        }

        // 判断推荐的用户部门是否达到300
        $count300 = UserInfo::valid()->where('pid_path', 'like', '%,' . $uid .',%')->count();
        if($count300 < 300){
            \Log::info('用户推荐的有效用户数量未达到', ['uid' => $uid, 'count' => $count300]);
            return 0;
        }

        // 判断某个部门是否达到100
        $ulRes = UserInfo::senior()->where('pid', $uid)->get(['uid']);
        if($ulRes->isEmpty()){
            \Log::info('用户部门数未达到', ['uid' => $uid]);
            return 0;
        }

        $status = false;
        foreach ($ulRes->toArray() as $v){

            $lowCount = UserInfo::valid()->where('pid_path', 'like', '%,' . $v['uid'] .',%')->count();
            if($lowCount >= 100){
                $status = true;
                break;
            }

        }

        if(!$status){
            \Log::info('用户部门用户数未达到100', ['uid' => $uid]);
            return 0;
        }

        return 1;

    }

    // 判断用户是否满足节点奖条件
    public static function checkNode($uid, $type = 0)
    {

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return 0;
        }

        // 判断当前用户是否报过单
        $userInfo = UserInfo::where('uid', $uid)->where('level', '!=', 0)->first();
        if(!$userInfo){
            \Log::info('用户未报过单');
            return 0;
        }

        // 判断节点奖类型
        switch ($type){

            // 普通节点奖
            case 0:

                $validNum = 0;
                $branchNum = 0;
                break;

            // 小节点奖
            case 1:

                $validNum = 30;
                $branchNum = 10;
                break;

            // 大节点奖
            case 2:

                $validNum = 60;
                $branchNum = 20;
                break;

            // 超级节点奖
            case 3:

                $validNum = 120;
                $branchNum = 40;
                break;

            default:

                \Log::info('传过的节点奖数据类型有误!', ['type' => $type]);
                return 0;
                break;

        }

        // 判断推荐的用户是否有5个高级
        $count5 = UserInfo::senior()->where('pid', $uid)->count();
        if($count5 < 5){
            \Log::info('用户高级用户数量未达到', ['uid' => $uid, 'count' => $count5]);
            return 0;
        }

        // 判断当前用户推荐的有效用户是否达到指定数量
        $count300 = UserInfo::valid()->where('pid_path', 'like', '%,' . $uid .',%')->count();
        if($count300 < $validNum){
            \Log::info('用户推荐的有效用户数量未达到', ['uid' => $uid, 'count' => $count300]);
            return 0;
        }

        // 判断某个部门是否达到指定数量
        $ulRes = UserInfo::senior()->where('pid', $uid)->get(['uid']);
        if($ulRes->isEmpty()){
            \Log::info('用户部门数未达到', ['uid' => $uid]);
            return 0;
        }

        $status = false;
        foreach ($ulRes->toArray() as $v){

            $lowCount = UserInfo::valid()->where('pid_path', 'like', '%,' . $v['uid'] .',%')->count();
            if($lowCount >= $branchNum){
                $status = true;
                break;
            }

        }

        if(!$status){
            \Log::info('用户部门用户数未达到' . $branchNum, ['uid' => $uid, 'type' => $type]);
            return 0;
        }

        return 1;

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/9
 * Time: 11:33
 */

namespace App\Http\Controllers;

use App\Models\EnergyOrder;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\UserWallet;
use App\Services\Service;
use Illuminate\Http\Request;

class RecommendController extends Controller
{

    // 个人推荐信息
    public function info()
    {

        Service::auth()->isLoginOrFail();

        $res = $this->getUserInfo(Service::auth()->getUser()->id);
        if(!$res){
            $this->responseError('数据有误');
        }

        if(Service::auth()->getUser()->id == 1){

            $arr = [
                'pt_user_count' => UserInfo::where('level', 1)->count(),
                'gj_user_count' => UserInfo::where('level', 2)->count(),
            ];

            $res = array_merge($res, $arr);
        }

        return $this->response($res);

    }


    // 获取用户推荐列表
    public function list(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result  = [];
        // 获取用户的推荐用户
        $user = User::where('pid', Service::auth()->getUser()->id)->paginate($request->get('per_page', 10));

        foreach ($user as $k => $v){

            $res = $this->getUserInfo($v->id);

            if($res){
                $result[] = $res;
            }
        }

        return $this->response($result);

    }


    // 获取用户推荐信息
    private function getUserInfo($uid)
    {

         $user = User::with('user_info')->where('id', $uid)->first();
         if(!$user){
            return false;
         }

         if(!$user->user_info){
            return [
                'avatar' => $user->avatar,
                'nickname' => $user->nickname,
                'level' => 0,
                'level_name' => '无',
                'is_auth' => $user->is_auth,
                'branch_count' => $this->getBranchCount($uid),
                'recommend_count' => $this->getRecommendCount($uid),
                'is_bonus' => 0,
                'is_admin' => 0,
                'branch_total_count' => $this->getBranchRecommendCount($uid),
                'energy_total_count' => $this->getEnergyTotalCount($uid),
                'energy_recommend_count' => $this->getgetEnergyRecommendCount($uid),
                'energy_today_count' => $this->getEnergyTodayCount($uid),
            ];
         }


         return [
             'avatar' => $user->avatar,
             'nickname' => $user->nickname,
             'level' => $user->user_info->level ?? 0,
             'level_name' => UserInfo::LEVEL[$user->user_info->level] ?? '空',
             'is_auth' => $user->is_auth,
             'branch_count' => $this->getBranchCount($uid),
             'recommend_count' => $this->getRecommendCount($uid),
             'is_bonus' => $user->user_info->is_bonus,
             'is_admin' => $user->user_info->is_admin,
             'branch_total_count' => $this->getBranchRecommendCount($uid),
             'energy_total_count' => $this->getEnergyTotalCount($uid),
             'energy_recommend_count' => $this->getgetEnergyRecommendCount($uid),
             'energy_today_count' => $this->getEnergyTodayCount($uid),
         ];

    }

    // 获取IUIC报单有效人数
    private function getBranchCount($uid)
    {
        return UserInfo::whereIn('level', [1, 2])->where('pid_path', 'like', '%,' . $uid . ',%')->count();
    }

    // 获取部门总人数
    private function getBranchRecommendCount($uid)
    {
        return User::where('pid_path', 'like', '%,' . $uid . ',%')->count();
    }

    // IUIC直接分享人数
    private function getRecommendCount($uid)
    {
        return User::where('pid', $uid)->count();
    }

    // 能量报单部门有效人数
    private function getEnergyTotalCount($uid)
    {

        // 获取用户部门的所有用户ID
        $lowerIds = User::where('pid_path', 'like', '%,' . $uid . ',%')->pluck('id')->toArray();

        return UserWallet::whereIn('uid', $lowerIds)->count();

    }

    // 能量报单直推有效人数
    private function getgetEnergyRecommendCount($uid)
    {

        // 获取用户手下的所有用户ID
        $lowerIds = User::where('pid', $uid)->pluck('id')->toArray();

        return UserWallet::whereIn('uid', $lowerIds)->count();

    }

    // 能量报单当日新增有效
    private function getEnergyTodayCount($uid)
    {

        // 获取用户部门的所有用户ID
        $lowerIds = User::where('pid_path', 'like', '%,' . $uid . ',%')->pluck('id')->toArray();

        return UserWallet::whereIn('uid', $lowerIds)->whereDate('created_at', now()->toDateString())->count();

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2020/1/19
 * Time: 17:58
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Coin;
use App\Models\SeniorAdmin;
use App\Models\User;
use App\Models\UserInfo;
use App\Services\Service;;
use Illuminate\Http\Request;

class SeniorAdminController extends Controller
{

    // 高级管理奖页面
    public function start(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = [
            'tj_iuic_num' => config('senior_admin.senior_admin_num'),
            'tj_senior_num' => config('senior_admin.senior_admin_lower_user_count'),
            'cur_level' => UserInfo::LEVEL[0],
        ];

        // 获取用户信息
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if($ui){
            $result['cur_level'] = UserInfo::LEVEL[$ui->level];
        }

        // 获取用户直推的用户ID
        $lowUsers = User::where('pid', Service::auth()->getUser()->id)->pluck('id')->toArray();

        // 获取用户直推高级用户的数量
        $result['cur_senior_num'] = UserInfo::whereIn('uid', $lowUsers)->where('level', 2)->count();

        return $this->response($result);
    }

    // 申请高级管理奖
    public function submit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'paypass' => 'required',
        ], [
            'paypass.required' => '交易密码不能为空',
        ]);

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户之前是否有提交
        if(SeniorAdmin::where(['uid' => Service::auth()->getUser()->id])->whereIn('status', [0, 1])->exists()){
            $this->responseError('数据有误');
        }

        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        // 判断用户是否是高级
        if(!$ui || $ui->level != 2){
            $this->responseError('用户级别不是高级');
        }

        // 获取人数条件
        $userCount = config('senior_admin.senior_admin_lower_user_count', 5);
        if($userCount > 0){

            // 获取用户直推高级用户的数量
            $count = $this->getLineUndexLevelCount(Service::auth()->getUser()->id);

            if($count < $userCount){
                $this->responseError('分享的高级用户数不足');
            }

        }

        // 获取金额条件
        $num = config('senior_admin.senior_admin_num', 5000);

        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if($coinAccount->amount < $num){
            $this->responseError('用户余额不足');
        }

        $saData = [
            'uid' => Service::auth()->getUser()->id,
            'num' => $num,
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            // 生成订单
            SeniorAdmin::create($saData);

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $num);

            // 用户冻结余额增加
            Account::addFrozen(Service::auth()->getUser()->id, $coin->id, $num);

            // 用户状态改变
            User::where('id', Service::auth()->getUser()->id)->update(['is_senior_admin' => 2]);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('高级管理奖申请异常');

            $this->responseError('申请异常');

        }

        return $this->responseSuccess('操作成功');

    }

    // 获取用户线下高级用户条数
    private function getLineUndexLevelCount($uid)
    {

        // 获取用户手下信息
        $lowerUsers = User::where('pid', $uid)->get();
        if ($lowerUsers->isEmpty()) {
            return 0;
        }

        $result = 0;

        foreach ($lowerUsers as $v) {

            // 获取用户本身级别
            $ui = UserInfo::where('uid', $v->id)->first();
            if($ui && $ui->level == 2){
                $result++;
                continue;
            }

            // 获取手下是否存在高级的
            if(UserInfo::whereIn('pid_path', 'like', '%,' . $v->id . ',%')->where('level', 2)->exists()){
                $result++;
                continue;
            }

        }

        return $result;

    }

}
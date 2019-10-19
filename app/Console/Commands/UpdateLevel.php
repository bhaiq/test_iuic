<?php

namespace App\Console\Commands;

use App\Models\UserBonus;
use App\Models\UserInfo;
use App\Services\LevelService;
use Illuminate\Console\Command;

class UpdateLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateLevel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时更新用户级别';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        \Log::info('==========  定时处理用户级别问题  ==========');

        $this->toUpdateLevel();

        \Log::info('==========  处理用户级别问题完成  ==========');

    }

    private function toUpdateLevel()
    {

        // 获取所有矿池释放完成的用户
        $ui = UserInfo::whereRaw('buy_total <= release_total')->where('level', '!=', 0)->where('buy_total', '>', 0)->get();
        if($ui->isEmpty()){
            \Log::info('没有查到矿池释放完成的用户');
            return false;
        }

        foreach ($ui as $v){

            // 先降低用户级别
            $this->reduceLevel($v->uid);

            // 递归更新节点奖
            $this->toUpdateNode($v->uid);

            // 递归更新管理奖
            $this->toUpdateAdmin($v->uid);

        }

    }

    // 降低用户级别
    private function reduceLevel($uid)
    {

        \DB::beginTransaction();
        try {

            UserInfo::where('uid', $uid)->update(['level' => 0]);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('降低用户级别出现异常', ['uid' => $uid]);

        }

        return true;

    }

    // 更新用户节点奖信息
    private function toUpdateNode($uid)
    {

        $ui = UserInfo::where('uid', $uid)->first();
        if(!$ui){
            \Log::info('用户数据有误', ['uid' => $uid]);
            return false;
        }

        // 判断当前用户是否有节点奖
        if($ui->is_bonus != 1){
            \Log::info('用户没有节点奖,跳过', ['uid' => $uid]);
            return $this->toUpdateNode($ui->pid);
        }

        // 获取用户节点奖数据
        $ub = UserBonus::where(['uid' => $uid, 'type' => 1])->first();
        if(!$ub){
            \Log::info('没有查到用户的节点奖励信息,跳过', ['uid' => $uid]);
            return $this->toUpdateNode($ui->pid);
        }

        // 验证用户是否还满足当前的节点条件
        $lsRes = LevelService::checkNode($uid, $ub->node_type);
        if($lsRes){
            \Log::info('用户满足当前用户节点条件,跳过', ['uid' => $uid]);
            return $this->toUpdateNode($ui->pid);
        }

        \DB::beginTransaction();
        try {

            // 判断当前用户级别
            if($ub->node_type <= 0){

                // 当用户是节点奖时更改用户状态
                $uiData = [
                    'is_bonus' => 0,
                ];

                UserInfo::where('uid', $uid)->update($uiData);

                // 删除奖励表信息
                UserBonus::where(['uid' => $uid, 'type' => 1])->delete();

            }else{

                // 当用户是小节点奖或以上时降低级别
                $ub->node_type = $ub->node_type - 1;
                $ub->save();

            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('降低用户级别出现异常', ['uid' => $uid]);

            return false;

        }

        // 处理成功时再处理一遍
        \Log::info('处理用户节点奖降级成功', ['uid' => $uid]);
        return $this->toUpdateNode($uid);

    }

    // 更新用户管理奖信息
    private function toUpdateAdmin($uid)
    {

        $ui = UserInfo::where('uid', $uid)->first();
        if(!$ui){
            \Log::info('用户数据有误', ['uid' => $uid]);
            return false;
        }

        // 判断当前用户是否有管理奖
        if($ui->is_admin != 1){
            \Log::info('用户没有管理奖,跳过', ['uid' => $uid]);
            return $this->toUpdateAdmin($ui->pid);
        }

        // 获取用户管理奖数据
        $ub = UserBonus::where(['uid' => $uid, 'type' => 2])->first();
        if(!$ub){
            \Log::info('没有查到用户的管理奖励信息,跳过', ['uid' => $uid]);
            return $this->toUpdateAdmin($ui->pid);
        }

        // 验证用户是否还满足当前的管理条件
        $lsRes = LevelService::checkAdmin($uid);
        if($lsRes){
            \Log::info('用户满足当前用户管理条件,跳过', ['uid' => $uid]);
            return $this->toUpdateAdmin($ui->pid);
        }

        \DB::beginTransaction();
        try {

            // 当用户是节点奖时更改用户状态
            $uiData = [
                'is_admin' => 0,
            ];

            UserInfo::where('uid', $uid)->update($uiData);

            // 删除奖励表信息
            UserBonus::where(['uid' => $uid, 'type' => 2])->delete();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('降低用户管理奖级别出现异常', ['uid' => $uid]);

            return false;

        }

        // 处理成功时再处理一遍
        \Log::info('处理用户管理奖降级成功', ['uid' => $uid]);
        return $this->toUpdateAdmin($ui->pid);

    }

}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserInfo;
use App\Services\LevelService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateAdminBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;
    private $xxUid = [
        1, 34, 125, 126, 105
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        \Log::info('===== 用户递归更新用户节点奖 =====');

        $this->updateNode($this->uid);

        \Log::info('===== 用户递归更新用户节点奖 =====');

        \Log::info('===== 用户递归更新用户管理奖 =====');

        $this->updateAdmin($this->uid);

        \Log::info('===== 用户递归更新用户管理奖 =====');

    }

    // 更新用户节点奖信息
    private function updateNode($uid, $type = 0)
    {

        // 获取用户信息
        $user = User::with('user_info')->find($uid);
        if(!$user){
            \Log::info('用户数据不存在');
            return false;
        }

        // 判断用户是否属于非升级的用户
        if(in_array($user->id, $this->xxUid)){
            \Log::info('用户属于非升级用户，跳过', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        if(!$user->user_info){
            \Log::info('用户未报单', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        $ub = '';

        // 判断用户之前是否有节点奖
        if(isset($user->user_info->is_bonus) && $user->user_info->is_bonus == 1){

            // 获取用户节点奖信息
            $ub = UserBonus::where(['uid' => $uid, 'type' => 1])->first();
            if($ub){
                $type = bcadd($ub->node_type, 1);
            }

        }

        $lsRes = LevelService::checkNode($uid, $type);
        if(!$lsRes){
            \Log::info('用户不满足升级信息', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        \DB::beginTransaction();
        try {

            // 判断用户之前是否有节点奖
            if($ub){

                $ub->node_type = $type;
                $ub->save();

            }else{

                $ubData = [
                    'uid' => $uid,
                    'type' => 1,
                    'node_type' => $type,
                    'created_at' => now()->toDateTimeString(),
                ];

                UserBonus::create($ubData);

                // 附属表更新
                $ulData = [
                    'is_bonus' => 1,
                ];

                UserInfo::where('uid', $uid)->update($ulData);

            }


            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('处理节点更新信息异常', ['uid' => $uid, 'type' => $type]);

        }

        // 处理成功则再处理一遍
        return $this->updateNode($uid);

    }

    // 更新用户管理奖信息
    private function updateAdmin($uid)
    {

        // 获取用户信息
        $user = User::with('user_info')->find($uid);
        if(!$user){
            \Log::info('用户数据不存在');
            return false;
        }

        // 判断用户是否属于非升级的用户
        if(in_array($user->id, $this->xxUid)){
            \Log::info('用户属于非升级用户，跳过', ['uid' => $uid]);
            return $this->updateNode($user->pid);
        }

        if(!$user->user_info){
            \Log::info('用户未报单', ['uid' => $uid]);
            return $this->updateAdmin($user->pid);
        }

        // 判断用户之前是否有节点奖
        if(isset($user->user_info->is_admin) && $user->user_info->is_admin == 1){
            \Log::info('用户已经是管理奖用户了');
            return $this->updateAdmin($user->pid);
        }

        $lsRes = LevelService::checkAdmin($uid);
        if(!$lsRes){
            \Log::info('用户不满足升级信息', ['uid' => $uid]);
            return $this->updateAdmin($user->pid);
        }

        \DB::beginTransaction();
        try {

            $ubData = [
                'uid' => $uid,
                'type' => 2,
                'node_type' => 0,
                'created_at' => now()->toDateTimeString(),
            ];

            UserBonus::create($ubData);

            // 附属表更新
            $ulData = [
                'is_admin' => 1,
            ];

            UserInfo::where('uid', $uid)->update($ulData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('处理管理奖更新信息异常', ['uid' => $uid]);

        }

        // 处理成功则换用户上级
        return $this->updateAdmin($user->pid);

    }

}

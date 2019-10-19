<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateAdminBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;

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

        \Log::info('=====  用户递归更新管理分红权限  =====');

        $this->updateUserInfo($this->uid);

        \Log::info('=====  用户递归更新管理分红权限结束  =====');

    }

    // 递归更新用户信息
    public function updateUserInfo($uid)
    {
        if($uid <= 0){
            \Log::info('已经到达最顶层');
            return false;
        }

        // 判断当前管理分红权限有没有超过500
        $userSum = UserBonus::where('type', 2)->count();
        if($userSum >= config('shop.admin_bonus_num')){
            \Log::info('管理分红权限用户已达到500');
            return false;
        }

        // 判断当前用户是否报过单
        $user = UserInfo::where('uid', $uid)->first();
        if(!$user){

            $u = User::where('id', $uid)->first();
            if(!$u){
                \Log::info('用户数据不存在', ['uid' => $uid]);
                return false;
            }

            return $this->updateUserInfo($u->pid);
        }

        // 判断该用户是否有管理分红
        if($user->is_admin == 1){
            return $this->updateUserInfo($user->pid);
        }

        // 判断推荐的用户是否有5个高级
        $count5 = UserInfo::senior()->where('pid', $uid)->count();
        if($count5 < 5){
            return $this->updateUserInfo($user->pid);
        }

        // 判断推荐的用户部门是否达到300
        $count300 = UserInfo::where('pid_path', 'like', '%,' . $uid .',%')->count();
        if($count300 < 300){
            return $this->updateUserInfo($user->pid);
        }

        // 判断某个部门达到100
        $status = false;
        $ulRes = UserInfo::senior()->where('pid', $uid)->get(['uid']);
        if($ulRes->isEmpty()){
            return $this->updateUserInfo($user->pid);
        }

        foreach ($ulRes->toArray() as $v){

            $lowCount = UserInfo::where('pid_path', 'like', '%,' . $v['uid'] .',%')->count();
            if($lowCount >= 100){
                $status = true;
                break;
            }

        }

        if(!$status){
            return $this->updateUserInfo($user->pid);
        }

        $ubData = [
            'uid' => $uid,
            'type' => 2,
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 分红权限表更新
            UserBonus::create($ubData);
            \Log::info('新增一条管理分红权限', $ubData);

            // 用户附属表更新
            $user->is_admin = 1;
            $user->save();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('失败=====新增管理分红权限失败');

        }

        return $this->updateUserInfo($user->pid);
    }
}

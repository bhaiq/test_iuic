<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\EnergyOrder;
use App\Models\PledgeLevel;
use App\Models\User;
use App\Services\EnergyService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnergyDynamicRelease implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;
    private $num;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $num)
    {
        $this->uid = $uid;
        $this->num = $num;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        \Log::info('=-=-=-=-=-  进行动态释放操作  -=-=-=-=-=');

        // 获取用户信息
        $user = User::find($this->uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        $this->toRecommendReward($user->pid, $this->num, 1);

        $this->toCommunityReward($user->pid, $this->num);

        \Log::info('=-=-=-=-=-  结束动态释放操作  -=-=-=-=-=');

    }

    // 进行直推奖操作
    private function toRecommendReward($uid, $num, $layer)
    {

        \Log::info('进行代数奖进来的数据', ['uid' => $uid, 'num' => $num, 'layer' => $layer]);

        // 先判断层数是否超过3层
        if($layer > 2){
            \Log::info('已经进行到3代了，结束奖励');
            return false;
        }

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        // 获取用户推荐的有效用户数
        $eoCount = EnergyOrder::getEnergyValidNum($uid);
        if($eoCount < $layer){
            \Log::info('用户直推的有效用户数量没有达到层数,跳过', ['count' => $eoCount, 'layer' => $layer]);
            $layer++;
            return $this->toRecommendReward($user->pid, $num, $layer);
        }

        \DB::beginTransaction();
        try {

            // 获取用户本代能拿到的比例
            $bl = $this->getRecommendLayerBl($layer);
            $oneNum = bcmul($num, $bl, 8);
            if($oneNum > 0){
                EnergyService::orderSpeedRelease($uid, $oneNum, '代数奖加速', $this->uid);
            }else{
                \Log::info('代数奖层数奖励比例异常');
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('代数奖加速释放出现异常');

        }

        $layer++;
        return $this->toRecommendReward($user->pid, $this->num, $layer);

    }

    // 获取直推层数比例
    private function getRecommendLayerBl($layer)
    {

        switch ($layer){

            case 1:
                $num = config('energy.energy_recommend_1_reward_bl', 0.05);
                break;

            case 2:
                $num = config('energy.energy_recommend_2_reward_bl', 0.1);
                break;

            default:
                $num = 0;
                break;

        }

        return $num;

    }

    // 进行社区节点奖操作
    private function toCommunityReward($uid, $num, $oldBl = 0)
    {

        \Log::info('进行社区节点奖进来的数据', ['uid' => $uid, 'num' => $num, 'bl' => $oldBl]);
        if($oldBl >= config('energy.energy_community_50000_reward_bl', 0.05)){
            \Log::info('已经发放完成，结束发放');
            return false;
        }

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        // 获取用户本次加速的比例
        $bl = $this->getCommunityRewardBl($user->pledge_num);
        if($bl <= 0){
            \Log::info('用户持币数量不够,跳过', ['cb_num' => $user->pledge_num]);
            return $this->toCommunityReward($user->pid, $num, $oldBl);
        }

        // 判断该用户实际拿到的比例
        if($bl <= $oldBl){
            \Log::info('用户持币数量级别的奖励已被领取,跳过', ['cb_num' => $user->pledge_num]);
            return $this->toCommunityReward($user->pid, $num, $oldBl);
        }else{
            $nBl = bcsub($bl, $oldBl, 4);
            $oldBl = $bl;
            $bl = $nBl;
        }

        \DB::beginTransaction();
        try {

            $oneNum = bcmul($num, $bl, 8);
            if($oneNum > 0){
                EnergyService::orderSpeedRelease($uid, $oneNum, '社区节点奖加速', $this->uid);
            }else{
                \Log::info('社区节点奖比例异常');
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('社区节点奖加速释放出现异常');

        }

        return $this->toCommunityReward($user->pid, $this->num, $oldBl);

    }

    // 获取社区节点奖奖励比例
    private function getCommunityRewardBl($num)
    {

        $bl = 0;

        $pl = PledgeLevel::where('num', '<=', $num)->latest('num')->first();
        if($pl){
            $bl = $pl->pledge_bl;
        }

        return $bl;

    }

}

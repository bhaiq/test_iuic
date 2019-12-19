<?php

namespace App\Jobs;

use App\Models\EnergyOrder;
use App\Models\User;
use App\Services\EnergyService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnergyTeamRelease implements ShouldQueue
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

        \Log::info('=-=-=-=-=-  进行静态释放的团队奖操作  -=-=-=-=-=');

        // 获取用户信息
        $user = User::find($this->uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        $this->toHandle($user->pid, $this->num, 1);

        \Log::info('=-=-=-=-=-  结束静态释放的团队奖操作  -=-=-=-=-=');

    }

    public function toHandle($uid, $num, $layer)
    {

        \Log::info('记录进来的层数信息', ['uid' => $uid, 'num' => $num, 'layer' => $layer]);

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
            return $this->toHandle($user->pid, $this->num, $layer);
        }

        \DB::beginTransaction();
        try {

            EnergyService::orderSpeedRelease($uid, $this->num, '团队奖加速', $this->uid);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('团队奖加速释放出现异常');

        }

        $layer++;
        return $this->toHandle($user->pid, $this->num, $layer);

    }

}

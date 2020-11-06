<?php

namespace App\Console\Commands;

use App\Jobs\EnergyTeamRelease;
use App\Models\EnergyOrder;
use App\Services\EnergyService;
use Illuminate\Console\Command;

class ReleaseEnergy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releaseEnergy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '释放能量';

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

        \Log::info('=====  定时释放能量资产  =====');
        $switch = config('energy.energy_switch');
        if($switch == 1){
            $this->toRelease();
        }
        

        \Log::info('=====  结束释放能量资产  =====');

    }

    private function toRelease()
    {

        // 获取能量报单信息
        $eo = EnergyOrder::where(['status' => 0, 'type' => 1])->where('created_at', '<', now()->toDateString() . ' 00:00:00')->get();
        if($eo->isEmpty()){
            \Log::info('没有合适的能量报单信息，放弃报单');
            return false;
        }

        foreach ($eo as $k => $v){

            \DB::beginTransaction();
            try {

                // 获取静态释放比例
                $bl = config('energy.energy_static_release_bl', 0.01);

                // 计算本次释放的数量
                $oneNum = bcmul($v->num, $bl, 8);
                if($oneNum <= 0){
                    \Log::info('释放比例异常' . $oneNum);
                    continue;
                }

                // 判断本次释放是否能达到释放顶点
                if(bcadd($oneNum, $v->release_num, 8) >= $v->add_num){

                    // 重新计算可以得到的数量
                    $oneNum = bcsub($v->add_num, $v->release_num, 8);
                    if($oneNum <= 0){

                        // 订单异常，更正异常
                        EnergyOrder::where('id', $v->id)->update(['status' => 1]);

                        \Log::info('ID为' . $v->id . '的订单异常,订单更正');

                        continue;

                    }

                    // 进行释放
                    EnergyService::orderRelease($v->uid, $oneNum, $v->id, '静态释放');

                    // 订单状态更改
                    $updData = [
                        'release_num' => $v->add_num,
                        'status' => 1,
                    ];
                    EnergyOrder::where('id', $v->id)->update($updData);

                }else{

                    // 进行释放
                    EnergyService::orderRelease($v->uid, $oneNum, $v->id, '静态释放');

                    // 订单释放量增加
                    EnergyOrder::where('id', $v->id)->increment('release_num', $oneNum);

                }

                \DB::commit();

            } catch (\Exception $exception) {

                \DB::rollBack();

                \Log::info('静态释放能量订单出现异常');

                continue;

            }

            // 获取团队奖的释放比例
            $teamBl = config('energy.energy_team_reward_bl', 0.01);
            $newNum = bcmul($teamBl, $oneNum, 8);
            if($newNum > 0){
                // 加入队列
                dispatch(new EnergyTeamRelease($v->uid, $newNum));
            }

        }

    }

}

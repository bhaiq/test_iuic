<?php

namespace App\Console\Commands;

use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Services\EcologySettlement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EcologyYjBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecology_yj_bonus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '产业红利奖';

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

//        $ce_time = time();
        $ce_time = strtotime("-1 day");
        //1万(今日总报单金额)*生态等级比例 / 该等级当前人数   分给每个生态用户(从他的冻结积分释放到可用)
        $EcologyCreaditsDay = new \App\Models\EcologyCreaditDay();

        $ecdData = [
            'set_status' => 1,
            'set_time' => date('Y-m-d H:i:s'),
        ];

        //判断是否是自动结算,不是则终止
        $set_status = EcologyConfigPub::where('id',1)->value('settlement_switch');
        if($set_status != 1){
            Log::info("配置为手动结算,自动则终止");
            return;
        }

        $EcologySettlement = new EcologySettlement();
        $info = $EcologyCreaditsDay
            ->where("day_time",date("Y-m-d",$ce_time))
            ->where('set_status',0)
            ->first();
        if(empty($info)){
            Log::info("当前没有未结算的");
            return;
        }
        \DB::beginTransaction();
        try {

            //修改结算表信息
            $EcologyCreaditsDay->where("day_time",date("Y-m-d",$ce_time))->update($ecdData);
            /////待处理/////
            //结算
            $settlement = $EcologySettlement->settlement($info->id,$info->total_cny_actual,date('Y-m-d H:i:s'));
            if($settlement['code'] == 0){
                Log::info("结算失败".$settlement['msg']);
                return;
            }
            // 加入队列处理
            // dispatch(new EcologySettlementQueue($id,$total_cny_actual,$res,$dqtime));
            /////待处理/////
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::info("修改失败");
            throw $e;
            return;
        }
    }
}

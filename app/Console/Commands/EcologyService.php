<?php

namespace App\Console\Commands;

use App\Models\CreaditTransfer;
use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\User;
use Illuminate\Console\Command;

class EcologyService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecology_service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生态2手续费奖';

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
        $ce_time =  strtotime("-1 day");
//        $ce_time =  time();
//        $end_time = date("Y-m-d 23:59:59");
        $end_time = date("Y-m-d");
        //每天划转手续费*比例 / 指定总人数(后台添加)  分给每个指定人(直接加到可用积分中)
        $yestaody = date("Y-m-d",$ce_time);
        $all_service = CreaditTransfer::where('created_at','>',$yestaody)
            ->where('created_at','<',$end_time)
            ->sum('service_charge');
//        dd($all_service);
        $users = User::where('is_ecology_service',1)->select('id')->get()->toArray();
        $num = count($users);
        $rate = EcologyConfigPub::where('id',1)->value('rate_service');
        $average = $all_service * $rate / $num;
        $wallet = New EcologyCreadit();
        foreach ($users as $k => $v)
        {
            //判断是否有钱包没有则生成
            $user_wallet = EcologyCreadit::where('uid',$v['id'])->first();
            if(empty($user_wallet)){
                $wallet->created_wallet($v['id']);
            }
            if($average > 0){
                EcologyCreadit::a_o_m($v['id'],$average,1,7,'生态2手续费奖',1);
            }

        }
        //插入附属表
        $fushu = New \App\Models\EcologyServiceDayFushu();
        $fushu->day_id = \App\Models\EcologyServiceDay::where('day_time',$yestaody)->value('id');
        $fushu->type = 1;
        $fushu->rate = $rate;
        $fushu->people_num = $num;
        $fushu->one_num = $average;
        $fushu->save();

    }
}

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
        //每天划转手续费*比例 / 指定总人数(后台添加)  分给每个指定人(直接加到可用积分中)
        $yestaody = date("Y-m-d",strtotime("-1 day"));
        $all_service = CreaditTransfer::where('created_at','>',$yestaody)
            ->where('created_at','<',date("Y-m-d"))
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
            EcologyCreadit::a_o_m($v['id'],$average,1,7,'生态2手续费奖',1);
        }

    }
}

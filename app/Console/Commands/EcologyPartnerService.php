<?php

namespace App\Console\Commands;

use App\Models\CreaditTransfer;
use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\UserPartner;
use Illuminate\Console\Command;

class EcologyPartnerService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecology_partner_service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生态2手续费合伙人奖';

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
//        $ce_time =  strtotime("-1 day");
        $ce_time =  time();
        $end_time = date("Y-m-d 23:59:59");
//        $end_time = date("Y-m-d");
        //例:每天划转手续费*比例 / 合伙人总人数   分给每个人(直接加到可用积分中)
        $yestaody = date("Y-m-d",$ce_time);
        $all_service = CreaditTransfer::where('created_at','>',$yestaody)
            ->where('created_at','<',$end_time)
            ->sum('service_charge');
        $rate = EcologyConfigPub::where('id',1)->value('rate_service_partner');
        $all_people = UserPartner::where('uid','>',0)->count();
        $lists = UserPartner::where('uid','>',0)->get();
        $average = $all_service * $rate / $all_people;
        $wallet = New EcologyCreadit();
        foreach ($lists as $k => $v)
        {
            //判断是否有钱包没有则生成
            $user_wallet = EcologyCreadit::where('uid',$v->uid)->first();
            if(empty($user_wallet)){
                $wallet->created_wallet($v->uid);
            }
            EcologyCreadit::a_o_m($v->uid,$average,1,7,'生态2手续费合伙人奖',1);
        }

    }
}

<?php

namespace App\Console\Commands;

use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\UserPartner;
use Illuminate\Console\Command;

class EcologyPartner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecologypartner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生态2合伙人奖';

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

        //例:1万(今日报单总金额)*1%(比例) / 合伙人总人数  分给每个合伙人(直接加到可用积分中)
        $rate = EcologyConfigPub::where('id',1)->value('rate_partner');
        $all_num = \App\Models\EcologyCreaditDay::where('day_time',date('Y-m-d'))->value('total_cny_actual');
        $people_num = UserPartner::where('id','>',0)->count();
        $average = $all_num * $rate / $people_num;
        $list = UserPartner::where('id','>',0)->get();
        $wallet = New EcologyCreadit();
        foreach ($list as $k => $v){
            //判断是否有钱包没有则生成
            $user_wallet = EcologyCreadit::where('uid',$v->uid)->first();
            if(empty($user_wallet)){
                $wallet->created_wallet($v->uid);
            }
            EcologyCreadit::a_o_m($v->uid,$average,1,5,'生态2合伙人奖','1');
        }

    }
}

<?php

namespace App\Console\Commands;

use App\Models\EcologyConfigPub;
use App\Models\EcologyCreaditOrder;
use Illuminate\Console\Command;

class EcologyCreaditDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecologycreaditday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '凌晨生成前一天报单总数据信息';

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
        //
//        $ce_time = time();
        $ce_time = strtotime("-1 day");
        $time = date('Y-m-d',$ce_time);//时间
//        $end = date('Y-m-d 23:59:59');
        $end = date('Y-m-d');

        //每日全网新增业绩(元)(应结算数)
        $total_cny = EcologyCreaditOrder::where('created_at','>',$time)
            ->where('created_at','<',$end)
            ->whereNull('end_time')
            ->sum('price_cny');
        //每日全网新增业绩(积分)
        $total_creadit = EcologyCreaditOrder::where('created_at','>',$time)
            ->where('created_at','<',$end)
            ->whereNull('end_time')
            ->sum('creadit_amount');
        //结算方式

        $log = New \App\Models\EcologyCreaditDay();
        $log->day_time = $time;
        $log->total_cny = $total_cny;
        $log->total_point = $total_creadit;
        $log->total_cny_actual = $total_cny;
        $log->set_status = 0;
        $log->save();
    }
}

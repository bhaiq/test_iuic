<?php

namespace App\Console\Commands;

use App\Models\KuangjiOrder;
use App\Models\KuangjiUserPosition;
use Illuminate\Console\Command;

class MinusDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minus_day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天关闭到期的矿机订单';

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
        $orders = KuangjiOrder::where('status',1)->get();
        foreach ($orders as $k => $v)
        {
            $start = strtotime(substr($v->created_at, 0, 10) . ' 00:00:00');
            $cur = time();
            $times = bcdiv(bcsub(bcadd($start, $v->total_day * 24 * 3600), $cur), 24 * 3600);
            if($times < 1){
                // 矿机订单关闭
                KuangjiOrder::where('id', $v->order_id)->update(['status' => 3]);

                // 矿位表更新
                KuangjiUserPosition::where('order_id', $v->order_id)->update(['order_id' => 0, 'kuangji_id' => 0]);
            }
        }
    }
}

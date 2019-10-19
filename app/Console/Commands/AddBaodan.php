<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use App\Models\ShopTotal;
use Illuminate\Console\Command;

class AddBaodan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addBaodan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '报单统计';

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

        \Log::info('===== 开始记录报单信息 =====');

        $this->addInfo();

        \Log::info('===== 记录报单信息结束 =====');

    }

    private function addInfo()
    {


        $btData = [
            'cur_date' => now()->subDay()->toDateString(),
            'gj_count' => ShopOrder::where('goods_name', '高级酒票')->whereDate('created_at', now()->subDay()->toDateString())->count(),
            'pt_count' => ShopOrder::where('goods_name', '普通酒票')->whereDate('created_at', now()->subDay()->toDateString())->count(),
            'zq_count' => ShopOrder::where('goods_name', '中秋特惠')->whereDate('created_at', now()->subDay()->toDateString())->count(),
            'created_at' => now()->toDateTimeString()
        ];

        ShopTotal::create($btData);

        return true;

    }


}

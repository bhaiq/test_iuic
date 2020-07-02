<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ExOrder;
use App\Models\ExOrderTogetger;
use App\Models\ExTeam;
use App\Models\MallGood;
use App\Models\ReleaseOrder;
use App\Models\ShopGoods;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\SymbolHistory;


class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $this->transferGoogs();

    }

    private function transferGoogs()
    {

        // 获取所有的商品
        $goods = ShopGoods::get();

        foreach ($goods as $v){

            $data = [
                'uid' => 0,
                'store_id' => 0,
                'goods_name' => $v->goods_name,
                'goods_price' => $v->goods_price,
                'goods_img' => $v->goods_img,
                'goods_info' => $v->goods_info,
                'ore_pool' => $v->ore_pool,
                'buy_count' => $v->buy_count,
                'type' => 1,
                'created_at' => now()->toDateTimeString(),
            ];

            MallGood::create($data);

        }

    }

}

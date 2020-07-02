<?php

use Illuminate\Database\Seeder;

class OtcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\OtcPublishSell::insert([
            ['id' => 1, 'uid' => 3, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
            ['id' => 2, 'uid' => 4, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
            ['id' => 3, 'uid' => 2, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
        ]);
        \App\Models\OtcPublishBuy::insert([
            ['id' => 1, 'uid' => 3, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
            ['id' => 2, 'uid' => 4, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
            ['id' => 3, 'uid' => 2, 'amount' => 1000, 'amount_min' => 10, 'price' => 6.61, 'amount_max' => 100, 'amount_lost' => 1000, 'pay_alipay' => true, 'is_over' => 0],
        ]);

        \App\Models\OtcOrder::insert([
            ['id' => 1, 'sell_id' => 3, 'amount' => 100, 'price' => 6.61, 'total_price' => 661, 'uid' => 3, 'type' => \App\Models\OtcOrder::TYPE_SELL],
        ]);

        \App\Models\OtcOrder::insert([
            ['id' => 2, 'buy_id' => 1, 'amount' => 100, 'price' => 6.61, 'is_pay' => false, 'total_price' => 661, 'uid' => 3, 'type' => \App\Models\OtcOrder::TYPE_BUY],
            ['id' => 3, 'buy_id' => 3, 'amount' => 100, 'price' => 6.61, 'is_pay' => true, 'total_price' => 661, 'uid' => 3, 'type' => \App\Models\OtcOrder::TYPE_BUY],
        ]);
    }
}

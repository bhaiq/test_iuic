<?php

namespace Tests\Unit;

use Tests\TestCase;

class SOrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSell()
    {
        $data = ['amount' => 15];
        $this->loginClient();
        $this->post('sOrderSell', $data)->assertOk();

        $this->assertDatabaseHas('account', [
            'S' => 985,
            'sell_S' => 14.7
        ]);
        $this->assertDatabaseHas('s_order_sell', [
            'uid' => '2',
            'amount' => 14.7
        ]);

        $this->get('sOrderSell?is_over=0')->assertOk();
    }

    public function testBuy()
    {
        $data = ['amount' => 12];
        $this->loginClient();
        $this->post('sOrderBuy', $data)->assertOk();

        $this->assertDatabaseHas('s_order_buy', [
            'uid' => '2',
            'amount' => 12
        ]);

        $this->get('sOrderBuy?is_over=0')->assertOk();
    }
}

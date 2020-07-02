<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OtcSellTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testOtcSell()
    {
        $data = [
            'amount' => 1000,
            'amount_min' => 100,
            'amount_max' => 1000,
            'pay_wechat' => true,
            'pay_alipay' => false,
            'pay_bank' => false,
            'price' => 7.11,
            'currency' => 1,
        ];

        $this->loginClient();
        $this->post('otcSell', $data)
            ->assertOk()
            ->assertJson($data);

        $this->assertDatabaseHas('account', [
            'US' => 0,
            'sell_US' => 1000
        ]);

        $data['uid'] = 2;
        $this->assertDatabaseHas('otc_publish_sell', $data);

        $this->get('otcSell')->assertOk();

    }

    public function testOrderSell()
    {
        $data = [
            "amount" => 10,
            "uid" => 2,
            "price" => "6.610",
            "sell_id" => 1,
        ];
        $this->loginClient();
        $this->put('otcSell/1', [
            'amount' => 10
        ])->assertJson($data);

        $this->assertDatabaseHas('otc_order', $data);

        $this->get('otcSell/1')
            ->assertOk();
    }
}

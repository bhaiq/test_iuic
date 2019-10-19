<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OtcBuyTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testOtc()
    {
        $data = [
            'amount' => 10000,
            'amount_min' => 100,
            'amount_max' => 1000,
            'pay_wechat' => true,
            'pay_alipay' => false,
            'pay_bank' => false,
            'price' => 7.11,
            'currency' => 1,
        ];

        $this->loginClient();
        $this->post('otcBuy', $data)
            ->assertOk()
            ->assertJson($data);

        $data['uid'] = 2;
        $this->assertDatabaseHas('otc_publish_buy', $data);

        $this->get('otcBuy')->assertOk();

    }

    public function testOrder()
    {
        $data = [
            "amount" => 10,
            "uid" => 2,
            "price" => "6.610",
            "buy_id" => 1,
        ];
        $this->loginClient();
        $this->put('otcBuy/1', [
            'amount' => 10
        ])->assertJson($data);

        $this->assertDatabaseHas('otc_order', $data);

        $this->get('otcBuy/1')
            ->assertOk();
    }
}

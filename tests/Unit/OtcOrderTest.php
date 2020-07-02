<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OtcOrderTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testList()
    {
        $this->loginClient1();
        $this->get('otcOrder')->assertOk();
    }

    public function testBuyPay()
    {
        $id = 1;
        $this->assertDatabaseHas('otc_order', [
            'id'     => $id,
            'is_pay' => false
        ]);
        $this->loginClient1();
        $this->put('otcOrder/' . $id . '/pay')
            ->assertOk();
        $this->assertDatabaseHas('otc_order', [
            'id'     => $id,
            'is_pay' => true
        ]);

        $id = 2;
        $this->assertDatabaseHas('otc_order', [
            'id'     => $id,
            'is_pay' => false
        ]);
        $this->loginClient1();
        $this->put('otcOrder/' . $id . '/pay')
            ->assertOk();
        $this->assertDatabaseHas('otc_order', [
            'id'     => $id,
            'is_pay' => true
        ]);
    }

    public function testCoinPay()
    {
        $this->loginClient1();
        $this->put('otcOrder/1/pay');
        $this->put('otcOrder/2/pay');

        $this->loginClient();
        $this->put('otcOrder/1/coin', ['password' => '123456'])
            ->assertOk();
        $this->assertDatabaseHas('account', [
            'uid'     => 2,
            'sell_US' => -100,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 3,
            'US'  => 1100,
        ]);
        $this->put('otcOrder/1/coin')
            ->assertStatus(400);
    }

    public function testCoinPaySell()
    {
        $this->loginClient1();
        $this->put('otcOrder/3/coin', ['password' => '123456'])->assertOk();
        $this->assertDatabaseHas('account', [
            'uid'     => 3,
            'sell_US' => -100,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 2,
            'US'  => 1100,
        ]);
        $this->put('otcOrder/3/coin', ['password' => '123456'])
            ->assertStatus(400);
    }
}

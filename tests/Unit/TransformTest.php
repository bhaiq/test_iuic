<?php

namespace Tests\Unit;

use App\Constants\HttpConstant;
use App\Models\TransformLog;
use Tests\TestCase;

class TransformTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testTransformToUS()
    {
        $data = [
            'amount' => 10,
        ];

        $this->put('transformToUS', $data)
            ->assertStatus(HttpConstant::CODE_401_UNAUTHORIZED);

        $this->loginClient();

        $this->put('transformToUS', $data)
            ->assertOk();
        $this->assertDatabaseHas('transform_log', [
            'uid' => 2,
            'USDT' => 10,
            'US' => 10,
            'type' => TransformLog::TYPE_USDT_TO_US,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 2,
            'USDT' => 990,
            'US' => 1010,
        ]);

    }

    public function testTransformToUSDT()
    {
        $data = [
            'amount' => 10,
        ];

        $this->put('transformToUSDT', $data)
            ->assertStatus(HttpConstant::CODE_401_UNAUTHORIZED);

        $this->loginClient();

        $this->put('transformToUSDT', $data)
            ->assertOk();
        $this->assertDatabaseHas('transform_log', [
            'uid' => 2,
            'USDT' => 10,
            'US' => 10 * TransformLog::RATE,
            'type' => TransformLog::TYPE_US_TO_USDT,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 2,
            'USDT' => 1010,
            'US' => 988,
        ]);

    }

}

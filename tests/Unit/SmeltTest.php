<?php

namespace Tests\Unit;

use App\Constants\HttpConstant;
use App\Models\SmeltLog;
use Tests\TestCase;

class SmeltTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSplit()
    {
        $data = [
            'amount' => 10,
        ];

        $this->put('smeltSplit', $data)
            ->assertStatus(HttpConstant::CODE_401_UNAUTHORIZED);

        $this->loginClient();
        $this->put('smeltSplit', $data)->assertOk();

        $this->assertDatabaseHas('smelt_log', [
            'uid' => 2,
            'US' => 10,
            'type' => SmeltLog::TYPE_SPLIT,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 2,
            'US' => 990,
            'lock_U' => 20,
            'lock_S' => 30,
        ]);
    }

    public function testCompound()
    {
        $data = [
            'amount' => 10,
        ];

        $this->put('smeltCompound', $data)
            ->assertStatus(HttpConstant::CODE_401_UNAUTHORIZED);

        $this->loginClient();
        $this->put('smeltCompound', $data)->assertOk();

        $this->assertDatabaseHas('smelt_log', [
            'uid' => 2,
            'US' => 10,
            'type' => SmeltLog::TYPE_COMPOUND,
        ]);

        $this->assertDatabaseHas('account', [
            'uid' => 2,
            'US' => 1010,
            'U' => 995.0,
            'S' => 995.0,
        ]);
    }
}

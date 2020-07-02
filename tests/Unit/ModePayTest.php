<?php

namespace Tests\Unit;

use App\Constants\HttpConstant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModePayTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $data = [
            'qr_code' => 'sdfdsf',
            'number' => '62264802021020203',
            'name' => '东南亚银行',
            'type' => 2,
            'bank' => [
                'name' => '东南亚银行',
                'address' => '测试开户行',
            ]
        ];
        $this->loginClient();
        $res = $this->post('modePay', $data)->assertOk();
        unset($data['name']);
        unset($data['bank']);
        $res->assertJson($data);

        $this->assertDatabaseHas('mode_of_payment', $data);

        $this->get('modePay')->assertOk();

        $this->get('modePay/1')
            ->assertOk()
            ->assertJson($data);

        $this->delete('modePay/1')->assertOk();
        $this->assertDatabaseMissing('mode_of_payment', $data);

        $this->get('modePay/1')
            ->assertStatus(HttpConstant::CODE_400_BAD_REQUEST);

    }
}

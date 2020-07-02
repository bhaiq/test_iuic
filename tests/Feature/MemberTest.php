<?php

namespace Tests\Feature;

use App\Libs\StringLib;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemberTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testMember()
    {
        $data = [
            'email'    => 'testAdmin@sina.com',
            'password' => '123123',
            'type'     => '2'
        ];
        $this->loginAdmin();
        $res = $this->postAdmin('member', $data)->assertOk();

        $data['password'] = StringLib::password($data['password']);
        $this->assertDatabaseHas('user', $data);
        unset($data['password']);
        $res->assertJson($data);

        $data = [
            'email'    => 'testAdmin@sina.com',
            'password' => '123123',
        ];
        $res  = $this->postAdmin('memberLogin', $data)->assertOk();
        unset($data['password']);
        $res->assertJson($data);
    }

    public function testList()
    {
        $this->loginAdmin();
        $this->getAdmin('member')->assertOk();
        $data = [
            'password' => 'sdfwe123'
        ];

        $this->putAdmin('member/10/password', $data)->assertOk();

        $data['email'] = 'testAdmin111@qq.com';

        $res  = $this->postAdmin('memberLogin', $data);
        unset($data['password']);
        $res->assertJson($data);
    }
}

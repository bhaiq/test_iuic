<?php

namespace Tests\Unit;

use App\Constants\HttpConstant;
use App\Services\EmailService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCreate()
    {
        $data = [
            'username' => 'sdfsfw@qq.com',
            'password' => '123123',
            're_password' => '123123',
            'invite_code' => '1234',
            'code' => '1234',
            'type' => '2',
        ];
        $key = (new EmailService())->redisKey('sdfsfw@qq.com');

        Redis::setex($key, 70, 1234);
        $res = $this->post('user', $data)
            ->assertOk();

        $data = [
            'email' => 'sdfsfw@qq.com'
        ];

        $res->assertJsonFragment($data);
        $this->assertDatabaseHas('user', $data);

        $this->post('user', $data)
            ->assertStatus(HttpConstant::CODE_400_BAD_REQUEST);


        $data = [
            'username' => 'sdfsfw@qq.com',
            'password' => '123123'
        ];

        $res = $this->post('userLogin', $data)
            ->assertOk();

        $data = [
            'email' => 'sdfsfw@qq.com',
        ];

        $res->assertJsonFragment(array_merge($data), [
            'token' => 0,
        ]);

        $data['password'] = 'wefd12';
        $this->post('userLogin', $data)
            ->assertStatus(HttpConstant::CODE_400_BAD_REQUEST);
    }

    public function testChildren()
    {
        $this->loginClient();
        $this->get('user')->assertOk();
    }

    public function testLogin()
    {
        $this->loginAdmin();
        $this->post('userLogout')->assertOk();
    }

    public function testForget()
    {
        $data=[
            'username'=>2134,
            'password'=>'sfsfwesdfsdf',
            're_password'=>'sfsfwesdfsdf',
            'code'=>2134,
        ];
        $this->put('userForget',$data)->assertStatus(HttpConstant::CODE_400_BAD_REQUEST);
    }
}

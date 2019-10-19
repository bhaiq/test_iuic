<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testSend()
    {
        $this->get('userCode?username=wx90h@sina.com&type=1')
        ->assertOk();
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class accountTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testSlog()
    {
        $this->loginClient();
        $this->get('account?last_id=6')->assertOk();
    }

    public function testAccountInfo()
    {
        $this->loginClient();
        $this->get('accountInfo')->assertOk();
    }

    public function testSort()
    {
        $this->loginClient();
        $this->get('accountRank')->assertOk();
    }
}

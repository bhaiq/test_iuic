<?php

use Illuminate\Database\Seeder;
use \App\Libs\StringLib;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::insert([
            ['id' => 1, 'email' => '123456@qq.com', 'transaction_password' => StringLib::password(123456), 'password' => StringLib::password(123456), 'pid' => 0, 'type' => 1, 'invite_code' => 1234],
            ['id' => 10, 'email' => 'testAdmin111@qq.com', 'transaction_password' => StringLib::password(123456), 'password' => StringLib::password(123456), 'pid' => 0, 'type' => 2, 'invite_code' => 1234],
            ['id' => 2, 'email' => '123457@qq.com', 'transaction_password' => StringLib::password(123456), 'password' => StringLib::password(123456), 'pid' => 0, 'type' => 0, 'invite_code' => 4321],
            ['id' => 3, 'email' => '123458@qq.com', 'transaction_password' => StringLib::password(123456), 'password' => StringLib::password(123456), 'pid' => 2, 'type' => 0, 'invite_code' => 4321],
            ['id' => 4, 'email' => '123459@qq.com', 'transaction_password' => StringLib::password(123456), 'password' => StringLib::password(123456), 'pid' => 2, 'type' => 0, 'invite_code' => 4321],
        ]);
    }
}

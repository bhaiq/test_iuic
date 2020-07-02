<?php

use Illuminate\Database\Seeder;

class AccountTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Account::insert([
            ['uid' => 1, 'USDT' => 1000, 'US' => 1000, 'U' => 1000, 'S' => 1000],
            ['uid' => 2, 'USDT' => 1000, 'US' => 1000, 'U' => 1000, 'S' => 1000],
            ['uid' => 3, 'USDT' => 1000, 'US' => 1000, 'U' => 1000, 'S' => 1000],
        ]);
        \App\Models\Slog::insert([
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
            ['uid'=>2, 'amount'=>10, 'type'=>1, 'remark'=>'sfe'],
        ]);
    }
}

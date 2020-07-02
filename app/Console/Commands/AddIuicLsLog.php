<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\IuicLsLog;
use App\Models\User;
use Illuminate\Console\Command;

class AddIuicLsLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addIuicLsLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'IUIC临时记录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->toHandle();

    }

    private function toHandle()
    {

        // 所有用户IUIC账号的信息
        $user = User::oldest('id')->get();

        foreach ($user as $v){

            $fbNum = 0;
            $fbFrozenNum = 0;
            $bbNum = 0;
            $bbFrozenNum = 0;

            // 获取用户法币IUIC余额信息
            $fb = Account::where(['uid' => $v->id, 'coin_id' => 2, 'type' => 1])->first();
            if($fb){
                $fbNum = $fb->amount;
                $fbFrozenNum = $fb->amount_freeze;
            }

            // 获取用户币币IUIC余额信息
            $bb = Account::where(['uid' => $v->id, 'coin_id' => 2, 'type' => 0])->first();
            if($bb){
                $bbNum = $bb->amount;
                $bbFrozenNum = $bb->amount_freeze;
            }

            $num = bcadd(bcadd(bcadd($fbNum, $fbFrozenNum, 8), $bbNum, 8), $bbFrozenNum, 8);

            $data = [
                'uid' => $v->id,
                'num' => $num,
                'fb_num' => $fbNum,
                'fb_forzen_num' => $fbFrozenNum,
                'bb_num' => $bbNum,
                'bb_frozen_num' => $fbFrozenNum,
                'created_at' => now()->toDateTimeString(),
            ];

            IuicLsLog::create($data);

        }

    }

}

<?php

namespace App\Console\Commands;

use App\Models\IuicLsLog;
use App\Models\KuangjiLinghuo;
use Illuminate\Console\Command;

class PledgeLinghuo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pledgeLinghuo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '质押灵活矿机';

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


        // 获取IUIC余额日志
        $il = IuicLsLog::get();

        foreach ($il as $k => $v){


            // 按照1比3的比例质押成灵活矿机

            if($v->num > 0){

                $num = bcmul($v->num, 1.3, 8);

                $klData = [
                    'uid' => $v->uid,
                    'num' =>$num,
                    'start_time' => '2020-02-28 10:00:00',
                    'created_at' => '2020-02-28 10:00:00',
                ];

                KuangjiLinghuo::create($klData);

                \Log::info('成功插入一条数据', ['uid' => $v->uid]);

            }
            

        }


    }
}

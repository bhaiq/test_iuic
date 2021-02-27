<?php

namespace App\Console\Commands;

use App\Models\EcologyCreadit;
use App\Models\User;
use Illuminate\Console\Command;

class Out extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '释放完成24小时内没有复投就出局';

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
        //每小时跑一次,判断当前这个人是否超过24小时未复投,未复投关闭得奖
        $wallet = EcologyCreadit::whereNotnull('release_end_time')->get();
        foreach ($wallet as $k => $v){
            $cle = time() - strtotime($v->release_end_time);
            $hours = ceil($cle/3600);
            if($hours >= 24){
                User::where('id',$wallet->uid)->update(['ecology_lv_close'=>1]);
            }
        }
    }
}

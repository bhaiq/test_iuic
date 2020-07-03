<?php

namespace App\Console\Commands;

use App\Models\Kuangji;
use App\Models\KuangjiUserPosition;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class JlKjRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jlkjrelease';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新的释放'; //每日执行,将已购买矿机算力加到user_info->release_total

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
        //获取所有用户开通矿位信息
        Log::info("矿机算力开始释放");
        $kuangji_list = KuangjiUserPosition::where('order_id','>',0)->where('kuangji_id','>',0)->get();
        foreach ($kuangji_list as $k => $kuangji){
            //当前矿位矿机算力
            $suanli = Kuangji::where('id',$kuangji->kuagnji_id)->value('suanli');
            Log::info("算力",['suanli'=>$suanli]);
            //user_info中增加release_total
            // 释放矿池数增加,不能大于user_info->buy_total
            $user_info = UserInfo::where('uid',$kuangji->uid)->first();
            if( $user_info->release_total >= $user_info->buy_total){
                Log::info("释放数达到最大,停止释放",['uid'=>$kuangji->uid,'order_id'=>$kuangji->order_id,'kuangji_id'=>$kuangji->kuangji_id]);
                continue;
            }
            if(bcadd($suanli,$user_info->release_total,8) >= $user_info->buy_total){
                //实际加的数量
                $true_num = bcsub($user_info->buy_total,$user_info->release_total,8);
            }else{
                $true_num = $suanli;
            }
            UserInfo::where('uid', $kuangji->uid)->increment('release_total', $true_num);
            // 矿池表信息增加
            UserWalletLog::addLog($kuangji->uid, 'kuangji_user_position', $kuangji->order_id, '算力灵活矿机释放', '-', $true_num, 2, 1);
        }
        Log::info("矿机算力结束释放");

    }
}

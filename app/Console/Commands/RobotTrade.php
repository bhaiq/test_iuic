<?php

namespace App\Console\Commands;

use App\Models\AccessToken;
use App\Models\ExOrder;
use Illuminate\Console\Command;

class RobotTrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'robotTrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '机器人交易';

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

        \Log::info('=====  开始进行机器人订单  =====');

        $this->toTrade();

        \Log::info('=====  结束进行机器人订单  =====');

    }

    private function toTrade()
    {

        // 先判断机器人开关是否开启
        if(config('robot.robot_switch', 0)){
            \Log::info('机器人总开关关闭，结束');
            return false;
        }

        // 获取机器人信息
        $at = AccessToken::where('uid', 297)->first();
        if(!$at){
            \Log::info('机器人信息有误，结束');
            return false;
        }

        // 再判断当前时间是否在交易时间之内
        $startTime = config('robot.robot_start_time', '00:00:00');
        $endTime = config('robot.robot_end_time', '23:59:59');
        $curTime = now()->toDateTimeString();
        if($curTime < now()->toDateString() . ' ' . $startTime && $curTime > now()->toDateString() . ' ' . $endTime){
            \Log::info('不在交易的时间之内，结束');
            return false;
        }

        // 获取当前买方最高价格
        $buyPrice = 0;
        $exBuy = ExOrder::where(['status' => 0, 'type' => 1, 'team_id' => 1])->latest('price')->first();
        if($exBuy){
            $buyPrice = $exBuy->price;
        }

        // 获取当前卖方最低价格
        $sellPrice = 0;
        $exSell = ExOrder::where(['status' => 0, 'type' => 0, 'team_id' => 1])->oldest('price')->first();
        if($exSell){
            $sellPrice = $exSell->price;
        }

        \Log::info('获取到的买卖双方价格', ['buy_price' => $buyPrice, 'sell_price' => $sellPrice]);

        // 获取机器人挂买最高价格
        $maxPrice = config('robot.robot_max_price', 10);
        if($buyPrice >= $maxPrice){
            \Log::info('当前价格已经超过机器人可挂买价格，结束', ['buy_price' => $buyPrice, 'max_price' => $maxPrice]);
            return false;
        }

        // 获取机器人最低挂卖价格
        $minPrice = config('robot.robot_min_price', 0.0001);
        if($sellPrice <= $minPrice){
            \Log::info('当前价格已经超过机器人可挂买价格，结束', ['sell_price' => $sellPrice, 'min_price' => $minPrice]);
            return false;
        }

        // 获取机器人挂单的最小幅度
        $minRange = config('robot.robot_min_range', 0.0001);

        // 获取机器人挂单的最大幅度
        $maxRange = config('robot.robot_max_range', 1);

        // 获取本次是做空还是做多
        $tradeStatus = config('robot.robot_trade_status', 1);
        if($tradeStatus){

            // 价格自增的随机数
            $addPrice = bcdiv(rand(bcmul($minRange, 100000), bcmul($maxRange, 100000)), 100000, 4);

            // 自增的价格
            $realPrice = bcadd($buyPrice, $addPrice, 4);

            // 实际的价格
            $newPrice = $realPrice > $sellPrice ? $sellPrice : $realPrice;

            // 实际的价格
            $newPrice = $newPrice > $maxPrice ? $maxPrice : $newPrice;

            // 请求的地址
//            $url = url()->current() . '/api/ex/buy/1';
            $url = 'http://iuic.9dg.com/api/ex/buy/1';

        }else{

            // 价格自减的随机数
            $reducePrice = bcdiv(rand(bcmul($minRange, 100000), bcmul($maxRange, 100000)), 100000, 4);

            // 自减的价格
            $realPrice = bcsub($sellPrice, $reducePrice, 4);

            // 实际的价格
            $newPrice = $realPrice > $buyPrice ? $realPrice : $buyPrice;

            // 实际的价格
            $newPrice = $newPrice < $minPrice ? $minPrice : $newPrice;

            // 请求的地址
//            $url = url()->current() . '/api/ex/sell/1';
            $url = 'http://iuic.9dg.com/api/ex/sell/1';
        }

        // 获取机器人挂买挂卖的最小量
        $tradeMinNum = config('robot.robot_min_trade_num', 1);

        // 获取机器人挂买挂卖的最大量
        $tradeMaxNum = config('robot.robot_max_trade_num', 100);

        // 自增的量
        $addTradeNum = bcdiv(rand(bcmul($tradeMinNum, 100000), bcmul($tradeMaxNum, 100000)), 100000);

        // 头部信息
        $header = [
            "Content-type:application/json;charset='utf-8'",
            "Accept:application/json",
            "x-token:" . $at->token
        ];

        // 挂单请求
        @$this->sendCurl($url, ['price' => $newPrice, 'amount' => $addTradeNum], $header);

    }

    // 发送请求
    private function sendCurl($url, $data, $header = [])
    {

        $data  = json_encode($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);

    }

}

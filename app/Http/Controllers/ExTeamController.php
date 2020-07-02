<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ExTeam;
use App\Models\Redis;

class ExTeamController extends Controller
{
    public function curPrice($team_id)
    {

        // 获取昨日收盘价格
        $lastPrice = Redis::get(Redis::KEY_EX_LAST_PRICE, $team_id);

        $result = [
            'today_trade_max' => bcmul(bcadd(1, config('trade.today_trade_max_bl'), 4), $lastPrice, 4),
            'today_trade_min' => bcmul(bcsub(1, config('trade.today_trade_min_bl'), 4), $lastPrice, 4),
            'cur_trade_max' => bcmul(config('trade.cur_trade_max_bl'), 1, 2),
            'cur_trade_min' => bcmul(config('trade.cur_trade_min_bl'), 1, 2),
            'last_price' => $lastPrice,
        ];

        return $this->response(array_merge(ExTeam::getCurPrice($team_id), $result));
    }

}

<?php

namespace App\Services;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\ExOrder;
use App\Models\ExTeam;
use App\Models\ExUs;
use App\Models\Pure\ExUs as PureExUs;
use App\Models\ExUsLegalOrder;
use App\Models\UsdtLog;
use App\Models\UsLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ExService
{
    public static function curPrice($team_id, $price = 0)
    {
        $key       = 'EX_PRICE_' . $team_id;
        $red_price = Redis::get($key);
        if ($price != 0) {
            if ($red_price != $price) {
                $red_price = StringLib::sprintN($price, 2);
                Redis::setex($key, 604800, $red_price);
                self::pushCurPrice($team_id, $red_price);
            }
        }
        return $red_price ?? 1;
    }

    public static function pushList(int $team_id)
    {
        Service::pusher()->allPush('ex_list_sell_' . $team_id, ExOrder::sellList($team_id));
        Service::pusher()->allPush('ex_list_buy_' . $team_id, ExOrder::buyList($team_id));
    }

    public static function pushCurPrice($team_id, $price)
    {
        $price_cny = Account::getRate();
        $cny_rate  = Account::getRate();
        Service::pusher()->allPush('ex_us_price_' . $team_id, compact('price', 'price_cny', 'cny_rate'));
    }

}

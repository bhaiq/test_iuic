<?php


namespace App\Models;

use App\Libs\StringLib;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis as baseRedis;
use Illuminate\Support\Str;

class Redis
{
    const KEY_EX_TEAM = 'ex_team_model';
    const KEY_EX_LAST_PRICE = 'last_team_price';
    const KEY_EX_MARKET = 'ex_market';
    const KEY_EX_COIN = 'coin_name';
    const KEY_EX_HIST0RY_PRICE = 'ex_history_price';

    public static function get($key, $id, $time = 60, bool $decode = false)
    {
        $redis_key = $key . '_' . $id;
        $value     = baseRedis::get($redis_key);
        if (!$value || $time == 0) {
            $time  = $time ?: 60;
            $f     = Str::camel($key);
            $value = self::$f($id);
            baseRedis::setex($redis_key, $time, $value);
        }
        return json_decode($value, $decode);
    }

    /**
     * @param int $id
     * @return float|mixed
     */
    public static function lastTeamPrice(int $id)
    {
      	$order = ExOrderTogetger::whereTeamId($id)->where('created_at', '<', now()->toDateString() . ' 00:00:00')->latest('id')->first();
        //$order = ExOrderTogetger::whereTeamId($id)->whereDate('created_at', Carbon::yesterday()->toDateString())->orderBy('id', 'desc')->first();
        return $order ? $order->price : 0;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function exTeamModel(int $id)
    {
        return ExTeam::find($id)->toJson();
    }

    public static function exMarket($id)
    {
        if ($id == 0) {
            $data = ExTeam::all(['id', 'name', 'coin_id_goods', 'coin_id_legal'])->toArray();
        } else {
            $data = ExTeam::whereCoinIdLegal($id)->get(['id', 'name', 'coin_id_goods', 'coin_id_legal'])->toArray();
        }

        foreach ($data as $k => $v) {
            $price             = ExTeam::curPrice($v['id']);
            $last_price        = self::get(self::KEY_EX_LAST_PRICE, $v['id']);
            $data[$k]['cny']   = bcmul($price, Account::getRate(), 4);
            $data[$k]['price'] = $price;
            $data[$k]['rate']  = $last_price == 0 ? 0 : bcdiv(($price - $last_price) * 100, $last_price, 2);
        }

        return json_encode($data);
    }

    public static function coinName(string $name)
    {

        $coin = Coin::where('name', $name)->first();

        return $coin ? $coin->toJson() : '';

    }

    public static function exHistoryPrice($team_id)
    {
        $data = [];

        $price         = ExTeam::curPrice($team_id);
        $last_price    = self::get(self::KEY_EX_LAST_PRICE, $team_id);
        $data['cny']   = bcmul($price, Account::getRate(), 4);
        $data['price'] = $price;
        $data['rate']  = $last_price == 0 ? StringLib::sprintN(0) : bcdiv(($price - $last_price) * 100, $last_price, 4);

        $cb = Carbon::now()->subHour(24)->toDateTimeString();

        $data['price_max'] = StringLib::sprintN(ExOrderTogetger::whereTeamId($team_id)->where('created_at', '>=', $cb)->max('price') ?: 0, 4);
        $data['price_min'] = StringLib::sprintN(ExOrderTogetger::whereTeamId($team_id)->where('created_at', '>=', $cb)->min('price') ?: 0, 4);
        $data['amount']    = StringLib::sprintN(ExOrderTogetger::whereTeamId($team_id)->where('created_at', '>=', $cb)->sum('success_amount'), 4);

        return json_encode($data);
    }

}

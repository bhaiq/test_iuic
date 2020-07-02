<?php

namespace App\Models;

use App\Jobs\ExJob;
use App\Libs\StringLib;
use App\Services\Service;
use Illuminate\Support\Facades\Redis;
use App\Models\Redis as Red;

/**
 * App\Models\ExTeam
 *
 * @property int $id
 * @property string $name 名称
 * @property int $coin_id_goods 交易币ID
 * @property int $coin_id_legal 法币ID
 * @property int $status 状态：0正常交易，1 维护中，2关闭
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereCoinIdGoods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereCoinIdLegal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExTeam whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Coin $goods
 */
class ExTeam extends Model
{
    protected $table = 'ex_team';
    const STATUS_ON = 0;

    public function goods()
    {
        return $this->hasOne('App\Models\Coin', 'id', 'coin_id_goods');
    }

    public static function curPrice($team_id, $price = 0)
    {
        return ExJob::curPrice($team_id, $price) ?? 1;
    }

    public static function getCurPrice(int $team_id)
    {
        return [
            'price' => ExTeam::curPrice($team_id),
            'price_cny' => bcmul(ExTeam::curPrice($team_id), Account::getRate(), 4),
            'rate' => Account::getRate(),
        ];
    }

    /**
     * @param int $team_id
     * @return ExTeam|ExTeam[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public static function getTeamName(int $team_id)
    {
        return Red::get(Red::KEY_EX_TEAM, $team_id);
    }

    public static function pushList(int $team_id)
    {
        Service::pusher()->allPush('ex_list_sell_' . $team_id, ExOrder::sellList($team_id));
        Service::pusher()->allPush('ex_list_buy_' . $team_id, ExOrder::buyList($team_id));
        self::pushMarket($team_id);
    }

    public static function pushCurPrice($team_id, $price = 0)
    {
        Service::pusher()->allPush('ex_price_' . $team_id, ExTeam::getCurPrice($team_id));
    }


    public static function pushMarket($team_id)
    {
        Service::pusher()->allPush('ex_market_' . $team_id, ExOrder::market($team_id));
        Service::pusher()->allPush('ex_market_0', ExOrder::market(0));
    }

    /**
     * @param int $team_id
     * @return ExTeam|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|void|null
     */
    public static function onOrFail(int $team_id)
    {
        $team = ExTeam::whereId($team_id)->whereStatus(ExTeam::STATUS_ON)->first();
        if (!$team) return abort(400, trans('system.illegal'));
        return $team;
    }
}

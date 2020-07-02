<?php

namespace App\Models;

use App\Libs\StringLib;
use Illuminate\Support\Facades\DB;
use App\Models\Redis as Red;
use \Illuminate\Support\Facades\Redis;

/**
 * App\Models\ExOrder
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property int $team_id 交易对ID
 * @property int $is_matched 是否已主动匹配 0 未匹配 1 已经匹配
 * @property float $price 单价
 * @property float $amount 挂单数量
 * @property float $amount_lost 剩余数量
 * @property float $amount_deal 花费数量
 * @property int $type 交易类型：0 卖，1 买
 * @property int $status 状态：0 未完成，1 已完成，2取消
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property-read mixed $amount_success
 * @property-read mixed $lost_legal
 * @property-read mixed $price_success
 * @property-read \App\Models\ExTeam $team
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereAmountDeal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereAmountLost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExOrder extends Model
{
    const STATUS_INIT = 0;
    const STATUS_FINISHED = 1;
    const STATUS_DEL = 2;
    const TYPE_SELL = 0;
    const TYPE_BUY = 1;
    const IS_MATCHED_NO = 0;
    const IS_MATCHED_YES = 1;
    protected $table = 'ex_order';
    protected $fillable = ['uid', 'team_id', 'price', 'amount', 'deal_us', 'amount_lost', 'status', 'type'];
    //is_matched
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    protected $appends = ['amount_success', 'price_success', 'lost_legal'];

    public static function delLock($id)
    {
        $redis_key = 'Ex_Order_Del' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '订单处理中！');
        Redis::setex($redis_key, 30, 1);
    }

    public static function createLock($id)
    {
        $redis_key = 'Ex_Order_Create' . '_' . $id;
        $redis_qx_key = 'Ex_Order_Qx' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '操作频繁！');
        Redis::setex($redis_key, 30, 1);
        Redis::setex($redis_qx_key, 60, 1);
    }

    public static function getCreateLock($id)
    {
        $redis_key = 'Ex_Order_Qx' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '操作频繁！');
    }

    public static function queueLock($id)
    {
        $redis_key = 'Ex_Order_Queue' . '_' . $id;
        Redis::setex($redis_key, 30, 1);
    }

    public static function getQueueLock($id)
    {
        $redis_key = 'Ex_Order_Queue' . '_' . $id;
        $value = Redis::get($redis_key);
        $value && abort(400, '交易在匹配，暂不能取消！');
    }

    public static function sellList($team_id)
    {
//        $key      = 'EX_SELL_LIST_' . $team_id;
//        $red_sell = Redis::get($key);
//        if (!$red_sell) {
        $data = ExOrder::whereStatus(ExOrder::STATUS_INIT)->whereTeamId($team_id)
            ->whereType(ExOrder::TYPE_SELL)
            ->select('price', DB::raw('SUM(amount_lost) as amount_total'))
            ->groupBy('price')
            ->orderBy('price', 'asc')
            ->take('10')->get()->map(function ($item) use ($team_id) {
                $item->price = StringLib::sprintN($item->price, 4);
                $item->amount_total = StringLib::sprintN($item->amount_total, 4);
                $item->team_name = ExTeam::find($team_id)->name;
                return $item;
            })->sortByDesc('price')->toArray();
        $red_sell = json_encode(array_values($data));
//            Redis::setex($key, 1, $red_sell);
//        }

        return json_decode($red_sell, true);
    }

    /**
     * @param int $id
     * @param int $time
     * @return array
     */
    public static function market(int $id, int $time = 0)
    {
        return Red::get(Red::KEY_EX_MARKET, $id, $time, true);
    }

    public static function buyList($team_id)
    {
//        $key     = 'EX_BUY_LIST_' . $team_id;
//        $red_buy = Redis::get($key);
//        if (!$red_buy) {
        $red_buy = ExOrder::whereStatus(ExOrder::STATUS_INIT)->whereTeamId($team_id)
            ->whereType(ExOrder::TYPE_BUY)
            ->select('price', DB::raw('SUM(amount_lost) as amount_total'))
            ->groupBy('price')
            ->orderBy('price', 'desc')
            ->take('10')->get()->map(function ($item) use ($team_id) {
                // $item->price        = StringLib::sprintN($item->price, 4);
                $item->amount_total = StringLib::sprintN($item->amount_total, 4);
                $item->team_name = ExTeam::find($team_id)->name;
                return $item;
            })->toJson();
//            Redis::setex($key, 1, $red_buy);
//        }

        return json_decode($red_buy, true);
    }

    public function team()
    {
        return $this->belongsTo('App\Models\ExTeam', 'team_id', 'id');
    }

    public function getPriceSuccessAttribute()
    {

        if ($this->amount_success == 0) {
            return 0;
        }
        return bcdiv($this->amount_deal, $this->amount_success, 4);
    }

    public function getLostLegalAttribute()
    {
        return bcsub(bcmul($this->amount, $this->price, 8), $this->amount_deal, 8);
    }

    public function getAmountSuccessAttribute()
    {
        return bcsub($this->amount, $this->amount_lost, 4);
    }

    public function isBuy()
    {
        return $this->type == self::TYPE_BUY;
    }

    public function isSell()
    {
        return $this->type == self::TYPE_SELL;
    }

    public function toArray()
    {
        $data = parent::toArray();

        $this->num_formate($data, [
            'price' => 4,
            'price_success' => 4,
            'amount' => 4,
            'amount_success' => 4,
            'amount_deal' => 4,
        ]);

        return $data;
    }
}

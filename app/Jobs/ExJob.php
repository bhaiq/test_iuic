<?php

namespace App\Jobs;

use App\Libs\StringLib;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\ExOrder;
use App\Models\ExOrderTogetger;
use App\Models\ExTeam;
use App\Models\ExTip;
use App\Models\HoldCoin;
use App\Models\NotTip;
use App\Models\SymbolHistory;
use App\Services\RealTimeBonus;
use App\Services\Service;
use App\Services\TradeRelease;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ExJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $is_buy_finish;
    protected $is_sell_finish;
    protected $order;

    /**
     * ExJob constructor.
     *
     * @param ExOrder $exOrder
     */
    public function __construct(ExOrder $exOrder)
    {
        $this->order = $exOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        \Log::info('=====   开始执行ID为' . $this->order->id . '的队列   =====');
        if ($this->order->is_matched == ExOrder::IS_MATCHED_NO) {

            ExOrder::where('id', $this->order->id)->update(['is_matched' => ExOrder::IS_MATCHED_YES]);

            if ($this->order->type == ExOrder::TYPE_SELL) {
                $this->doOrderBySell($this->order);
            }

            if ($this->order->type == ExOrder::TYPE_BUY) {
                $this->doOrderByBuy($this->order);
            }
        }
        \Log::info('=====   执行ID为' . $this->order->id . '的队列结束   =====');
    }

    public function doOrderByBuy(ExOrder $order_buy)
    {
        do {
            DB::transaction(function () use ($order_buy, &$next) {
                $this->doInit($order_buy);
                $order_sell = ExOrder::where('id', '<', $order_buy->id)->whereStatus(ExOrder::STATUS_INIT)->whereTeamId($order_buy->team_id)->whereType(ExOrder::TYPE_SELL)->where('price', '<=', $order_buy->price)->orderBy('price')->take(1)->lockForUpdate()->get()->first();
                if ($order_sell) {

                    // 增加一个匹配锁
                    ExOrder::queueLock($order_sell->id);

                    //true 为卖单结束 剩余买单继续处理 false为买单结束直接完成
                    if ($order_buy->amount_lost > $order_sell->amount_lost) {

                        $do_amount = $order_sell->amount_lost;
                        $order_buy->amount_lost = bcsub($order_buy->amount_lost, $do_amount, 6);
                        $order_buy->save();

                        $this->is_sell_finish = true;

                        $next = true;
                    } else if ($order_buy->amount_lost == $order_sell->amount_lost) {
                        $do_amount = $order_sell->amount_lost;

                        $this->is_buy_finish = true;
                        $this->is_sell_finish = true;

                        $next = false;
                    } else {
                        $do_amount = $order_buy->amount_lost;

                        $this->is_buy_finish = true;

                        $order_sell->amount_lost = bcsub($order_sell->amount_lost, $do_amount, 6);
                        $order_sell->save();


                        $next = false;
                    }

                    $this->account($order_sell, $order_buy, $do_amount);
                } else {
                    $next = false;
                }

            });
        } while ($next);
        ExTeam::pushList($order_buy->team_id);
    }

    public function doOrderBySell(ExOrder $order_sell)
    {
        do {
            DB::transaction(function () use ($order_sell, &$next) {
                $this->doInit($order_sell);
                $order_buy = ExOrder::where('id', '<', $order_sell->id)->whereStatus(ExOrder::STATUS_INIT)->whereTeamId($order_sell->team_id)->whereType(ExOrder::TYPE_BUY)->where('price', '>=', $order_sell->price)->orderBy('price', 'desc')->take(1)->lockForUpdate()->get()->first();
                if ($order_buy) {

                    // 增加一个匹配锁
                    ExOrder::queueLock($order_buy->id);

                    //true 为买单结束 剩余买单继续处理 false为卖单结束直接完成
                    if ($order_sell->amount_lost > $order_buy->amount_lost) {

                        $do_amount = $order_buy->amount_lost;

                        $order_sell->amount_lost = bcsub($order_sell->amount_lost, $do_amount, 6);
                        $order_sell->save();

                        $this->is_buy_finish = true;

                        $next = true;
                    } else if ($order_sell->amount_lost == $order_buy->amount_lost) {
                        $do_amount = $order_sell->amount_lost;

                        $this->is_buy_finish = true;
                        $this->is_sell_finish = true;

                        $next = false;
                    } else {
                        $do_amount = $order_sell->amount_lost;

                        $this->is_sell_finish = true;

                        $order_buy->amount_lost = bcsub($order_buy->amount_lost, $do_amount, 6);
                        $order_buy->save();

                        $next = false;
                    }

                    $this->account($order_sell, $order_buy, $do_amount);
                } else {
                    $next = false;
                }

            });

        } while ($next);
        ExTeam::pushList($order_sell->team_id);
    }

    protected function doInit()
    {
        $this->is_buy_finish = false;
        $this->is_sell_finish = false;
    }

    public function account(ExOrder $sell, ExOrder $buy, $success_amount)
    {

        if ($this->order->type == ExOrder::TYPE_SELL) {
            $price = $buy->price;
        } else {
            $price = $sell->price;
        }

        self::curPrice($sell->team_id, $price);

        $us_cast = bcmul($success_amount, $price, 8);

        $sell->amount_deal = bcadd($us_cast, $sell->amount_deal, 8);
        $sell->save();

        $buy->amount_deal = bcadd($us_cast, $buy->amount_deal, 8);
        $buy->save();

        if ($this->is_buy_finish) $this->buyFinish($buy);

        if ($this->is_sell_finish) $this->sellFinish($sell);

        $deal = ExOrderTogetger::create([
            'team_id' => $sell->team_id,
            'sell_id' => $sell->id,
            'buy_id' => $buy->id,
            'seller_id' => $sell->uid,
            'buyer_id' => $buy->uid,
            'success_amount' => $success_amount,
            'price' => $price,
            'type' => $this->order->type
        ]);

        \Log::info('记录数据', [$success_amount, $price]);

        $this->hold($sell, $buy, $success_amount, $price);

        $this->pushHistory($deal);

        $this->statHistory($sell, $success_amount);
    }

    /**
     * @param ExOrder $sell
     * @param ExOrder $buy
     */
    public function hold(ExOrder $sell, ExOrder $buy, $success_amount, $price)
    {
        try {

            $sell_hold = HoldCoin::find($sell->uid);
            if (!$sell_hold) {
                $sell_hold = HoldCoin::create(['uid' => $sell->uid]);
            }

            if ($sell_hold->amount <= $success_amount) {
                $sell_hold->price = 0;
                $sell_hold->amount = 0;
                $sell_hold->save();
            } else {
                if ($sell_hold->price && $sell_hold->amount) {
                    $sell_hold->price = ($sell_hold->price * $sell_hold->amount - $success_amount * $price) / ($sell_hold->amount - $success_amount);
                    $sell_hold->amount -= $success_amount;
                    $sell_hold->save();
                } else {
                    $sell_hold->price = 0;
                    $sell_hold->amount = 0;
                    $sell_hold->save();
                }
            }

            $buy_hold = HoldCoin::find($buy->uid);
            if (!$buy_hold) {
                $buy_hold = HoldCoin::create(['uid' => $buy->uid]);
            }

            $buy_hold->price = ($buy_hold->price * $buy_hold->amount + $success_amount * $price) / ($buy_hold->amount + $success_amount);
            $buy_hold->amount += $success_amount;
            $buy_hold->save();

            \Log::info('统计用户持币顺利');

        } catch (\Exception $e) {

            \Log::info('统计用户持币出现异常，但是不进行处理');

        }

    }

    protected function buyFinish(ExOrder $buy)
    {
        $team = ExTeam::find($buy->team_id);
        $buy->amount_lost = 0;
        $buy->status = ExOrder::STATUS_FINISHED;
        $buy->save();

//        $tip = max(bcmul($buy->amount, 0.003, 7), 0.01);
//
//        $get_coin = bcsub($buy->amount, $tip, 7);

        (new TradeRelease())->release($buy->uid, $buy->amount);

        $get_coin = $buy->amount;

        Account::whereUid($buy->uid)->whereCoinId($team->coin_id_goods)->whereType(Account::TYPE_CC)->increment('amount', $get_coin);

        Service::account()->createLog($buy->uid, $team->coin_id_goods, $get_coin, AccountLog::SCENE_EX_IN);

        $amount_freeze = bcmul($buy->amount, $buy->price, 8);

        Account::whereUid($buy->uid)->whereCoinId($team->coin_id_legal)->whereType(Account::TYPE_CC)->decrement('amount_freeze', $amount_freeze);
        Service::account()->createLog($buy->uid, $team->coin_id_legal, $amount_freeze, AccountLog::SCENE_EX_OUT);

        if ($buy->lost_legal != 0) {
            Account::whereUid($buy->uid)->whereCoinId($team->coin_id_legal)->whereType(Account::TYPE_CC)->increment('amount', $buy->lost_legal);
            Service::account()->createLog($buy->uid, $team->coin_id_legal, $buy->lost_legal, AccountLog::SCENE_EX_BACK);
        }
    }

    public function sellFinish(ExOrder $sell)
    {
        $team = ExTeam::find($sell->team_id);
        $sell->amount_lost = 0;
        $sell->status = ExOrder::STATUS_FINISHED;
        $sell->save();

//        $amount = bcmul($sell->price, $sell->amount, 8);
        \Log::info('new usdt '.$sell->id);
        $amount = $sell->amount_deal;

        // 判断该用户需不需要收手续费
        $notTip = NotTip::pluck('uid')->toArray();

        if (!in_array($sell->uid, $notTip)) {

            $tipBl = config('trade.tip_bl');
            $tip = max(bcmul($amount, $tipBl, 8), 0.01);

            $tipBonusBl = config('trade.tip_bonus_bl');
            $tip2 = max(bcmul($amount, $tipBonusBl, 8), 0.01);

            // 记录手续费信息
            ExTip::addTip($tip, $tip2, $sell->id);

            (new RealTimeBonus())->handle($tip2);

            $get_coin = bcsub($amount, $tip, 8);

        } else {

            \Log::info('用户不需要收手续费');

            $get_coin = $amount;

        }


        Account::whereUid($sell->uid)->whereCoinId($team->coin_id_legal)->whereType(Account::TYPE_CC)->increment('amount', $get_coin);
        Account::whereUid($sell->uid)->whereCoinId($team->coin_id_goods)->whereType(Account::TYPE_CC)->decrement('amount_freeze', $sell->amount);
        Service::account()->createLog($sell->uid, $team->coin_id_legal, $get_coin, AccountLog::SCENE_EX_IN);
        Service::account()->createLog($sell->uid, $team->coin_id_goods, $sell->amount, AccountLog::SCENE_EX_OUT);
    }

    public static function curPrice($team_id, $price = 0)
    {
        $key = 'EX_PRICE_' . $team_id;
        $red_price = Redis::get($key);
        if ($price != 0 && $red_price != $price) {
            $red_price = StringLib::sprintN($price, 4);
            Redis::setex($key, 604800, $red_price);
            ExTeam::pushCurPrice($team_id, $red_price);
        }
        return $red_price;
    }

    /**
     * @param ExOrder $order
     * @param         $amount
     */
    protected function statHistory(ExOrder $order, $amount)
    {
        $sym = SymbolHistory::getByCreated($order->team_id);
        $sym->h = max($sym->h, $order->price);
        $sym->l = $sym->l == 0 ? $order->price : min($sym->l, $order->price);
        $sym->c = $order->price;
        $sym->v += $amount;
        $sym->save();
    }

    /**
     * @param ExOrderTogetger $deal
     * @throws \Pusher\PusherException
     */
    protected function pushHistory(ExOrderTogetger $deal)
    {
        Service::pusher()->allPush('history_order_' . $this->order->team_id, $deal->toArray());
        Service::pusher()->allPush('history_price_' . $this->order->team_id, ExOrderTogetger::historyPrice($this->order->team_id, 0));
    }
}

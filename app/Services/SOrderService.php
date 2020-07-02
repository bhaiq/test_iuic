<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Slog;
use App\Models\SOrderBuy as Buy;
use App\Models\SOrderSell as Sell;
use App\Models\Pure\SOrderBuy;
use App\Models\Pure\SOrderLog;
use App\Models\Pure\SOrderSell;
use App\Models\UsLog;
use Illuminate\Support\Facades\DB;

class SOrderService
{
    public function doOrderByBuy(SOrderBuy $order_buy)
    {
        do {
            DB::transaction(function () use ($order_buy, &$next) {
                $order_buy->refresh();
                $order_sell = SOrderSell::whereIsOver(Sell::IS_OVER_NOT)->orderBy('id')->take(1)->lockForUpdate()->get()->first();
                if ($order_sell) {
                    //true 为卖单结束 剩余买单继续处理 false为买单结束直接完成
                    if ($order_buy->amount_lost > $order_sell->amount_lost) {
                        $do_amount = $order_sell->amount_lost;

                        SOrderBuy::mathLostAmount(bcsub($order_buy->amount_lost, $do_amount, 6), $order_buy->id);

                        $order_sell->amount_lost = 0;
                        $order_sell->is_over     = Sell::IS_OVER_YES;
                        $order_sell->save();

                        $next = true;
                    } else if ($order_buy->amount_lost == $order_sell->amount_lost) {
                        $do_amount = $order_sell->amount_lost;

                        $order_buy->amount_lost = 0;
                        $order_buy->is_over     = Buy::IS_OVER_YES;
                        $order_buy->save();

                        $order_sell->amount_lost = 0;
                        $order_sell->is_over     = Sell::IS_OVER_YES;
                        $order_sell->save();

                        $next = false;
                    } else {
                        $do_amount = $order_buy->amount_lost;

                        $order_buy->amount_lost = 0;
                        $order_buy->is_over     = Buy::IS_OVER_YES;
                        $order_buy->save();

                        SOrderSell::mathLostAmount(bcsub($order_sell->amount_lost, $do_amount, 6), $order_sell->id);

                        $next = false;
                    }

                    $this->account($order_sell->uid, $order_buy->uid, $do_amount);
                } else {
                    $next = false;
                }

            });
        } while ($next);
    }

    public function doOrderBySell(SOrderSell $order_sell)
    {
        do {
            DB::transaction(function () use ($order_sell, &$next) {
                $order_sell->refresh();
                $order_buy = SOrderBuy::whereIsOver(Buy::IS_OVER_NOT)->orderBy('id')->take(1)->lockForUpdate()->get()->first();
                if ($order_buy) {
                    //true 为买单结束 剩余买单继续处理 false为卖单结束直接完成
                    if ($order_sell->amount_lost > $order_buy->amount_lost) {

                        $do_amount = $order_buy->amount_lost;

                        SOrderSell::mathLostAmount(bcsub($order_sell->amount_lost, $do_amount, 6), $order_sell->id);

                        $order_buy->amount_lost = 0;
                        $order_buy->is_over     = Sell::IS_OVER_YES;
                        $order_buy->save();

                        $next = true;
                    } else if ($order_sell->amount_lost == $order_buy->amount_lost) {
                        $do_amount = $order_sell->amount_lost;

                        $order_buy->amount_lost = 0;
                        $order_buy->is_over     = Buy::IS_OVER_YES;
                        $order_buy->save();

                        $order_sell->amount_lost = 0;
                        $order_sell->is_over     = Sell::IS_OVER_YES;
                        $order_sell->save();

                        $next = false;
                    } else {
                        $do_amount = $order_sell->amount_lost;

                        $order_sell->amount_lost = 0;
                        $order_sell->is_over     = Buy::IS_OVER_YES;
                        $order_sell->save();

                        SOrderBuy::mathLostAmount(bcsub($order_buy->amount_lost, $do_amount, 6), $order_buy->id);

                        $next = false;
                    }

                    $this->account($order_sell->uid, $order_buy->uid, $do_amount);
                } else {
                    $next = false;
                }

            });
        } while ($next);
    }

    public function account(int $sell_uid, int $buy_uid, $amount)
    {
        $user_sell = Account::whereUid($sell_uid)->first();
        $user_sell->decrement('sell_S', $amount);
        $user_sell->increment('US', $amount);
        Service::account()->createSlog($sell_uid, $amount, Slog::SCENE_SELL);
        Service::account()->createUsLog($sell_uid, $amount, UsLog::SCENE_SELL_S);

        $user_buy = Account::whereUid($buy_uid)->first();
        $user_buy->increment('S', $amount);
        $user_buy->decrement('lock_US', $amount);
        Service::account()->createSlog($buy_uid, $amount, Slog::SCENE_BUY);
        Service::account()->createUsLog($buy_uid, $amount, UsLog::SCENE_BUY_S);

        SOrderLog::create(['sell_id' => $sell_uid, 'buy_id' => $buy_uid, 'amount' => $amount, 'expansion' => [
            'buyer'  => ['uid' => $user_buy->user->id, 'nickname' => $user_buy->user->nickname, 'avatar' => $user_buy->user->avatar],
            'seller' => ['uid' => $user_sell->user->id, 'nickname' => $user_sell->user->nickname, 'avatar' => $user_sell->user->avatar]
        ]]);
    }

    public function doDelBuy(SOrderBuy $orderBuy)
    {
        DB::transaction(function () use ($orderBuy) {
            $amount = $orderBuy->amount_lost;
            Service::auth()->getUser()->account()->decrement('lock_US', $amount);
            Service::auth()->getUser()->account()->increment('US', $amount);
            $orderBuy->is_over = Buy::IS_OVER_DEL;
            $orderBuy->save();
        });
    }

    public function doDelSell(SOrderSell $orderSell)
    {
        DB::transaction(function () use ($orderSell, &$amount) {
            $amount = $orderSell->amount_lost;
            Service::auth()->getUser()->account()->decrement('sell_S', $amount);
            Service::auth()->getUser()->account()->increment('free_s', $amount);
            $orderSell->is_over = Sell::IS_OVER_DEL;
            $orderSell->save();
        });
    }
}

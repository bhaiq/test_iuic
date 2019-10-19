<?php

namespace App\Http\Controllers;

use App\Jobs\ExJob;
use App\Models\AccountLog;
use App\Models\ExOrder;
use App\Models\ExTeam;
use App\Models\ExTip;
use App\Models\NotTip;
use App\Models\Redis;
use App\Services\RealTimeBonus;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExOrderSellController extends Controller
{
    public function _list($team_id)
    {
        return $this->response(ExOrder::sellList($team_id));
    }

    public function create($team_id, Request $request)
    {

        Service::auth()->isLoginOrFail();

        $ccMinTime = config('trade.cc_min_time', '00:00:00');
        $ccMaxTime = config('trade.cc_max_time', '23:59:59');
        // 增加时间限制
        if(!Carbon::now()->between(Carbon::create(now()->toDateString(). ' ' . $ccMinTime),Carbon::create(now()->toDateString(). ' ' . $ccMaxTime))){
            $this->responseError('该时间段不能交易');
        }

        $team = ExTeam::onOrFail($team_id);

        $amount = Service::auth()->account($team->coin_id_goods)->amount;

        $this->validate($request->all(), [
            'amount' => 'required|numeric|min:1|max:' . $amount,
            'price'  => 'required|numeric|min:0.01',
        ]);

        // 获取昨日收盘价格
        $lastPrice = Redis::get(Redis::KEY_EX_LAST_PRICE, $team_id);

        // 增加价格限制
        $todayTradeMax = bcmul(bcadd(1, config('trade.today_trade_max_bl'), 4), $lastPrice, 4);
        $todayTradeMin = bcmul(bcsub(1, config('trade.today_trade_min_bl'), 4), $lastPrice, 4);
        if($request->get('price') > $todayTradeMax || $request->get('price') < $todayTradeMin){
            $this->responseError('价格超出当日限制');
        }

        // 获取实时行情行情
        $realPrice = ExTeam::getCurPrice($team_id);

        // 获取当次最大涨幅和最低涨幅
        $cur_trade_max_bl = config('trade.cur_trade_max_bl');
        $cur_trade_min_bl = config('trade.cur_trade_min_bl');
        if(bcmul(bcadd($cur_trade_max_bl, 1, 8), $realPrice['price'], 8) < $request->get('price') || bcmul(bcsub(1, $cur_trade_min_bl, 8), $realPrice['price'], 8) > $request->get('price')){
            $this->responseError('单价超过限制');
        }

        ExOrder::createLock(Service::auth()->getUser()->id);

        $user   = Service::auth()->getUser();
        $uid    = $user->id;
        $price  = $request->input('price');
        $amount = $request->input('amount');


        DB::transaction(function () use ($price, $amount, $team, $uid, &$order_sell) {
            $amount_lost = $amount;
            $type        = ExOrder::TYPE_SELL;
            $team_id     = $team->id;
            $order_sell  = ExOrder::create(compact('uid', 'team_id', 'price', 'amount', 'amount_lost', 'type'));
            Service::auth()->account($team->coin_id_goods)->decrement('amount', $amount);
            Service::auth()->account($team->coin_id_goods)->increment('amount_freeze', $amount);
        });

        dispatch(new ExJob($order_sell));

        return $this->response([Service::auth()->account($team->coin_id_legal)->toArray(), Service::auth()->account($team->coin_id_goods)->toArray()]);
    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();
        $user    = Service::auth()->getUser();
        $ex_sell = ExOrder::findOrFail($id);

        if ($ex_sell->type != ExOrder::TYPE_SELL) return $this->responseError('system.illegal');
        if ($ex_sell->uid != $user->id) return $this->responseError('system.illegal');
        if ($ex_sell->status != ExOrder::STATUS_INIT) return $this->responseError('ex_us.controller.has_done');

        ExOrder::getCreateLock(Service::auth()->getUser()->id);
        ExOrder::delLock($ex_sell->id);
        ExOrder::getQueueLock($ex_sell->id);

        DB::transaction(function () use ($ex_sell, $user, &$team) {
            $ex_sell->status = ExOrder::STATUS_DEL;
            $ex_sell->save();
            $team = ExTeam::find($ex_sell->team_id);

            if ($ex_sell->amount_deal != 0) {

                // 判断该用户需不需要收手续费
                $notTip = NotTip::pluck('uid')->toArray();

                if(!in_array($ex_sell->uid, $notTip)){

                    $tipBl = config('trade.tip_bl');
                    $tip = max(bcmul($ex_sell->amount_deal, $tipBl, 8), 0.01);

                    $tipBonusBl = config('trade.tip_bonus_bl');
                    $tip2 = max(bcmul($ex_sell->amount_deal, $tipBonusBl, 8), 0.01);

                    // 记录手续费信息
                    ExTip::addTip($tip, $tip2, $ex_sell->id);

                    (new RealTimeBonus())->handle($tip2);

                    $get_coin = bcsub($ex_sell->amount_deal, $tip, 7);

                }else{

                    \Log::info('用户不需要收手续费');

                    $get_coin = $ex_sell->amount_deal;

                }

                //                Service::account()->exTip($tip, 'US');

                Service::auth()->account($team->coin_id_legal)->increment('amount', $get_coin);
                Service::account()->createLog($user->id, $team->coin_id_legal, $get_coin, AccountLog::SCENE_EX_BACK);
            }

            Service::auth()->account($team->coin_id_goods)->increment('amount', $ex_sell->amount_lost);
            Service::auth()->account($team->coin_id_goods)->decrement('amount_freeze', $ex_sell->amount);

            Service::account()->createLog($user->id, $team->coin_id_goods, $ex_sell->amount_lost, AccountLog::SCENE_EX_BACK);
        });

        ExTeam::pushList($ex_sell->team_id);

        return $this->response([Service::auth()->account($team->coin_id_legal)->toArray(), Service::auth()->account($team->coin_id_goods)->toArray()]);
    }
}

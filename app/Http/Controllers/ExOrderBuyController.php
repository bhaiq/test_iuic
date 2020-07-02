<?php

namespace App\Http\Controllers;

use App\Jobs\ExJob;
use App\Models\AccountLog;
use App\Models\ExOrder;
use App\Models\Account;
use App\Models\User;
use App\Models\ExTeam;
use App\Models\Redis;
use App\Services\Service;
use App\Services\TradeRelease;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExOrderBuyController extends Controller
{
    public function _list($team_id)
    {
        return $this->response(ExOrder::buyList($team_id));
    }

    public function selfList($team_id, Request $request)
    {
        Service::auth()->isLoginOrFail();
        $user   = Service::auth()->getUser();
        $status = $request->get('status', 0);
        $status = explode(',', $status);
        $type   = $request->get('type', 0);
        $type   = explode(',', $type);

        if ($team_id == 0) {
            $list = ExOrder::whereUid($user->id)
                ->whereIn('status', $status)
                ->whereIn('type', $type)
                ->orderBy('id', 'desc')->paginate(10);
            foreach ($list->items() as $item) {
                $item->team_name = ExTeam::getTeamName($item->team_id)->name;
            }
        } else {
            $list      = ExOrder::whereUid($user->id)
                ->whereTeamId($team_id)
                ->whereIn('status', $status)
                ->whereIn('type', $type)
                ->orderBy('id', 'desc')->paginate(10);
            $team_name = ExTeam::getTeamName($team_id)->name;
            foreach ($list->items() as $item) {
                $item->team_name = $team_name;
            }
        }


        return $this->response($list->toArray());
    }

    public function create($team_id, Request $request)
    {
        \Log::info('开始1');
        Service::auth()->isLoginOrFail();

        $ccMinTime = config('trade.cc_min_time', '00:00:00');
        $ccMaxTime = config('trade.cc_max_time', '23:59:59');
        // 增加时间限制
        if(!Carbon::now()->between(Carbon::create(now()->toDateString(). ' ' . $ccMinTime),Carbon::create(now()->toDateString(). ' ' . $ccMaxTime))){
            $this->responseError('该时间段不能交易');
        }

        $price = $request->input('price', 1000000000);

        $team = ExTeam::onOrFail($team_id);

        $amount = Service::auth()->account($team->coin_id_legal)->amount;
      
        $this->validate($request->all(), [
            'price'  => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:1|max:' . bcdiv($amount, $price, 4),

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

        DB::transaction(function () use ($amount, $price, $team, $uid, &$order_buy) {
            $amount_lost = $amount;
            $lost_legal  = bcmul($amount, $price, 4);
            $type        = ExOrder::TYPE_BUY;
            $team_id     = $team->id;
            $order_buy   = ExOrder::create(compact('uid', 'team_id', 'price', 'amount', 'amount_lost', 'type'));

            Service::auth()->account($team->coin_id_legal)->decrement('amount', $lost_legal);
            Service::auth()->account($team->coin_id_legal)->increment('amount_freeze', $lost_legal);

        });

        dispatch(new ExJob($order_buy));
        \Log::info('结束1');
        return $this->response([Service::auth()->account($team->coin_id_legal)->toArray(), Service::auth()->account($team->coin_id_goods)->toArray()]);
    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();
        $user   = Service::auth()->getUser();
        $ex_buy = ExOrder::findOrFail($id);

        if ($ex_buy->type != ExOrder::TYPE_BUY) return $this->responseError('system.illegal');
        if ($ex_buy->uid != $user->id) return $this->responseError('system.illegal');
        if ($ex_buy->status != ExOrder::STATUS_INIT) return $this->responseError('ex_us.controller.has_done');

        ExOrder::getCreateLock(Service::auth()->getUser()->id);
        ExOrder::delLock($ex_buy->id);
        ExOrder::getQueueLock($ex_buy->id);

        $team = ExTeam::find($ex_buy->team_id);
        DB::transaction(function () use ($ex_buy, $user, $team) {
            $ex_buy->status = ExOrder::STATUS_DEL;
            $ex_buy->save();

            if ($ex_buy->amount != $ex_buy->amount_lost) {
                $deal = bcsub($ex_buy->amount, $ex_buy->amount_lost, 8);

//                $tip  = max(bcmul($deal, 0.003, 7), 0.01);
//
//                $get_coin = bcsub($deal, $tip, 7);

                (new TradeRelease())->release($ex_buy->uid, $ex_buy->amount_success);

                $get_coin = $deal;

                Service::auth()->account($team->coin_id_goods)->increment('amount', $get_coin);

                Service::account()->createLog($ex_buy->uid, $team->coin_id_goods, $get_coin, AccountLog::SCENE_EX_IN);
            }

            $lost_legal = bcmul($ex_buy->amount, $ex_buy->price, 4);
            Service::auth()->account($team->coin_id_legal)->increment('amount', $ex_buy->lost_legal);
            Service::auth()->account($team->coin_id_legal)->decrement('amount_freeze', $lost_legal);
            Service::account()->createLog($ex_buy->uid, $team->coin_id_legal, $ex_buy->lost_legal, AccountLog::SCENE_EX_DEL);
        });

        ExTeam::pushList($ex_buy->team_id);

        return $this->response([Service::auth()->account($team->coin_id_legal)->toArray(), Service::auth()->account($team->coin_id_goods)->toArray()]);

    }
    
    public function jl_jiedong(Request $request){
        return "停止服务";

        $account = $request->get('account');
        $uid = User::where('new_account',$account)->value('id');
        // return $uid;
        $cetype = $request->get('type');//1iuic(卖),2usdt(买)
        $num = $request->get('num');//退回数额
        if($cetype == 1){
            //先判断冻结月是否足够
            if($num > Account::where('uid',$uid)->where('coin_id',1)->where('type',0)->value('amount_freeze')){
                return "usdt可释放冻结余额不足";
            }
                //coin_id:1usdt 2iuic  type  type:0币币账户 1法币账户
                Account::where('uid',$uid)->where('coin_id',1)->where('type',0)->increment('amount', $num);
                Account::where('uid',$uid)->where('coin_id',1)->where('type',0)->decrement('amount_freeze', $num);
                Service::account()->createLog($uid, 1, $num, AccountLog::SCENE_EX_DEL);
                return "退回usdt执行成功";
        }elseif($cetype == 2){
            if($num > Account::where('uid',$uid)->where('coin_id',2)->where('type',0)->value('amount_freeze')){
                return "iuic可释放冻结余额不足";
            }
                Account::where('uid',$uid)->where('coin_id',2)->where('type',0)->increment('amount', $num);
                Account::where('uid',$uid)->where('coin_id',2)->where('type',0)->decrement('amount_freeze', $num);
                Service::account()->createLog($uid, 2, $num, AccountLog::SCENE_EX_BACK);
                return "退回iuic执行成功";
        }

    }
}

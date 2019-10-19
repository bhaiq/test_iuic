<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ExOrder;
use App\Models\ExOrderTogetger;
use App\Models\ExTeam;
use App\Models\ReleaseOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\SymbolHistory;


class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
//        $this->min();
        $this->getWallet();
//        $order = ExOrderTogetger::whereTeamId(1)->whereDate('created_at', Carbon::yesterday()->toDateString())->orderBy('id', 'desc')->first();
//        dump($order->toArray());die;
//
//        $timestamp=1565452800;
//        do {
//            (date('i',$timestamp) % 15 == 0) && $this->bigLine(SymbolHistory::TYPE_15,$timestamp);
//            (date('i',$timestamp) == 0) && $this->bigLine(SymbolHistory::TYPE_1H,$timestamp);
//            (date('i',$timestamp) == 0 && date('H',$timestamp) % 4 == 0) && $this->bigLine(SymbolHistory::TYPE_4H,$timestamp);
//            (date('i',$timestamp) == 0 && date('H',$timestamp) == 0) && $this->bigLine(SymbolHistory::TYPE_1D,$timestamp);
//            $timestamp+=900;
//        }while($timestamp<1565594550);
    }
    public function min($id=100){
        $first = SymbolHistory::whereType(1)->where('id','>',$id)->orderBy('id','asc')->first();
        if($first){
            $last = SymbolHistory::whereType(1)->where('id','<',$first->id)->orderBy('id','desc')->first();

            $first->o=$last->c;
            $first->save();
            dump($first->id);
            $this->min($first->id);

        }
    }
    public function bigLine(int $type, $timestamp)
    {
        $ex_teams = ExTeam::all();
        $s_type   = bcsub($type, 1, 0);

        $cb = Carbon::createFromTimestamp($timestamp);
        switch ($type) {
            case SymbolHistory::TYPE_15:
                $st = $cb->subMinute(15)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_1H:
                $st = $cb->subMinute(60)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_4H:
                $st = $cb->subHour(4)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_1D:
                $st = $cb->subHour(24)->toDateTimeString();
                break;
        }

        $et = Carbon::createFromDate(date('Y-m-d H:i',$timestamp))->toDateTimeString();

        if ($type == SymbolHistory::TYPE_1D) {
            $created_at = $st;
        } else {
            $created_at = $et;
        }

        foreach ($ex_teams as $ex_team) {

            $h = 0;
            $l = 0;
            $o = 0;
            $c = 0;

            $team_id = $ex_team->id;

            $sym = SymbolHistory::whereType($type)->whereTeamId($team_id)->orderBy('id', 'desc')->first();

            $s_sym = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->get();

            $v = $s_sym->sum('v') ?: 0;

            if ($v) {
                $h = $s_sym->max('h');
                $l = $s_sym->min('l');
                if ($l==0.179200){
                    dd($s_sym->toArray());
                }
                dump($l.'_max:'.$h);
                if ($sym->toArray()) {
                    $o = $sym->c;
                } else {
                    $o = $s_sym->sortBy('id')->first()->o ?: 0;
                }
                $c = $s_sym->sortByDesc('id')->first()->c ?: 0;
            } else {
                if ($sym->toArray()) {
                    $h = $sym->c;
                    $l = $sym->c;
                    $o = $sym->c;
                    $c = $sym->c;
                }
            }

//            SymbolHistory::create(compact('created_at', 'team_id', 'h', 'l', 'o', 'c', 'v', 'type'));
        }
    }

    public function getWallet()
    {
        // 获取用户钱包余额
        $uNum = Account::where('coin_id', 1)->sum('amount');
        $iNum = Account::where('coin_id', 2)->sum('amount');

        // 获取用户钱包冻结余额
        $uFrozenNum = Account::where('coin_id', 1)->sum('amount_freeze');
        $iFrozenNum = Account::where('coin_id', 2)->sum('amount_freeze');

        // 获取买单交易中的IUIC余额
        $buyTradeNum = ExOrder::where(['status' => 0, 'type' => 1])->sum(\DB::raw('(amount - amount_lost)'));

        // 获取买单交易中的IUIC余额
        $sellTradeNum = ExOrder::where(['status' => 0, 'type' => 0])->sum(\DB::raw('(amount - amount_lost)'));

        // 获取买单交易中的USDT余额
        $buyUsdtNum = ExOrder::where(['status' => 0, 'type' => 1])->sum('amount_deal');

        // 获取卖单交易中的USDT余额
        $sellUsdtNum = ExOrder::where(['status' => 0, 'type' => 0])->sum('amount_deal');

        $data = [
            'unum' => $uNum,
            'inum' => $iNum,
            'u_frozen_num' => $uFrozenNum,
            'i_frozen_num' => $iFrozenNum,
            'buy_trade_num' => $buyTradeNum,
            'sell_trade_num' => $sellTradeNum,
            'buy_usdt_num' => $buyUsdtNum,
            'sell_usdt_num' => $sellUsdtNum,
        ];

        dump(['lj_release' => ReleaseOrder::sum('release_num')]);
        dd($iNum + $iFrozenNum + $buyTradeNum - $sellTradeNum);

    }
}

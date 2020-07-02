<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ExOrder;
use App\Models\ReleaseOrder;
use App\Models\WalletCollect;
use Illuminate\Console\Command;

class AddWalletCollect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addWalletCollect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '余额收集';

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

        \Log::info('==========  每小时记录钱包余额  ==========');

        $this->toRecord();

        \Log::info('==========  每小时记录钱包余额  ==========');

    }

    private function toRecord()
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
            'lj_release' => ReleaseOrder::sum('release_num'),
        ];

        WalletCollect::create($data);

    }

}

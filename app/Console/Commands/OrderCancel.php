<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\OtcOrder;
use App\Models\OtcPublishBuy;
use App\Models\OtcPublishSell;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OrderCancel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order_cancel';

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
        $this->cancelSell();
        $this->cancelBuy();
    }

    public function cancelSell()
    {
        $order = OtcOrder::whereIsPay(false)->where('type', OtcOrder::TYPE_SELL)
            ->whereIn('status', [OtcOrder::STATUS_INIT, OtcOrder::STATUS_OVER])
            ->whereTime('created_at', '<', Carbon::createFromTimestamp(time() - 15 * 60))
            ->lockForUpdate()
            ->get();
        $order->each(function ($item) {
            $item->otcPublishSell()->increment('amount_lost', $item->amount);
            $publish          = $item->otcPublishSell;
            $publish->is_over = OtcPublishSell::IS_OVER_NOT;
            $publish->save();
            $item->status = OtcOrder::STATUS_CANCEL;
            $item->save();
        });
    }

    public function cancelBuy()
    {
        $order = OtcOrder::whereIsPay(false)->where('type', OtcOrder::TYPE_BUY)
            ->whereTime('created_at', '<', Carbon::createFromTimestamp(time() - 15 * 60))
            ->whereIn('status', [OtcOrder::STATUS_INIT, OtcOrder::STATUS_OVER])
            ->lockForUpdate()
            ->get();
        $order->each(function ($item) {
            $amount = $item->amount;
            Account::whereUid($item->uid)->whereCoinId($item->coin_id)->whereType(Account::TYPE_LC)->decrement('amount_freeze', $amount);
            Account::whereUid($item->uid)->whereCoinId($item->coin_id)->whereType(Account::TYPE_LC)->increment('amount', $amount);
            $item->otcPublishBuy()->increment('amount_lost', $item->amount);
            $publish          = $item->otcPublishBuy;
            $publish->is_over = OtcPublishBuy::IS_OVER_NOT;
            $publish->save();
            $item->status = OtcOrder::STATUS_CANCEL;
            $item->save();
        });
    }
}

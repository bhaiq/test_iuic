<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\ExTip;
use App\Models\KuangjiLinghuo;
use App\Models\KuangjiOrder;
use App\Models\KuangjiUserPosition;
use App\Models\ReleaseOrder;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use App\Services\KuangjiBonus;
use Illuminate\Console\Command;

class KuangjiRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kuangjiRelease';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '矿机释放';

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

        \Log::info('=====  开始矿机定时释放  =====');

//        $this->toRelease();

        \Log::info('=====  矿机定时释放结束  =====');

        \Log::info('=====  开始灵活矿机定时释放  =====');

        $this->toLhRelease();

        \Log::info('=====  灵活矿机定时释放结束  =====');

    }

    // 普通矿机释放
    private function toRelease()
    {

        // 获取现在正在跑的矿机信息
        $kup = KuangjiUserPosition::with(['order', 'kuangji'])->where('order_id', '>', 0)->where('kuangji_id', '>', 0)->get();
        if ($kup->isEmpty()) {
            \Log::info('没有正在跑的矿机');
            return false;
        }

        foreach ($kup as $k => $v) {

            if (!isset($v->order) || empty($v->order)) {
                \Log::info('矿机订单信息有误', ['kup_id' => $v->id]);
                continue;
            }

            if (!isset($v->kuangji) || empty($v->kuangji)) {
                \Log::info('矿机信息有误', ['kup_id' => $v->id]);
                continue;
            }

            $start = strtotime(substr($v->order->created_at, 0, 10) . ' 00:00:00');
            $cur = time();

            \DB::beginTransaction();
            try {

                // 当矿机订单过期的时候进行处理
                if (($start * 181 * 24 * 3600) < $cur) {

                    // 改变订单状态
                    KuangjiOrder::where('id', $v->order_id)->update(['status', 0]);

                    // 改变矿位状态
                    KuangjiUserPosition::where('id', $v->id)->update(['order_id' => 0, 'kuangji_id' => 0]);

                } else {

                    // 获取本次的矿机释放数
                    $oneNum = ($v->kuangji->suanli) > 0 ? $v->kuangji->suanli : 0;
                    if ($oneNum <= 0) {
                        \Log::info('获取到的算力信息异常', ['kup_id' => $v->id]);
                        continue;
                    }

                    // 订单释放
                    $this->orderRelease($v->uid, $oneNum, $v->order_id);

                }

                \DB::commit();

            } catch (\Exception $exception) {

                \DB::rollBack();

                \Log::info('矿机矿池释放异常', ['kup_id' => $v->id]);

                continue;

            }

        }

    }

    // 灵活矿机释放
    private function toLhRelease()
    {

        // 获取正在跑的灵活矿机信息
        $kjl = KuangjiLinghuo::where('created_at', '<', now()->toDateString() . ' 00:00:00')->where('num', '>', 0)->get();

        if ($kjl->isEmpty()) {
            \Log::info('没有合适的灵活矿机信息');
            return false;
        }

        foreach ($kjl as $v) {

            $start = strtotime(substr($v->start_time, 0, 10) . ' 00:00:00');
            $cur = time();

            \DB::beginTransaction();
            try {

                // 当矿机订单过期的时候进行处理
                if (($start * 181 * 24 * 3600) < $cur) {

                    $uData = [
                        'num' => 0,
                        'start_time' => null,
                    ];

                    // 改变矿位状态
                    KuangjiLinghuo::where('id', $v->order_id)->update($uData);

                } else {

                    // 获取算力
                    $suanli = config('kuangji.kuangji_flexible_suanli_bl', 0.02);

                    $maxLh = config('kuangji.kuangji_flexible_max', 200);

                    // 获取本次能释放的数量
                    $oldNum = ($v->num) > $maxLh ? $maxLh : $v->num;

                    $oneNum = bcmul($oldNum, $suanli, 4);
                    if ($oneNum <= 0) {
                        \Log::info('灵活算力有异常', ['oneNum' => $oneNum, 'id' => $v->id]);
                        continue;
                    }

                    // 订单释放
                    $this->orderRelease($v->uid, $oneNum, $v->id, 'kuangji_linghuo', '灵活矿机释放');

                }

                \DB::commit();

            } catch (\Exception $exception) {

                \DB::rollBack();

                \Log::info('灵活矿机矿池释放异常', ['kup_id' => $v->id]);

                continue;

            }

        }

    }

    private function orderRelease($uid, $totalNum, $orderId = 0, $dyTable = 'kuangji_order', $exp = '矿机释放')
    {

        \Log::info('订单释放进来的值', ['uid' => $uid, 'totalNum' => $totalNum, 'orderId' => $orderId]);

        // 获取用户矿池信息
        $ui = UserInfo::where('uid', $uid)->first();
        if (!$ui) {
            \Log::info('获取不到用户矿池信息');
            return false;
        }

        // 获取释放比例
        $releaseTipBl = config('kuangji.kuangji_release_bl', 0.3);

        // 如果本次释放能完全释放矿池
        if (bcadd($ui->release_total, $totalNum, 8) >= $ui->buy_total) {

            $sjNum = bcsub($ui->buy_total, $ui->release_total, 8);

            $totalNum = $sjNum;

        }

        // 判断本次释放是否小于0
        if ($totalNum <= 0) {
            \Log::info('没有可释放的矿池，直接结束');
            return false;
        }

        $tipNum = bcmul($totalNum, $releaseTipBl, 8);

        // 记录手续费信息
        ExTip::addTip($tipNum, $tipNum, $orderId, 1);

        $num = bcsub($totalNum, $tipNum, 8);

        // 释放矿池数增加
        UserInfo::where('uid', $uid)->increment('release_total', $totalNum);

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');

        // 用户余额增加
        Account::addAmount($uid, $coin->id, $num);

        // 用户余额日志增加
        AccountLog::addLog($uid, $coin->id, $num, 20, 1, Account::TYPE_LC, $exp);

        // 矿池表信息增加
        UserWalletLog::addLog($uid, $dyTable, $orderId, $exp, '-', $totalNum, 2, 1);

        // 获取用户订单表信息
        $ro = ReleaseOrder::where(['uid' => $uid, 'status' => 0])->get();
        foreach ($ro as $v) {

            if (bcadd($v->release_num, $num, 8) < $v->total_num) {

                ReleaseOrder::where('id', $v->id)->increment('release_num', $num);

                break;

            } else {

                $rNum = bcsub($v->total_num, $v->release_num, 8);

                $num = bcsub($num, $rNum, 8);

                // 订单状态改变
                $roData = [
                    'release_num' => $v->total_num,
                    'release_time' => now()->toDateTimeString(),
                    'status' => 1,
                ];

                ReleaseOrder::where('id', $v->id)->update($roData);

            }

        }

        // 释放手续费费实时释放
        (new KuangjiBonus())->handle($tipNum);

        return true;

    }

}

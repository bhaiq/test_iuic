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

        \Log::info('=====  开始灵活矿机定时释放  =====');

        $this->toLhRelease();

        \Log::info('=====  灵活矿机定时释放结束  =====');

    }

    // 灵活矿机释放
    private function toLhRelease()
    {

        // 获取正在跑的灵活矿机信息
        $kjl = KuangjiLinghuo::where('created_at', '<', now()->toDateString() . ' 00:00:00')->where('num', '>', 0)->limit(50)->get();

        if ($kjl->isEmpty()) {
            \Log::info('没有合适的灵活矿机信息');
            return false;
        }

        \DB::beginTransaction();
        try {

            foreach ($kjl as $v) {

                /*$start = strtotime(substr($v->start_time, 0, 10) . ' 00:00:00');
                $cur = time();*/

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

        // 判断没有矿池的情况下去释放质押的灵活矿机
        if ($ui->release_total < $ui->buy_total) {

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

            // 释放手续费费实时释放
            (new KuangjiBonus())->handle($tipNum);

        } else {

            \Log::info('灵活矿机释放，没有矿池的情况下进了灵活矿机质押页面');

            // 获取灵活矿机的信息
            $kjLinghuo = KuangjiLinghuo::where('uid', $uid)->first();
            if (!$kjLinghuo) {
                \Log::info('没有矿池的情况下也没有灵活矿机，结束');
                return false;
            }

            // 判断还有没有质押的IUIC
            if ($kjLinghuo->num > 0) {
                \Log::info('没有矿池的情况下已经没有质押的IUIC了，结束');
                return false;
            }

            // 判断本次释放是否超过质押数量
            if ($totalNum > $kjLinghuo->num) {
                $totalNum = $kjLinghuo->num;
            }

            if ($totalNum <= 0) {
                \Log::info('可释放数量小于或等于0，放弃本次释放');
                return false;
            }

            $tipNum = bcmul($totalNum, $releaseTipBl, 8);

            // 获取那个USDT的币种ID
            $coin = Coin::getCoinByName('IUIC');

            // 用户余额增加
            Account::addAmount($uid, $coin->id, $totalNum);

            // 用户余额日志增加
            AccountLog::addLog($uid, $coin->id, $totalNum, 20, 1, Account::TYPE_LC, $exp);

            // 质押的灵活矿机数量减少
            KuangjiLinghuo::where('uid', $uid)->decrement('num', $totalNum);

            // 矿池表信息增加
            UserWalletLog::addLog($uid, $dyTable, $orderId, '算力灵活矿机释放', '-', $totalNum, 2, 1);

            // 释放手续费费实时释放
            (new KuangjiBonus())->handle($tipNum);

        }

        return true;

    }

}

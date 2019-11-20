<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/10/16
 * Time: 11:17
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\Kuangji;
use App\Models\KuangjiLinghuo;
use App\Models\KuangjiOrder;
use App\Models\KuangjiPosition;
use App\Models\KuangjiUserPosition;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use App\Services\Service;
use Illuminate\Http\Request;

class KuangJiController extends Controller
{

    // 矿机中心
    public function home(Request $request)
    {

        Service::auth()->isLoginOrFail();

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $lcAccount = Service::auth()->account($coin->id, Account::TYPE_LC);
        $ccAccount = Service::auth()->account($coin->id, Account::TYPE_CC);

        $result = [
            'iuic_num' => bcadd($lcAccount->amount, $ccAccount->amount, 4),
            'sy_pool' => 0,
            'lj_income' => 0,
            'lj_pool' => 0,
            'today_sl' => 0,
            'kj_list' => Kuangji::get()->toArray(),
        ];

        // 获取用户附属表信息
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if ($ui) {

            $result['sy_pool'] = bcsub($ui->buy_total, $ui->release_total, 4);
            $result['lj_income'] = bcmul($ui->release_total, 1, 4);
            $result['lj_pool'] = bcmul($ui->buy_total, 1, 4);

        }

        // 获取用户矿位信息
        $kup = KuangjiUserPosition::from('kuangji_user_position as kup')
            ->select('k.suanli')
            ->leftJoin('kuangji_order as ko', 'ko.id', 'kup.order_id')
            ->leftJoin('kuangji as k', 'k.id', 'kup.kuangji_id')
            ->where('kup.uid', Service::auth()->getUser()->id)
            ->where('kup.order_id', '>', 0)
            ->where('ko.created_at', '<', now()->toDateString() . ' 00:00:00')
            ->get()
            ->toArray();

        if (!empty($kup)) {

            // 计算正常矿机的算力
            foreach ($kup as $k => $v) {
                $result['today_sl'] += $v['suanli'];
            }

        }

        // 获取用户灵活算力的信息
        $kjl = KuangjiLinghuo::where('uid', Service::auth()->getUser()->id)->first();
        if ($kjl) {

            $maxLh = config('kuangji.kuangji_flexible_max', 200);

            $lhNum = $kjl->num > $maxLh ? $maxLh : $kjl->num;
            $lhSl = bcmul($lhNum, config('kuangji.kuangji_flexible_suanli_bl', 0.02), 2);

            $result['today_sl'] += $lhSl;
        }

        $result['today_sl'] = bcmul($result['today_sl'], 1, 2);

        return $this->response($result);

    }

    // 购买记录
    public function log(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $res = KuangjiOrder::from('kuangji_order as ko')
            ->select('ko.*', 'k.name', 'k.img', 'k.price', 'k.suanli', 'k.valid_day')
            ->join('kuangji as k', 'k.id', 'kuangji_id')
            ->where('ko.uid', Service::auth()->getUser()->id)
            ->latest('ko.id')
            ->paginate($request->get('per_page', 10));

        $result = $res->toArray();

        foreach ($result['data'] as $k => $v) {

            if ($v['status'] == 1) {

                $start = strtotime(substr($v['created_at'], 0, 10) . ' 00:00:00');

                $cur = time();

                $result['data'][$k]['sy_time'] = bcdiv(bcsub(bcadd($start, 181 * 24 * 3600), $cur), 24 * 3600);

            } else {
                $result['data'][$k]['sy_time'] = 0;
            }

            $result['data'][$k]['created_at'] = date('Y/m/d H:i', strtotime($v['created_at']));

        }

        return $this->response($result);

    }

    // 我的矿机
    public function my(Request $request)
    {

        Service::auth()->isLoginOrFail();

        // 获取矿位信息
        $kp = KuangjiPosition::get();

        $result = [];
        foreach ($kp as $k => $v) {

            $arr = [
                'kp_id' => $v->id,
                'kp_name' => $v->name,
                'kp_price' => $v->price,
                'is_open' => 0,
                'is_use' => 0,
                'kj_info' => null,
                'kup_id' => 0,
            ];

            // 判断自己这个矿位有没有开启
            $kup = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'position_id' => $v->id])->first();
            if ($kup) {

                $arr['is_open'] = 1;
                $arr['kup_id'] = $kup->id;

                if ($kup->kuangji_id > 0 && $kup->order_id > 0) {

                    $arr['is_use'] = 1;

                    $res = KuangjiOrder::from('kuangji_order as ko')
                        ->select('ko.*', 'k.name', 'k.img', 'k.price', 'k.suanli', 'k.valid_day')
                        ->join('kuangji as k', 'k.id', 'kuangji_id')
                        ->where('ko.id', $kup->order_id)
                        ->first();

                    $start = strtotime(substr($res->created_at, 0, 10) . ' 00:00:00');
                    $cur = time();

                    $kjInfo = [];
                    $kjInfo['sy_time'] = bcdiv(bcsub(bcadd($start, 181 * 24 * 3600), $cur), 24 * 3600);
                    $kjInfo['name'] = $res->name;
                    $kjInfo['img'] = $res->img;
                    $kjInfo['price'] = $res->price;
                    $kjInfo['suanli'] = $res->suanli;
                    $kjInfo['valid_day'] = $res->valid_day;

                    // 获取赎回比例
                    $redeemBl = $this->getRedeemBl(bcsub(180, $kjInfo['sy_time']));

                    // 获取赎回数量
                    $kjInfo['redeem_num'] = bcmul($redeemBl, $res->price);

                    $arr['kj_info'] = $kjInfo;

                }

            }

            // 判断这个矿位能不能买
            if (!$kup && $v->status == 0) {
                continue;
            }

            $result[] = $arr;

        }

        return $this->response($result);

    }

    // 购买矿位
    public function buyPosition(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'kp_id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'kp_id.required' => '矿位信息不能玩空',
            'kp_id.integer' => '矿位信息必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 获取矿位信息
        $kp = KuangjiPosition::where('status', 1)->find($request->get('kp_id'));
        if (!$kp) {
            $this->responseError('数据有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 判断用户是否已经购买
        $kupBool = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'position_id' => $request->get('kp_id')])->exists();
        if ($kupBool) {
            $this->responseError('该矿位已经购买了');
        }

        // 判断用户矿池数量是否充足
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if (!$ui || $ui->buy_total <= 0 || $ui->buy_total <= $ui->release_total) {
            $this->responseError('用户矿池不足');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if ($coinAccount->amount < $kp->price) {
            $this->responseError('用户余额不足');
        }

        $kupData = [
            'uid' => Service::auth()->getUser()->id,
            'position_id' => $request->get('kp_id'),
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            // 矿位表新增
            KuangjiUserPosition::create($kupData);

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $kp->price, Account::TYPE_LC);

            // 用户日志新增
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $kp->price, 20, 0, Account::TYPE_LC, '购买矿位');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买矿位异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 购买矿机
    public function buy(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'id.required' => '矿机信息不能玩空',
            'id.integer' => '矿机信息必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 获取矿机信息
        $kj = Kuangji::find($request->get('id'));
        if (!$kj) {
            $this->responseError('数据有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户是否有充足的矿位
        $kup = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'order_id' => 0, 'kuangji_id' => 0])->first();
        if (!$kup) {
            $this->responseError('没有矿位');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if ($coinAccount->amount < $kj->price) {
            $this->responseError('用户余额不足');
        }

        $koData = [
            'uid' => Service::auth()->getUser()->id,
            'kuangji_id' => $request->get('id'),
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            // 矿位表新增
            $ko = KuangjiOrder::create($koData);

            // 用户矿位表改变
            $kup->order_id = $ko->id;
            $kup->kuangji_id = $request->get('id');
            $kup->save();

            // 用户附属表释放状态改变
            UserInfo::where('uid', Service::auth()->getUser()->id)->update(['release_status' => 1]);
            UserInfo::where('uid', Service::auth()->getUser()->id)->increment('buy_total', $kj->num);

            // 矿池记录新增
            UserWalletLog::addLog(Service::auth()->getUser()->id, 'kuangji_order', $ko->id, '购买矿机', '+', $kj->num, 2, 1);

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $kj->price, Account::TYPE_LC);

            // 用户日志新增
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $kj->price, 20, 0, Account::TYPE_LC, '购买矿机');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买矿机异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 矿池记录
    public function releaseLog(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = UserWalletLog::where('uid', Service::auth()->getUser()->id)
            ->where('wallet_type', 2)
            ->where('log_type', 1)
            ->select('exp', 'sign', 'num', 'created_at')
            ->latest()
            ->paginate($request->get('per_page', 10));

        return $this->response($result->toArray());

    }

    // 矿机赎回
    public function redeem(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'id.required' => '矿位信息不能玩空',
            'id.integer' => '矿位信息必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 判断矿机赎回功能是否开启
        if (empty(config('kuangji.kuangji_redeem_switch'))) {
            $this->responseError('功能暂不开放');
        }

        // 获取用户矿池信息
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if (!$ui) {
            $this->responseError('数据有误');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        if (!$coin) {
            $this->responseError('币种信息');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证矿位信息是否正确
        $kup = KuangjiUserPosition::with(['order', 'kuangji'])->where(['uid' => Service::auth()->getUser()->id, 'id' => $request->get('id')])->first();
        if (!$kup) {
            $this->responseError('矿位信息有误');
        }

        // 判断矿位有没有数据
        if (empty($kup->order_id) || empty($kup->kuangji_id)) {
            $this->responseError('该矿位没有矿机');
        }

        // 判断矿池的剩余数量是否支持赎回
        if (bcsub($ui->buy_total, $ui->release_total, 8) < $kup->kuangji->num) {
            $this->responseError('剩余矿池数量不足');
        }

        // 计算矿机释放的时间
        $buyDay = bcadd(bcdiv(bcsub(time(), strtotime($kup->order->created_at)), 3600 * 24), 1);
        if ($buyDay > 90) {
            $this->responseError('矿机超过90天,不能赎回');
        }

        // 计算本次释放赎回比例
        $redeemBl = $this->getRedeemBl($buyDay);

        // 计算本次释放赎回数量
        $oneNum = bcmul($kup->kuangji->num, $redeemBl, 8);

        \DB::beginTransaction();
        try {

            // 矿机订单关闭
            KuangjiOrder::where('id', $kup->order_id)->update(['status' => 2]);

            // 矿位表更新
            KuangjiUserPosition::where('id', $kup->id)->update(['order_id' => 0, 'kuangji_id' => 0]);

            // 用户总矿池数量减少
            UserInfo::where('uid', Service::auth()->getUser()->id)->decrement('buy_total', $kup->kuangji->num);

            // 矿池记录新增
            UserWalletLog::addLog(Service::auth()->getUser()->id, 'kuangji_order', $kup->order_id, '矿机赎回', '-', $kup->kuangji->num, 2, 1);

            // 用户余额增加
            Account::addAmount(Service::auth()->getUser()->id, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志增加
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $oneNum, 20, 1, Account::TYPE_LC, '矿机赎回');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('矿机赎回异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 计算赎回手续费
    private function getRedeemBl($dayNum)
    {

        if ($dayNum < 30) {
            $redeemBl = config('kuangji.kuangji_redeem_30_bl', 0.7);
        } else if ($dayNum < 60) {
            $redeemBl = config('kuangji.kuangji_redeem_60_bl', 0.5);
        } else {
            $redeemBl = config('kuangji.kuangji_redeem_90_bl', 0.3);
        }

        return $redeemBl;
    }

    // 获取灵活矿位信息
    public function getFlexible(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = [
            'kp_name' => '灵活矿位',
            'kp_price' => config('kuangji.kuangji_flexible_price', 0),
            'is_open' => 0,
            'is_use' => 0,
            'kj_info' => null,
        ];

        // 验证用户是否已经激活灵活矿位
        $kjl = KuangjiLinghuo::where('uid', Service::auth()->getUser()->id)->first();
        if ($kjl) {

            $result['is_open'] = 1;
            $result['is_use'] = 1;

            if ($kjl->num > 0) {


                $start = strtotime(substr($kjl->start_time, 0, 10) . ' 00:00:00');
                $cur = time();

                $maxLh = config('kuangji.kuangji_flexible_max', 200);

                $totalNum = $kjl->num > $maxLh ? $maxLh : $kjl->num;

                $suanli = bcmul($totalNum, config('kuangji.kuangji_flexible_suanli_bl', 0.02), 2);

                $result['kj_info'] = [
                    'sy_time' => bcdiv(bcsub(bcadd($start, 181 * 24 * 3600), $cur), 24 * 3600),
                    'name' => '灵活算力',
                    'img' => url()->previous() . '/images/lh.png',
                    'price' => $kjl->num,
                    'suanli' => $suanli,
                    'valid_day' => 180,
                ];

            }else{

                $result['kj_info'] = [
                    'sy_time' => 0,
                    'name' => '灵活算力',
                    'img' => url()->previous() . '/images/lh.png',
                    'price' => 0,
                    'suanli' => 0,
                    'valid_day' => 0,
                ];
            }

        }

        return $this->response($result);

    }

    // 购买灵活矿位
    public function submitFlexible(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'paypass' => 'required',
        ], [
            'paypass.required' => '交易密码不能为空',
        ]);

        // 判断有木有灵活矿位信息
        $kjl = KuangjiLinghuo::where('uid', Service::auth()->getUser()->id)->first();
        if ($kjl) {
            $this->responseError('已经购买了');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        $price = config('kuangji.kuangji_flexible_price', 0);
        if ($coinAccount->amount < $price) {
            $this->responseError('用户余额不足');
        }

        $kjlData = [
            'uid' => Service::auth()->getUser()->id,
            'num' => 0,
            'start_time' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            KuangjiLinghuo::create($kjlData);

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $price, Account::TYPE_LC);

            // 用户日志新增
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $price, 20, 0, Account::TYPE_LC, '购买灵活矿位');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买灵活矿位异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 灵活矿位购买矿机
    public function buyFlexible(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'num' => 'required|integer',
            'paypass' => 'required',
        ], [
            'num.required' => '数量不能为空',
            'num.integer' => '数量必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 判断数量是否小于限制数量
        $minLh = config('kuangji.kuangji_flexible_min', 1);
        if ($request->get('num') < $minLh) {
            $this->responseError('购买数量不能小于' . $minLh);
        }

        // 判断数量是否大于于限制数量
        $maxLh = config('kuangji.kuangji_flexible_max', 200);
        if ($request->get('num') > $maxLh) {
            $this->responseError('购买数量不能大于' . $maxLh);
        }

        // 判断有木有灵活矿位信息
        $kjl = KuangjiLinghuo::where('uid', Service::auth()->getUser()->id)->first();
        if (!$kjl) {
            $this->responseError('未购买矿位');
        }

        // 判断用户已有的加上本次购买的是否超过限制的
        if(bcadd($request->get('num'), $kjl->num) > $maxLh){
            $this->responseError('最多只能购买' . bcsub($maxLh, $kjl->num));
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if ($coinAccount->amount < $request->get('num')) {
            $this->responseError('用户余额不足');
        }

        \DB::beginTransaction();
        try {

            $kjl->start_time = now()->toDateTimeString();
            $kjl->save();

            KuangjiLinghuo::where('uid', Service::auth()->getUser()->id)->increment('num', $request->get('num'));

            // 用户附属表释放状态改变
            UserInfo::where('uid', Service::auth()->getUser()->id)->update(['release_status' => 1]);
            UserInfo::where('uid', Service::auth()->getUser()->id)->increment('buy_total', $request->get('num'));

            // 矿池记录新增
            UserWalletLog::addLog(Service::auth()->getUser()->id, 'kuangji_linghuo', $kjl->id, '购买灵活矿机', '+', $request->get('num'), 2, 1);

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $request->get('num'), Account::TYPE_LC);

            // 用户日志新增
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $request->get('num'), 20, 0, Account::TYPE_LC, '购买灵活矿机');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买灵活矿位异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 赎回矿机说明
    public function redeemInfo(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = [
            'redeem_30' => bcmul(config('kuangji.kuangji_redeem_30_bl', 0.7), 100) . '%',
            'redeem_60' => bcmul(config('kuangji.kuangji_redeem_60_bl', 0.5), 100) . '%',
            'redeem_90' => bcmul(config('kuangji.kuangji_redeem_90_bl', 0.3), 100) . '%',
        ];

        return $this->response($result);

    }

}
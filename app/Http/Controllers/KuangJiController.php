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
        if($ui){

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

        if(!empty($kup)){

            foreach ($kup as $k => $v){
                $result['today_sl'] += $v['suanli'];
            }

        }

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

        foreach ($result['data'] as $k => $v){

            if($v['status'] > 0){

                $start = strtotime(substr($v['created_at'], 0, 10) . ' 00:00:00');

                $cur = time();

                $result['data'][$k]['sy_time']  = bcdiv(bcsub(bcadd($start, 181 * 24 * 3600), $cur), 24 * 3600);

            }else{
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
        foreach ($kp as $k => $v){

            $arr = [
                'kp_id' => $v->id,
                'kp_name' => $v->name,
                'kp_price' => $v->price,
                'is_open' => 0,
                'is_use' => 0,
                'kj_info' => null,
            ];

            // 判断自己这个矿位有没有开启
            $kup = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'position_id' => $v->id])->first();
            if($kup){

                $arr['is_open'] = 1;

                if($kup->kuangji_id > 0 && $kup->order_id > 0){

                    $arr['is_use'] = 1;

                    $res = KuangjiOrder::from('kuangji_order as ko')
                        ->select('ko.*', 'k.name', 'k.img', 'k.price', 'k.suanli', 'k.valid_day')
                        ->join('kuangji as k', 'k.id', 'kuangji_id')
                        ->where('ko.id', $kup->order_id)
                        ->first();

                    $start = strtotime(substr($res->created_at, 0, 10) . ' 00:00:00');
                    $cur = time();

                    $kjInfo = [];
                    $kjInfo['sy_time']  = bcdiv(bcsub(bcadd($start, 181 * 24 * 3600), $cur), 24 * 3600);
                    $kjInfo['name'] = $res->name;
                    $kjInfo['img'] = $res->img;
                    $kjInfo['price'] = $res->price;
                    $kjInfo['suanli'] = $res->suanli;
                    $kjInfo['valid_day'] = $res->valid_day;

                    $arr['kj_info'] = $kjInfo;

                }

            }

            // 判断这个矿位能不能买
            if(!$kup && $v->status == 0){
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
            'kp_id'     => 'required|integer',
            'paypass' => 'required',
        ], [
            'kp_id.required' => '矿位信息不能玩空',
            'kp_id.integer' => '矿位信息必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 获取矿位信息
        $kp = KuangjiPosition::where('status', 1)->find($request->get('kp_id'));
        if(!$kp){
            $this->responseError('数据有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 判断用户是否已经购买
        $kupBool = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'position_id' => $request->get('kp_id')])->exists();
        if($kupBool){
            $this->responseError('该矿位已经购买了');
        }

        // 判断用户矿池数量是否充足
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if(!$ui || $ui->buy_total <= 0 || $ui->buy_total <= $ui->release_total){
            $this->responseError('用户矿池不足');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if($coinAccount->amount < $kp->price){
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
            'id'     => 'required|integer',
            'paypass' => 'required',
        ], [
            'id.required' => '矿机信息不能玩空',
            'id.integer' => '矿机信息必须是整数',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 获取矿机信息
        $kj = Kuangji::find($request->get('id'));
        if(!$kj){
            $this->responseError('数据有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户是否有充足的矿位
        $kup = KuangjiUserPosition::where(['uid' => Service::auth()->getUser()->id, 'order_id' => 0, 'kuangji_id' => 0])->first();
        if(!$kup){
            $this->responseError('没有矿位');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('IUIC');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if($coinAccount->amount < $kj->price){
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

}
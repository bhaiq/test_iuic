<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\CoinExtract;
use App\Models\UsdtExtract;
use App\Models\UserInfo;
use App\Services\Service;
use Illuminate\Http\Request;
use App\Models\EthTransaction;
use App\Models\Wallet;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class EthController extends Controller
{
    public function handleEthTokenTransaction(Request $request)
    {
        $post = $request->post();
        $hash = $post['hash'];
        $userAddress = $post['to'];
        $isHash = EthTransaction::whereHash($hash)->first();
        $amount = $post['amount'];
        $contract = $post['contract'];
        if ($isHash == null) {
            $coinId = $this->contractFilter($contract);
            if($coinId == null) {
                return 'success';
            }

            $uid = Wallet::whereAddress($userAddress)->where('type', 1)->value('uid');
            if ($uid != null) {
                $transaction = [
                    'hash' => $hash,
                    'block' => $post['blockNum'],
                    'from' => $post['from'],
                    'to' => $post['to'],
                    'amount' => $post['amount'],
                    'coin_id' => $coinId
                ];

                DB::transaction(function () use ($uid, $amount, $transaction, $coinId) {
                    EthTransaction::create($transaction);
                    $this->account($uid, $coinId,  $amount);
                    Service::account()->createLog($uid, $coinId, $amount,AccountLog::SCENE_RECHARGE, compact('hash'));
                });
            }
        }

        return 'success';
    }

    public function contractFilter(string $contractAddress)
    {
        $coiTypeList = Coin::all()->pluck('coin_types', 'id')->toArray();
        foreach ($coiTypeList as $k => $coin_types) {
            foreach ($coin_types as $coin_type) {
                if ($coin_type['value'] == $contractAddress) return $k;
            }
        }

        return null;
    }


    public function extract(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $user = Service::auth()->getUser();
        $max = $user->account->USDT;
        $this->validate($request->all(), [
            'amount' => 'required|numeric|min:10|max:' . $max,
            'address' => 'required|max:50',
            'pay_password' => 'required',
        ], [
            'amount.required' => trans('eth.controller.extract.amount_required'),
            'amount.min' => trans('eth.controller.extract.less_then_10'),
            'amount.max' => trans('eth.controller.extract.not_enough'),
        ]);

        Service::auth()->isTransactionPasswordYesOrFail($request->input('pay_password'));
        $uid = $user->id;
        $onAmount = $request->input('amount');
        $address = $request->input('address');
        $charge = 1;
        $amount = $onAmount - $charge;

        DB::transaction(function () use ($user, $uid, $amount, $onAmount, $address, $charge) {
            $user->account()->decrement('USDT', $onAmount);
            UsdtExtract::create(compact('uid', 'amount', 'address', 'charge'));
            Service::account()->createUsdtLog($uid, $onAmount, UsdtLog::SCENE_ROLL_OUT);
            Service::account()->extractTip();
        });

        $user->account->refresh();
        return $this->response($user->toArray());
    }

    public function getExtraceList(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $user = Service::auth()->getUser();
        $uid = $user->id;
        $per_page = $request->get('per_page');

        $list = UsdtExtract::whereUid($uid)->orderBy('id', 'desc')->paginate($per_page);

        return $this->response($list->toArray());
    }

    public function account(int $uid, int $coinId, $amount)
    {
        Account::whereUid($uid)->where('coin_id', $coinId)->whereType(Account::TYPE_CC)->increment('amount', $amount);
    }

    // 手续费
    public function extractConfig(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'coin_id'   => 'required',
        ], [
            'coin_id.required' => '币种信息不能为空',
        ]);

        $c = Coin::where('id', $request->get('coin_id'))->first();
        if(!$c){
            $this->responseError('币种信息有误');
        }
        if(!in_array(strtoupper($c->name), ['USDT', 'IUIC'])){
            $this->responseError('币种信息数据有误');
        }

        if($c->name == 'USDT'){

            $charge = StringLib::sprintN(config('extract.usdt_tip'), 2);

        }else{

            $charge = StringLib::sprintN(config('extract.iuic_tip'), 2);

        }

        return $this->response(['charge' => $charge]);

    }

    // 提币申请提交
    public function extractSubmit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'coin_id'   => 'required',
            'num'   => 'required|numeric',
            'paypass'   => 'required',
            'address'   => 'required',
        ], [
            'coin_id.required' => '币种信息不能为空',
            'num.required'   => '提币数量不能为空',
            'num.numeric'   => '数量格式不正确',
            'paypass.required'   => '支付密码不能为空',
            'address.required'   => '提币地址不能为空',
        ]);

        $c = Coin::where('id', $request->get('coin_id'))->first();
        if(!$c){
            $this->responseError('币种信息有误');
        }
        if(!in_array(strtoupper($c->name), ['USDT', 'IUIC'])){
            $this->responseError('币种信息数据有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 判断用户账号是否异常
        $acco = Account::where('uid', Service::auth()->getUser()->id)->get();
        if($acco){
            foreach ($acco as $v){
                if($v->amount_freeze <= -10){
                    $this->responseError('账号异常,请联系客服');
                    break;
                }
            }
        }

        // 提现判断矿池开关
        if(config('kuangji.kuangji_cash_switch', 0)){

            // 判断用户矿池是否充足
            $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
            if(!$ui || bcsub($ui->buy_total, $ui->release_total, 8) <= 0){
                $this->responseError('矿池数量不足,不能提现');
            }

        }
        
        // 获取手续费
        if($c->name == 'USDT'){
            $charge = config('extract.usdt_tip');
        }else{
            $charge = config('extract.iuic_tip');
        }

        // 提取数量不嫩小于手续费
        if($request->get('num') <= $charge){
            $this->responseError('提币数量不能小于或等于手续费');
        }

        // 获取用户余额
        $coinAccount = Service::auth()->account($c->id, Account::TYPE_CC);

        // 判断用户余额是否充足
        if($coinAccount->amount < $request->get('num')){
            $this->responseError('用户余额不足');
        }

        $ceData = [
            'uid' => Service::auth()->getUser()->id,
            'coin_id' => $c->id,
            'coin_num' => $request->get('num'),
            'charge' => $charge,
            'address' => $request->get('address'),
            'final_num' => bcsub($request->get('num'), $charge, 8),
            'status' => 0,
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            CoinExtract::create($ceData);

            \Log::info('用户提现表新增一条数据', $ceData);

            Account::reduceAmount(Service::auth()->getUser()->id, $c->id, $request->get('num'), Account::TYPE_CC);

            Account::addFrozen(Service::auth()->getUser()->id, $c->id, $request->get('num'), Account::TYPE_CC);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('用户提现失败', $request->all());

            $this->responseError('提现异常');

        }

        $a = Account::where('uid', Service::auth()->getUser()->id)->first();
        $result = [
            'amount' => $a->amount,
            'amount_freeze' => $a->amount_freeze,
            'amount_cny' => $a->amount_cny,
        ];

        return $this->response($result);

    }

    // 用户提现列表
    public function extractList(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'coin_id'   => 'required',
        ], [
            'coin_id.required' => '币种信息不能为空',
        ]);


        $res = CoinExtract::from('coin_extract as ce')
            ->select('ce.id', 'ce.coin_num', 'ce.charge', 'ce.address', 'ce.final_num', 'ce.status', 'ce.created_at', 'c.name as coin_name')
            ->leftJoin('coin as c', 'c.id', 'ce.coin_id')
            ->where(['uid' => Service::auth()->getUser()->id, 'coin_id' => $request->get('coin_id')])
            ->latest()
            ->paginate($request->get('per_page', 10));

        foreach ($res as $k => $v){

            $res[$k]['status_name'] = CoinExtract::STATUS[$v->status];
            $res[$k]['created_at'] = $v->created_at->toDateTimeString();

        }

        return $this->response($res->toArray());

    }

}

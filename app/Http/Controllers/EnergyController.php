<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/17
 * Time: 17:08
 */

namespace App\Http\Controllers;

use App\Jobs\EnergyDynamicRelease;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\EnergyExchange;
use App\Models\EnergyGood;
use App\Models\EnergyLog;
use App\Models\EnergyOrder;
use App\Models\EnergyTransfer;
use App\Models\MallAddress;
use App\Models\UserInfo;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Services\Service;
use Illuminate\Http\Request;

class EnergyController extends Controller
{

    // 获取能量商品列表
    public function goods()
    {

        $this->responseError(trans('api.service_closing'));

        Service::auth()->isLoginOrFail();

        $result = [];
        $res = EnergyGood::latest('top')->get();
        if($res->isEmpty()){
            return $this->response($result);
        }

        foreach ($res as $k => $v){

            $result[] = [
                'goods_id' => $v->id,
                'goods_name' => $v->goods_name,
                'goods_img' => $v->goods_img,
                'goods_price' => $v->goods_price,
                'goods_details' => $v->goods_details,
                'coin_type' => 'USDT',
                'add_num' => $v->add_num,
                'xg_num' => $v->xg_num,
                'buy_num' => EnergyOrder::geyBuyNum(Service::auth()->getUser()->id, $v->id),
            ];

        }

        return $this->response($result);

    }

    // 商品购买
    public function buy(Request $request)
    {
        $this->responseError(trans('api.service_closing'));

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required|integer',
            'goods_num' => 'required|integer|min:1',
            'address_id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'goods_id.required' => trans('api.information_cannot_empty'),
            'goods_id.integer' => trans('api.information_type_incorrect'),
            'goods_num.required' => trans('api.goods_cannot_empty'),
            'goods_num.integer' => trans('api.item_quantity_incorrect'),
            'goods_num.min' => trans('api.goods_not_less_than_1'),
            'address_id.required' => trans('api.address_cannot_empty'),
            'address_id.integer' => trans('api.address_is_incorrect'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);

        // 判断用户是否实名验证
        $user = Service::auth()->getUser();
        if(!$user){
            $this->responseError(trans('api.parameter_is_wrong'));
        }
        if($user->is_auth != 1){
            $this->responseError(trans('api.dont_authenticated'));
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证商品信息
        $good = EnergyGood::where('id', $request->get('goods_id'))->first();
        if(!$good){
            $this->responseError(trans('api.incorrect_commodity_information'));
        }

        // 验证购买的数量是否达到限购的数量
        $buyCount = EnergyOrder::geyBuyNum(Service::auth()->getUser()->id, $good->id);
        if(bcadd($buyCount, $request->get('goods_num')) > $good->xg_num){
            $this->responseError(trans('api.purchase_exceeds_limit'));
        }

        // 验证地址信息
        $address = MallAddress::where(['id' => $request->get('address_id'), 'uid' => $user->id])->first();
        if(!$address){
            $this->responseError(trans('api.address_is_incorrect'));
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        $totalPrice = bcmul($good->goods_price, $request->get('goods_num'), 8);
        if($coinAccount->amount < $totalPrice){
            $this->responseError(trans('api.insufficient_user_balance'));
        }

        $newAddress = '';
        $i = 0;

        $arr = explode(',', $address->address);
        foreach ($arr as $val) {
            if ($i > 0) {
                $newAddress .= $val;
            }
            $i++;
        }

        $oNum = bcmul($good->num, $request->get('goods_num'), 2);
        $oAddNum = bcmul($good->add_num, $request->get('goods_num'), 2);

        $eoData = [
            'uid' => $user->id,
            'goods_id' => $good->id,
            'goods_name' => $good->goods_name,
            'goods_img' => $good->goods_img,
            'goods_price' => $good->goods_price,
            'goods_num' => $request->get('goods_num'),
            'num' => $oNum,
            'add_num' => $oAddNum,
            'to_name' => $address->name,
            'to_mobile' => $address->mobile,
            'to_address' => $newAddress . $address->address_info,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 生成订单
            $eno = EnergyOrder::create($eoData);
          	$orid=$eno->id;

            // 用户余额减少
            Account::reduceAmount($user->id, $coin->id, $totalPrice);

            // 用户余额日志增加
            AccountLog::addLog($user->id, $coin->id, $totalPrice, 21, 0, Account::TYPE_LC, '购买能量商品');

            // 先验证用户是否有能量账户
            if(UserWallet::where('uid', $user->id)->exists()){

                UserWallet::addEnergyFrozenNum($user->id, $oAddNum);

            }else{

                $uwData = [
                    'uid' => $user->id,
                    'energy_frozen_num' => $oAddNum,
                    'created_at' => now()->toDateTimeString(),
                ];
                UserWallet::create($uwData);

            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买能量商品出现异常');

            $this->responseError(trans('api.buy_abnormal'));

        }

        // 加入队列
        dispatch(new EnergyDynamicRelease(Service::auth()->getUser()->id, $oNum,$totalPrice,$orid));

        $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 兑换提交
    public function exchange(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'coin_id'     => 'required',
            'num' => 'required|integer|min:0',
            'paypass' => 'required',
        ], [
            'coin_id.required' => trans('api.currency_information_cannot_empty'),
            'num.required' => trans('api.quantity_cannot_empty'),
            'num.integer' => trans('api.quantity_must_integer'),
            'num.min' => trans('api.quantity_cannot_less_than_0'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);

        // 获取币种信息
        $coin = Coin::find($request->get('coin_id'));
        if(!$coin){
            $this->responseError(trans('api.currency_information_incorrect'));
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户余额是否充足
        if(!UserWallet::checkWallet(Service::auth()->getUser()->id, $request->get('num'))){
            $this->responseError(trans('api.insufficient_user_balance'));
        }

        $totalNum = $request->get('num');

        // 判断有木有手续费
        if(!empty(config('shop.energy_exchange_tip', 0))){

            $tip = config('shop.energy_exchange_tip', 0);
            $totalNum = bcmul($request->get('num'), bcsub(1, $tip, 8), 8);

        }

        // 获取能量兑换人民币的价格
        $energyCny = UserWallet::getCnyEnergy();

        // 获取币种兑换人民币的价格
        $cny = Account::getCoinCnyPrice($coin->id);

        // 获取IUIC兑换人民币的价格
        $iuicCny = Account::getCoinCnyPrice(2);

        // 计算转矿的数量
        $zkBl = config('energy.energy_zk_bl', 0.5);
        $zkNum = bcdiv(bcmul(bcmul($totalNum, $zkBl, 8), $energyCny, 8), $iuicCny,8);

        // 计算本次兑换得到的数量
        $gainBl = bcsub(1, $zkBl, 4);
        $oneNum = bcdiv(bcmul(bcmul($totalNum, $gainBl, 8), $energyCny, 8), $cny, 8);

        $eeData = [
            'uid' => Service::auth()->getUser()->id,
            'coin_id' => $coin->id,
            'num' => $request->get('num'),
            'gain_num' => $oneNum,
            'zk_bl' => $zkBl,
            'zk_num' => $zkNum,
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 兑换表新增
            $ee = EnergyExchange::create($eeData);

            // 能量资产减少
            UserWallet::reduceEnergyNum(Service::auth()->getUser()->id, $request->get('num'));

            // 能量资产余额表日志新增
            EnergyLog::addLog(Service::auth()->getUser()->id, 1, 'energy_goods', $ee->id, '兑换' . $coin->name, '-', $request->get('num'), 1);

            // 币种资产增加
            Account::addAmount(Service::auth()->getUser()->id, $coin->id, $oneNum);

            // 用户余额资产增加
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $oneNum, 22, 1, Account::TYPE_LC, '能量兑换');

            // 用户矿池增加
            UserInfo::addBuyTotal(Service::auth()->getUser()->id, $zkNum);

            // 用户框处余额日志增加
            UserWalletLog::addLog(Service::auth()->getUser()->id, 'energy_exchange', $ee->id, '能量兑换', '+', $zkNum, 2, 1);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('能量资产兑换异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 兑换页面数据获取
    public function exchangeStart()
    {

        Service::auth()->isLoginOrFail();

        $num = 0;
        $ui = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
        if($ui){
            $num = $ui->energy_num;
        }

        $result = [
            'num' => $num,
            'zk_bl' => config('energy.energy_zk_bl', 0.5)
        ];

        return $this->response($result);

    }

    // 划转页面数据获取
    public function transferStart()
    {

        Service::auth()->isLoginOrFail();

        $num = 0;

        // 获取用户能量资产信息
        $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
        if($uw){
            $num = bcmul($uw->energy_frozen_num, 1, 4);
        }

        $result = [
            'num' => $num,
            'bl' => 1,
        ];

        return $this->response($result);

    }

    // 能量划转提交
    public function transfer(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'num' => 'required|integer|min:0',
            'paypass' => 'required',
        ], [
            'num.required' => trans('api.quantity_cannot_empty'),
            'num.integer' => trans('api.quantity_must_integer'),
            'num.min' => trans('api.quantity_cannot_less_than_0'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 获取用户能量资产信息
        $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
        if(!$uw || $uw->energy_frozen_num < $request->get('num')){
            $this->responseError(trans('api.insufficient_user_balance'));
        }

        $data = [
            'uid' => Service::auth()->getUser()->id,
            'num' => $request->get('num'),
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 冻结能量资产划转表新增
            $et = EnergyTransfer::create($data);

            // 冻结能量减少
            UserWallet::reduceEnergyFrozenNum(Service::auth()->getUser()->id, $request->get('num'));

            // 用户IUIC矿池增加
            UserInfo::addBuyTotal(Service::auth()->getUser()->id, $request->get('num'));

            $totalNum = $request->get('num');

            // 先获取用户的能量订单信息
            $orders = EnergyOrder::where(['uid' => Service::auth()->getUser()->id, 'status' => 0])->get();
            foreach ($orders as $v){

                // 先判断该订单的未释放量
                $noReleaseNum = bcsub($v->add_num, $v->release_num, 8);

                // 判断可释放数和划转数的大小
                if($totalNum > $noReleaseNum){

                    // 订单状态改变
                    $updData = [
                        'status' => 1,
                        'release_num' => $v->add_num,
                    ];

                    EnergyOrder::where('id', $v->id)->update($updData);

                    $totalNum = bcsub($totalNum, $noReleaseNum, 8);

                }else if($totalNum == $noReleaseNum){

                    // 订单状态改变
                    $updData = [
                        'status' => 1,
                        'release_num' => $v->add_num,
                    ];

                    EnergyOrder::where('id', $v->id)->update($updData);

                    $totalNum = 0;

                    break;

                }else{

                    // 释放量增加
                    EnergyOrder::where('id', $v->id)->increment('release_num', $totalNum);

                    $totalNum = 0;

                }

            }

            if($totalNum > 0){
                new \Exception(trans('api.parameter_is_wrong'));
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('能量资产划转异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 能量兑换页面计算
    public function exchangeCompute(Request $request)
    {

        $this->validate($request->all(), [
            'num' => 'required|integer|min:0',
            'coin_id' => 'required',
        ], [
            'num.required' => trans('api.quantity_cannot_empty'),
            'num.integer' => trans('api.quantity_must_integer'),
            'num.min' => trans('api.quantity_cannot_less_than_0'),
            'coin_id.required' => trans('api.currency_information_cannot_empty'),
        ]);

        // 获取币种信息
        $coin = Coin::find($request->get('coin_id'));
        if(!$coin){
            $this->responseError(trans('api.currency_information_incorrect'));
        }

        $totalNum = $request->get('num');
        $tipNum = 0;

        // 判断有木有手续费
        if(!empty(config('shop.energy_exchange_tip', 0))){

            $tip = config('shop.energy_exchange_tip', 0);
            $tipNum = bcmul($tip, $request->get('num'), 8);
            $totalNum = bcsub($request->get('num'), $tipNum, 8);

        }

        // 获取能量兑换人民币的价格
        $energyCny = UserWallet::getCnyEnergy();

        // 获取币种兑换人民币的价格
        $cny = Account::getCoinCnyPrice($coin->id);

        // 获取IUIC兑换人民币的价格
        $iuicCny = Account::getCoinCnyPrice(2);

        // 计算转矿的数量
        $zkBl = config('energy.energy_zk_bl', 0.5);
        $zkNum = bcdiv(bcmul(bcmul($totalNum, $zkBl, 8), $energyCny, 8), $iuicCny,8);

        // 计算本次兑换得到的数量
        $gainBl = bcsub(1, $zkBl, 4);
        $oneNum = bcdiv(bcmul(bcmul($totalNum, $gainBl, 8), $energyCny, 8), $cny, 8);

        $result = [
            'num' => $oneNum,
            'zk_num' => $zkNum,
            //            'tip_num' => $tipNum,
        ];

        return $this->response($result);

    }

    // 能量兑换币种列表
    public function coin(Request $request)
    {

        $result = [
            [
                'id' => 1,
                'name' => 'USDT',
            ]
        ];

        return $this->response($result);
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/17
 * Time: 17:08
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\EnergyExchange;
use App\Models\EnergyGood;
use App\Models\EnergyLog;
use App\Models\EnergyOrder;
use App\Models\MallAddress;
use App\Models\UserWallet;
use App\Services\Service;
use Illuminate\Http\Request;

class EnergyController extends Controller
{

    // 获取能量商品列表
    public function goods()
    {

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

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id'     => 'required|integer',
            'address_id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'goods_id.required' => '商品信息不能为空',
            'goods_id.integer' => '商品信息类型不正确',
            'address_id.required' => '地址信息不能为空',
            'address_id.integer' => '地址信息类型不正确',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 判断用户是否实名验证
        $user = Service::auth()->getUser();
        if(!$user){
            $this->responseError('数据有误');
        }
        if($user->is_auth != 1){
            $this->responseError('未实名认证');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证商品信息
        $good = EnergyGood::where('id', $request->get('goods_id'))->first();
        if(!$good){
            $this->responseError('商品信息有误');
        }

        // 验证购买的数量是否达到限购的数量
        $buyCount = EnergyOrder::geyBuyNum(Service::auth()->getUser()->id, $good->id);
        if($buyCount >= $good->xg_num){
            $this->responseError('购买数量超过限制');
        }

        // 验证地址信息
        $address = MallAddress::where(['id' => $request->get('address_id'), 'uid' => $user->id])->first();
        if(!$address){
            $this->responseError('地址信息有误');
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 判断用户余额是否充足
        if($coinAccount->amount < $good->goods_price){
            $this->responseError('用户余额不足');
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

        $eoData = [
            'uid' => $user->id,
            'goods_id' => $good->id,
            'goods_name' => $good->goods_name,
            'goods_img' => $good->goods_img,
            'goods_price' => $good->goods_price,
            'num' => $good->num,
            'add_num' => $good->add_num,
            'to_name' => $address->name,
            'to_mobile' => $address->mobile,
            'to_address' => $newAddress . $address->address_info,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 生成订单
            EnergyOrder::create($eoData);

            // 用户余额减少
            Account::reduceAmount($user->id, $coin->id, $good->goods_price);

            // 用户余额日志增加
            AccountLog::addLog($user->id, $coin->id, $good->goods_price, 21, 0, Account::TYPE_LC, '购买能量商品');

            // 先验证用户是否有能量账户
            if(UserWallet::where('uid', $user->id)->exists()){

                UserWallet::addEnergyFrozenNum($user->id, $good->add_num);

            }else{

                $uwData = [
                    'uid' => $user->id,
                    'energy_frozen_num' => $good->add_num,
                    'created_at' => now()->toDateTimeString(),
                ];
                UserWallet::create($uwData);

            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('购买能量商品出现异常');

            $this->responseError('购买异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 兑换提交
    public function exchange(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'coin_id'     => 'required',
            'num' => 'required|integer|min:1',
            'paypass' => 'required',
        ], [
            'coin_id.required' => '币种信息不能为空',
            'num.required' => '数量不能为空',
            'num.integer' => '数量必须是整数',
            'num.min' => '数量不能小于1',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 获取币种信息
        $coin = Coin::find($request->get('coin_id'));
        if(!$coin){
            $this->responseError('币种信息有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证用户余额是否充足
        if(!UserWallet::checkWallet(Service::auth()->getUser()->id, $request->get('num'))){
            $this->responseError('用户余额不足');
        }

        // 获取币种兑换人民币的价格
        $cny = Account::getCoinCnyPrice($coin->id);

        // 获取能量兑换人民币的价格
        $energyCny = UserWallet::getCnyEnergy();

        // 计算本次兑换得到的数量
        $oneNum = bcdiv(bcmul($request->get('num'), $energyCny, 8), $cny, 8);

        // 判断有木有手续费
        if(!empty(config('shop.energy_exchange_tip', 0))){

            $tip = config('shop.energy_exchange_tip', 0);
            $oneNum = bcmul($oneNum, bcsub(1, $tip, 8), 8);

        }

        $eeData = [
            'uid' => Service::auth()->getUser()->id,
            'coin_id' => $coin->id,
            'num' => $request->get('num'),
            'gain_num' => $oneNum,
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 兑换表新增
            $ee = EnergyExchange::create($eeData);

            // 能量资产减少
            UserWallet::reduceEnergyNum(Service::auth()->getUser()->id, $request->get('num'));

            // 能量资产余额表日志新增
            EnergyLog::addLog(Service::auth()->getUser()->id, 'energy_goods', $ee->id, '兑换' . $coin->name, '-', $request->get('num'), 1);

            // 币种资产增加
            Account::addAmount(Service::auth()->getUser()->id, $coin->id, $oneNum);

            // 用户余额资产增加
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $oneNum, 22, 1, Account::TYPE_LC, '能量兑换');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('能量资产兑换异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

}
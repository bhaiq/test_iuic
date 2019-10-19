<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/3
 * Time: 18:08
 */

namespace App\Http\Controllers;

use App\Jobs\UpdateAdminBonus;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\MallAddress;
use App\Models\ReleaseOrder;
use App\Models\ShopGoods;
use App\Models\ShopOrder;
use App\Models\UserBonus;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use App\Services\Service;
use Illuminate\Http\Request;

class ShopController extends Controller
{

    // 商品列表
    public function goods()
    {

        $res = ShopGoods::oldest('id')->get(['id', 'goods_name', 'goods_img', 'goods_info', 'goods_price', 'coin_type', 'ore_pool', 'goods_details', 'sale_num', 'created_at'])->toArray();
        return $this->response($res);

    }

    // 商品购买
    public function store(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id'     => 'required|integer',
            'address_id' => 'required|integer',
            'paypass' => 'required',
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
        $good = ShopGoods::where('id', $request->get('goods_id'))->first();
        if(!$good){
            $this->responseError('商品信息有误');
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

        $soData = [
            'uid' => $user->id,
            'goods_name' => $good->goods_name,
            'goods_img' => $good->goods_img,
            'goods_price' => $good->goods_price,
            'coin_type' => $good->coin_type,
            'ore_pool' => $good->ore_pool,
            'status' => 1,
            'to_name' => $address->name,
            'to_mobile' => $address->mobile,
            'to_address' => $newAddress . $address->address_info,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 生成订单
            $so = ShopOrder::create($soData);

            // 商品销量加1
            ShopGoods::where('id', $good->id)->increment('sale_num');

            // 用户余额减少
            Account::reduceAmount($user->id, $coin->id, $good->goods_price);

            // 用户余额减少日志
            Service::account()->createLog($user->id, $coin->id, $good->goods_price, AccountLog::SCENE_GOODS_BUY);

            // 更新用户的附属表信息
            $this->updateUserLevel($user->id, $user->pid, $good->buy_count, $good->ore_pool, $user->pid_path);

            // 增加用户矿池余额记录
            UserWalletLog::addLog($user->id, 'shop_order', $so->id, '购买商品', '+', $good->ore_pool, 2, 1);

            // 释放订单表增加
            $reoData = [
                'uid' => $user->id,
                'total_num' => $good->ore_pool,
                'today_max' => bcmul(config('release.today_release_bl'), $good->ore_pool, 2),
                'release_time' => now()->subDay()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ];
            ReleaseOrder::create($reoData);

            // 判断上级用户是否是普通会员
            $pidUserLevel = UserInfo::where('uid', $user->pid)->first();
            if($pidUserLevel){

                // 判断用户是否有分红权限
                /*if($pidUserLevel->is_bonus != 1){
                    $this->updateBonus($user->pid);
                }*/

                // 判断上级级别
                if($pidUserLevel->level == 2){

                    // 上级推荐奖励
                    $pidReward = bcmul(config('shop.pid_reward'), $good->ore_pool, 2);
                    UserInfo::where('uid', $user->pid)->increment('buy_total', $pidReward);

                    // 释放订单表增加
                    $reoData = [
                        'uid' => $user->pid,
                        'total_num' => $pidReward,
                        'today_max' => $pidReward,
                        'release_time' => now()->subDay()->toDateTimeString(),
                        'type' => 1,
                        'created_at' => now()->toDateTimeString(),
                    ];
                    ReleaseOrder::create($reoData);


                    // 上级奖励日志
                    UserWalletLog::addLog($user->pid, 'shop_order', $so->id, '推荐奖励', '+', $pidReward, 2, 1);

                }

                if($pidUserLevel->level == 1){

                    // 上级推荐奖励
                    $pidReward = 200;
                    UserInfo::where('uid', $user->pid)->increment('buy_total', $pidReward);

                    // 释放订单表增加
                    $reoData = [
                        'uid' => $user->pid,
                        'total_num' => $pidReward,
                        'today_max' => $pidReward,
                        'release_time' => now()->subDay()->toDateTimeString(),
                        'type' => 1,
                        'created_at' => now()->toDateTimeString(),
                    ];
                    ReleaseOrder::create($reoData);

                    // 上级奖励日志
                    UserWalletLog::addLog($user->pid, 'shop_order', $so->id, '推荐奖励', '+', $pidReward, 2, 1);


                }

            }


            \DB::commit();

        } catch (\Exception $exception) {

            throw $exception;

            \DB::rollBack();

            \Log::info('商城订单购买出现异常');

            $this->responseError('购买异常');

        }


        // 队列递归更新用户管理权限
        dispatch(new UpdateAdminBonus($user->id));

        $this->responseSuccess('操作成功');

    }

    // 商品订单记录
    public function order(Request $request)
    {
        Service::auth()->isLoginOrFail();

        $res = ShopOrder::where('uid', Service::auth()->getUser()->id)
            ->select('id', 'goods_name', 'goods_img', 'goods_price', 'coin_type', 'ore_pool', 'created_at')
            ->latest('id')
            ->paginate($request->get('per_page', 10));

        return $this->response($res->toArray());

    }

    // 商品订单详情
    public function orderInfo($id)
    {
        Service::auth()->isLoginOrFail();

        $res = ShopOrder::select('id', 'goods_name', 'goods_img', 'goods_price', 'coin_type', 'ore_pool', 'to_name', 'to_mobile', 'to_address', 'created_at')
            ->where('id', $id)
            ->where('uid', Service::auth()->getUser()->id)
            ->first();
        if(!$res){
            $this->responseError('数据有误');
        }

        return $this->response($res->toArray());

    }

    // 更新用户级别
    private function updateUserLevel($uid, $pid, $buyCount, $num, $pidPath)
    {

        $userLevel = UserInfo::where('uid', $uid)->first();
        if($userLevel) {
            // 当用户级别存在的情况下

            $ulData = [
                'buy_total' => $userLevel->buy_total + $num,
                'buy_count' => $userLevel->buy_count + $buyCount
            ];

            // 当用户满足升为高级的情况下
            if(bcadd($userLevel->buy_count, $buyCount) >= 5){
                $ulData['level'] = 2;
            }else{
                $ulData['level'] = 1;
            }

            UserInfo::where('uid', $uid)->update($ulData);

            \Log::info('编辑了用户ID为'. $uid .'的用户级别信息', $ulData);

        }else{
            // 当用户级别不存在的情况下

            $level = ($buyCount >= 5) ? 2 : 1;

            $ulData = [
                'uid' => $uid,
                'pid' => $pid,
                'pid_path' => $pidPath,
                'level' => $level,
                'buy_total' => $num,
                'buy_count' => $buyCount,
            ];

            UserInfo::create($ulData);

            \Log::info('新增一条用户级别信息', $ulData);

        }

    }

    // 更新用户分红信息
    private function updateBonus($uid)
    {

        $status = false;

        // 判断用户推荐的用户
        $count5 = UserInfo::where('pid', $uid)->where('level', 2)->count();
        $count20 = UserInfo::where('pid', $uid)->count();

        if($count5 >= 5 || $count20 >= 20){
            $status = true;
        }

        // 达到分红权限以后
        if($status){

            // 判断用户是否已经有权限
            if(!UserBonus::where(['uid' => $uid, 'type' => 1])->exists()){

                $ubData = [
                    'uid' => $uid,
                    'type' => 1,
                    'created_at' => now()->toDateTimeString(),
                ];

                UserBonus::create($ubData);

                \Log::info('用户分红表新增一条数据', $ubData);

                $ulData = [
                    'is_bonus' => 1,
                ];

                UserInfo::where('uid', $uid)->update($ulData);

            }

        }

        return true;
    }


}
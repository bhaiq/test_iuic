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
use App\Models\User;
use App\Models\StarCommunity;
use App\Models\UserWalletLog;
use App\Models\CommunityDividend;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{

    // 商品列表
    public function goods()
    {

        $res = ShopGoods::oldest('id')->where('is_show','1')->get(['id', 'goods_name', 'goods_img', 'goods_info', 'goods_price', 'coin_type', 'ore_pool', 'goods_details', 'sale_num', 'created_at'])->toArray();
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
          
          	//IUIC社群奖(修改为奖励系数反比)
          	$this->star_community($user->id,$good->bonus_coefficient);

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
                    if($pidReward > 0){

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

                if($pidUserLevel->level == 1){

                    // 上级推荐奖励
                    $pidReward = bcmul(config('shop.pid_reward'), $good->ore_pool, 2);
                    $pidReward = $pidReward > 200 ? 200 : $pidReward;

                    if($pidReward > 0){

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

            }
			
          
          
          	//统计业绩
			$pid_path=trim(User::where('id',$user->id)->value('pid_path'), ',');
			$pid_arr=explode(',',$pid_path);
			$pids=User::where('star_community','>','0')->whereIn('id',$pid_arr)->pluck('id')->toArray();
          	
			
			foreach($pids as $v){
				$ucomm=CommunityDividend::where('uid',$v)->first();
				if($ucomm){
					CommunityDividend::where('uid',$v)->update(['this_month'=>$ucomm->this_month + $good->goods_price,'total'=>$ucomm->total + $good->goods_price]);
				}else{
					$data['uid']=$v;
					$data['this_month']=$good->goods_price;
					$data['total']=$good->goods_price;
					$data['created_at']=date('Y-m-d H:i:s',time());
					$data['updated_at']=date('Y-m-d H:i:s',time());
					DB::table('community_dividends')->insert($data);
				}
			}
          

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('商城订单购买出现异常');

            $this->responseError('购买异常');

        }


        // 队列递归更新用户管理权限
        dispatch(new UpdateAdminBonus($user->id, $good->bonus_coefficient));

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
  
  	//IUIC社群奖(极差制) 一条线上一共拿(0.25)从直推的第一个人开始往上
    public function star_community($uid,$bd_price){
      \Log::info('IUIC社群奖开始');
        $pid_path = User::where('id',$uid)->value('pid_path');
            //去尾部的逗号
            $pid_path1 = rtrim($pid_path, ",");
            //去除头部逗号
            $pid_path2 = ltrim($pid_path1, ",");
            //根据逗号拆分成数组
            $pids = explode(",",$pid_path2);
            // dd($pids);
            $teams = array();
            foreach ($pids as $k => $v) {
                //查到所有星级社群用户
                if(User::where('id',$v)->value('star_community') > 0){
                    array_unshift($teams,$v);
                }
            }
            //所得总比例
            $all_star_bl = StarCommunity::where('id',3)->value('star_bl');
      		
      		//$all_star_bl=$this->star_bl($uid);
      		//$arr_reward=bcmul($all_star_bl, $bd_price, 2);
      		//dd($arr_reward);
      		
            $yf_bl = 0;
            //已经返利过的比例
            $data_fbl = [];
            
            foreach ($teams as $k => $v) {
                //判断该比例是否返过
                if(in_array($this->star_bl($v),$data_fbl)){
                    continue;
                }
              	//如果已经返过0.2则不返0.1
              	if(count($data_fbl)>0){
                  if(max($data_fbl)>$this->star_bl($v)){
                      continue;
                  };
            	}
              	\Log::info('第'.$k.'次循环', ['all_star_bl' => $all_star_bl]);
                if($all_star_bl - $this->star_bl($v) > 0){
                    Account::where(['uid' => $v, 'coin_id' => 1, 'type' => Account::TYPE_LC])->increment('amount',$bd_price*$this->star_bl($v));
                    // 用户余额日志增加
                    AccountLog::addLog($v, 1, $bd_price*$this->star_bl($v), 103, 1, Account::TYPE_LC, 'IUIC社群奖');
                    array_push($data_fbl,$this->star_bl($v));
                  $yf_bl = bcadd($yf_bl,$this->star_bl($v),4);
                  \Log::info('反比',['uid'=>'$v','yf_bl'=>$this->star_bl($v)]);
                    $all_star_bl = bcsub($all_star_bl,$this->star_bl($v),4);
                    //\Log::info('第'.$k.'次循环', ['all_star_bl' => $all_star_bl]);
                }else{
                    //如果当前星级为二,则反0.1
                 
                    $star = StarCommunity::where('star_bl',$this->star_bl($v))->value('id');
                    if($star == 2){
                       Account::where(['uid' => $v, 'coin_id' => 1, 'type' => Account::TYPE_LC])->increment('amount',$bd_price*(bcsub($this->star_bl($v),$yf_bl,4)));
                       // 用户余额日志增加
                       AccountLog::addLog($v, 1, $bd_price*(bcsub($this->star_bl($v),$yf_bl,4)), 103, 1, Account::TYPE_LC, 'IUIC社群奖');
                      array_push($data_fbl,$this->star_bl($v));
                      $yf_bl = bcadd($yf_bl,bcsub($this->star_bl($v),$yf_bl,4),4);
                      \Log::info('反比',['uid'=>$v,'yf_bl'=>$yf_bl]);
                      $all_star_bl = bcsub($all_star_bl,bcsub($this->star_bl($v),$yf_bl,4),4);
                          }
                    if($star == 3){
                        Account::where(['uid' => $v, 'coin_id' => 1, 'type' => Account::TYPE_LC])->increment('amount',$bd_price*(bcsub($this->star_bl($v),$yf_bl,4)));
                        // 用户余额日志增加
                        AccountLog::addLog($v, 1, $bd_price*(bcsub($this->star_bl($v),$yf_bl,4)), 103, 1, Account::TYPE_LC, 'IUIC社群奖');
                        array_push($data_fbl,$this->star_bl($v));
                         \Log::info('IUIC社群奖结束');                       
                          }

                        }
            }
            
    	}
    //社群奖励比例
    public function star_bl($uid){
        $star_community = User::where('id',$uid)->value('star_community');
        $star_bl = StarCommunity::where('id',$star_community)->value('star_bl');
        return $star_bl;
    }


}
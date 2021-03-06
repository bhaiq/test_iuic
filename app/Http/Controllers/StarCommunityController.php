<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Service;
use App\Models\StarCommunity;
use App\Models\User;
use App\Models\Coin;
use App\Models\Agreement;
use App\Models\StarOrder;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\CommunityDividend;

class StarCommunityController extends Controller
{
    //星级列表信息
    public function index()
    {
        Service::auth()->isLoginOrFail();
        $stars = StarCommunity::all();
        $bid = User::where('id',Service::auth()->getUser()->id)->value('star_community');
        $data = [];
        foreach ($stars as $k => $v){
            $data[$k]['id'] = $v->id;
            $data[$k]['name'] = $v->name;
            $data[$k]['price'] = round($v->price, 0);
            if($v->id == 1){
                $data[$k]['star_bl'] = bcmul(config('senior_admin.senior_admin_1_reward_bl')*100, 1,4);
            }else if($v->id == 2){
                $data[$k]['star_bl'] = bcmul(config('senior_admin.senior_admin_2_reward_bl')*100, 1,4);
            }else if($v->id == 3){
                $data[$k]['star_bl'] = bcmul(config('senior_admin.senior_admin_3_reward_bl')*100, 1,4);
            }

        }
      
      	$my_yeji=CommunityDividend::where('uid',Service::auth()->getUser()->id)->first();
      	
      	//dd($my_yeji);
      	if(!empty($my_yeji)){
        	//本月新增累计
          	$this_month=$my_yeji->this_month;

         	//总共累计
          	$total=$my_yeji->total;

        }else{
        	//本月新增累计
          	$this_month=0;

         	//总共累计
          	$total=0;
        
        }
      	
      	//所在挡位
      	$lv1=config('senior_admin.community_lv1');
      	$lv2=config('senior_admin.community_lv2');
      	$lv3=config('senior_admin.community_lv3');
      	$lv4=config('senior_admin.community_lv4');
      	
      	if($total>=$lv4){
        	$dangwei= trans('api.fourth_gear');
        }elseif($total>=$lv3){
        	$dangwei= trans('api.third_gear');
        }elseif($total>=$lv2){
        	$dangwei= trans('api.second_gear');
        }elseif($total>=$lv1){
        	$dangwei= trans('api.first_gear');
        }else{
        	$dangwei= trans('api.not_have');
        }
      	
      	//当日新增
        $start_time = Carbon::now()->startOfDay();
        $end_time = Carbon::now()->endOfDay();
      	$sons=User::where('pid_path','like','%'.','.Service::auth()->getUser()->id.','.'%')->pluck('id')->toArray();
      	$today=StarOrder::whereBetween('created_at',[$start_time,$end_time])->whereIn('uid',$sons)->get();
      	//下级购买商品所给业绩
      	$todays = ShopOrder::whereBetween('created_at',[$start_time,$end_time])->whereIn('uid',$sons)->get();
      	$today_total=0;
      	foreach($today as $v){
        	$today_total+=$v->shop_price;
        }
      	foreach ($todays as $v){
            $today_total+=$v->goods_price;
        }
      
        return $this->response(['data' => $data,'star'=>$bid,'total'=>$total,'this_month'=>$this_month,'today_total'=>$today_total,'dangwei'=>$dangwei]);
      
        
    }
    //点击购买反差价
    public function price_spread(Request $request)
    {
        Service::auth()->isLoginOrFail();
        //判断是否有该星群
        $buy_id = $request->buy_id;
        $bid = User::where('id',Service::auth()->getUser()->id)->value('star_community');
        if($bid >= $buy_id && $bid > 0){
            return $this->responseError(trans('api.you_can_current_one'));
        }
        //算差价
        $now_starprice = StarCommunity::where('id',$bid)->value('price');
//        dd($bid);
        $price_spread = StarCommunity::where('id',$buy_id)->value('price') - $now_starprice;
        return $this->response(['data'=>$price_spread]);
    }

    //购买商品时弹出的协议
    public function agreement()
    {
        Service::auth()->isLoginOrFail();
        $agreement = Agreement::where('id',1)->value('agreements');
        $data['agreement'] = $agreement;
        $data['switchs'] = 1; //1关闭2开启
        return $this->response(['data'=>$data]);
    }
    //购买接口
    public function buy(Request $request)
    {	
      	
        $this->validate($request->all(), [
            'buy_id' => 'required|integer',
            'paypass' => 'required',
        ], [
            'buy_id.required' => trans('api.information_cannot_empty'),
            'paypass.required' => trans('api.trade_password_cannot_empty'),
        ]);
        $buy_id = $request->buy_id;
        $paypass = $request->paypass;
      	
        $bid = User::where('id',Service::auth()->getUser()->id)->value('star_community');
      	
        if($bid >= $buy_id && $bid > 0){
            return $this->responseError(trans('api.you_can_current_one'));
        }
        //算差价
        $now_starprice = StarCommunity::where('id',$bid)->value('price');
      	
        $price_spread = StarCommunity::where('id',$buy_id)->value('price') - $now_starprice;
      	
        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));
        // 验证商品信息
        $good = StarCommunity::where('id', $request->get('buy_id'))->first();
        if(!$good){
            $this->responseError(trans('api.incorrect_commodity_information'));
        }
        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);
		
        // 判断用户余额是否充足
        $totalPrice = $good->price;
        if($coinAccount->amount < $price_spread){
            $this->responseError(trans('api.insufficient_user_balance'));
        }
        $eoData = [
            'uid' => Service::auth()->getUser()->id,
            'shop_id' => $good->id,
            'shop_name' => $good->name,
            'shop_price' => $price_spread,//差价
            // 'created_at' => now()->toDateTimeString(),
        ];
      	
       \DB::beginTransaction();
        try {

            // 兑换表新增
            $ee = StarOrder::create($eoData);
          
            // 币种资产减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $price_spread);
            // 用户余额资产记录增加
//            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $price_spread, 102, 1, Account::TYPE_LC, '购买星级社群');
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $price_spread, 102, 1, Account::TYPE_LC, '购买运营中心');
			//用户升星级
          	User::where('id',Service::auth()->getUser()->id)->update(['star_community' => $good->id]);
          	//给上级返利(社群分享奖)
//            \Log::info('社群分享奖开启');
            \Log::info('运营中心分享奖开启');
            $pid = User::where('id',Service::auth()->getUser()->id)->value('pid');
          	
            Account::addAmount($pid, 1, $price_spread*config('energy.share_star_reward',0));
//            AccountLog::addLog($pid, 1, $price_spread*config('energy.share_star_reward',0), 101, 1, Account::TYPE_LC, '社群分享奖');
            AccountLog::addLog($pid, 1, $price_spread*config('energy.share_star_reward',0), 101, 1, Account::TYPE_LC, '运营中心分享奖');
//            \Log::info('社群分享奖结束');
            \Log::info('运营中心分享奖结束');

          
//          	\Log::info('社群分享奖-伞下  开始');
          	\Log::info('运营中心分享奖-伞下  开始');
          		$pid_path=trim(User::where('id',Service::auth()->getUser()->id)->value('pid_path'),',');
          		
          		$pids=explode(',',$pid_path);
          		$list=User::where('star_community','>','0')->whereIn('id',$pids)->get();
				//$nn="";
          		foreach($list as $v){
                	$community_sanxia_bl=User::where('id',$v->id)->value('community_sanxia_bl');
                  
                  	if($community_sanxia_bl <= 0){
                    	continue;
                    }

                  	$reward_num=bcmul($price_spread,$community_sanxia_bl,2);
                  	//为给个合伙人账户加usdt
        			$m=Account::addAmount($v->id,1,$reward_num);
        			// 用户余额日志表更新
//        			$n=AccountLog::addLog($v->id,1,$reward_num, 29, 1, Account::TYPE_LC, '社群分享奖-伞下');
        			$n=AccountLog::addLog($v->id,1,$reward_num, 29, 1, Account::TYPE_LC, '运营分享奖-伞下');
					//$nn.=$v->id.",".$price_spread.",".$community_sanxia_bl.",".$reward_num;
                }
          		//dd($nn);
//          	\Log::info('社群分享奖-伞下  结束');
          	\Log::info('运营中心分享奖-伞下  结束');

          
          
          
            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

//            \Log::info('购买星级社群异常');
            \Log::info('购买运营中心异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfully'));

    }


    
}

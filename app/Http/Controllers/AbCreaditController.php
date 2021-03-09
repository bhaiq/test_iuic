<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\CreaditTransfer;
use App\Models\EcologyBuyRmb;
use App\Models\EcologyConfig;
use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\EcologyCreaditOrder;
use App\Models\ExOrder;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\UserWallet;
use App\Services\Service;
use App\Services\UpEcologyLv;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AbCreaditController extends Controller
{
    //积分购买列表
    public function creadits_list()
    {
        $list = EcologyBuyRmb::where('is_show','1')->orderby('sort','desc')->get();
        $data = [];
        $now_price = json_decode(json_encode(ExOrder::market(0, 60)),true);
        $data['times'] = (string)EcologyConfigPub::where('id',1)->value('point_multiple');
        $data['cny'] = (string)$now_price[0]['cny'];
        foreach ($list as $k => $v){
            $data['list'][$k]['num'] = (string)$v->branch_num;
        }
        return $this->response(['data'=>$data]);
    }

    //购买积分(扣除法币可用iuic,加积分,加等额锁定矿池)
    public function buy_creadits(Request $request)
    {
//        $this->responseSuccess('暂未开放');
//        return;

        $time = time();
        if(!empty(session('time'))){
            if($time <= session('time')+5){
                return $this->responseError(trans('api.request_is_frequent'));
            }
        }
        session(['time'=>$time]);

        //获取购买价格金额
        $price = $request->get('num');
        if ($price%10000 !=0){
            return $this->responseError(trans('api.parameter_is_wrong'));
        }
        $uid = Service::auth()->getUser()->id;
        $user = User::where('id',$uid)->first();
        //获取上一笔订单时间
        $last_order = New EcologyCreaditOrder();
        $last_order = $last_order->where('uid',$uid)->orderby('id','desc')->first();
        if(strtotime($last_order['created_at'])+10 < time() ){
            $this->responseSuccess(trans('api.request_is_frequent'));
            return;
        }
//        //获取iuic当前价格
        $now_price = json_decode(json_encode(ExOrder::market(0, 60)),true);
//        return $now_price[0]['cny'];
        //计算扣除法币的iuic
        $freeze_iuic = $price/$now_price[0]['cny'];
        //计算赠送的冻结积分
        $freeze_creadit = $price * EcologyConfigPub::where('id',1)->value('point_multiple');
        //判断余额是否足够
        $user_iuic_balance = Account::where('uid',$uid)
                            ->where('coin_id',2)
                            ->where('type',1)
                            ->value('amount');
        if($user_iuic_balance < $freeze_iuic){
            return $this->responseError(trans('api.insufficient_user_balance'));
        }
        $creadit_m = New EcologyCreadit();
        //扣可用法币iuic,加积分,加iuic矿池,生成订单
        \DB::beginTransaction();
        try{
            //扣可用法币iuic
            Account::reduceAmount($uid,'2',$freeze_iuic);
            AccountLog::addLog($uid,2,$freeze_iuic,'33','0','1','购买积分');
            //加积分
//            EcologyCreadit::a_o_m('uid',$uid)->increment('amount_freeze',$freeze_creadit);
            EcologyCreadit::a_o_m($uid,$freeze_creadit,1,1,'购买积分',2);
            //加iuic矿池(没有矿池创建矿池)
            if(empty(UserInfo::where('uid',$uid)->first())){
                $ulData = [
                    'uid' => $uid,
                    'pid' => $user->pid,
                    'pid_path' => $user->pid_path,
                    'level' => 1,
                    'buy_total' => 0,
                    'buy_count' => 0,
                ];

                UserInfo::create($ulData);
            }
            UserInfo::where('uid', $uid)->increment('buy_total', $freeze_iuic);
            //生成订单
            $order = New EcologyCreaditOrder();
            $order->uid = $uid;
            $order->price_cny = $price;
            $order->creadit_amount = $freeze_creadit;
            $order->already_amount = 0;
            $order->iuic_amount = $freeze_iuic;
            $order->now_price = $now_price[0]['cny'];
            $order->save();
            $creadit_m->ecology_share_reward($uid,$price);
            //给自己升合格消费者(如果自己等级低)
            if($user->ecology_lv = 1){
                User::where('id',$uid)->update(['ecology_lv'=>2,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            }
            //升级
            $level = New UpEcologyLv();
            $level->up_ecology_lv($uid);
            //若已进入24小时内报单,将时间清除
            EcologyCreadit::where('uid',$uid)->update(['release_end_time'=>null]);
            //车奖加入累计
            $car_rate = EcologyConfigPub::where('id',1)->value('car_rate');
            EcologyConfigPub::where('id',1)->increment('car_total',$price*$car_rate);
            EcologyConfigPub::where('id',1)->increment('car_surplus',$price*$car_rate);
            \DB::commit();
        }catch (\Exception $e){
            \DB::rollBack();
            Log::info($e->getMessage());
            return $this->responseError($e->getMessage());
        }
        $this->responseSuccess(trans('api.operate_successfully'));

    }

    public function  mu()
    {
        $now_price = json_decode(json_encode(ExOrder::market(0, 60)),true);
        return $this->response($now_price);
    }

    // 划转页面数据获取
    public function transferStart()
    {

        Service::auth()->isLoginOrFail();
        $num = 0;
        $bl = bcmul(EcologyConfigPub::where('id',1)->value('rate'),100);
        $now_price = json_decode(json_encode(ExOrder::market(0, 60)),true);
        // 获取用户积分资产信息
        $uw = EcologyCreadit::where('uid', Service::auth()->getUser()->id)->first();
        if($uw){
            $num = bcmul($uw->amount, 1, 2);
        }

        $result = [
            'num' => $num,
            'bl' => $bl,
            'cny' => $data['cny'] = (string)$now_price[0]['cny'],
            'pirce' => $data['cny'] = (string)$now_price[0]['price'],
        ];

        return $this->response($result);

    }

    // 积分划转提交
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

        // 获取用户积分资产信息
        $uw = EcologyCreadit::where('uid', Service::auth()->getUser()->id)->first();
        if(!$uw || $uw->amount < $request->get('num')){
            $this->responseError(trans('api.insufficient_user_balance'));
        }
        $creadit_m = New EcologyCreadit();
        //实际到账数量
        $true_num = $request->get('num') - $request->get('num')*EcologyConfigPub::where('id',1)->value('rate');
        $now_price = json_decode(json_encode(ExOrder::market(0, 60)),true);
        //折合usdt
        $true_num = bcmul(bcdiv($true_num,$now_price[0]['cny'],4),$now_price[0]['price'],4);
        $data = [
            'uid' => Service::auth()->getUser()->id,
            'num' => $request->get('num'),
            'charge_rate' => EcologyConfigPub::where('id',1)->value('rate'),
            'service_charge' => $request->get('num')*EcologyConfigPub::where('id',1)->value('rate'),
            'true_num' => $true_num,
            'created_at' => now()->toDateTimeString(),
            'usdt_cny' => $now_price[0]['price']."/".$now_price[0]['cny'],
        ];

        \DB::beginTransaction();
        try {

            // 积分资产划转表新增
            CreaditTransfer::create($data);

            // 可用积分减少
            EcologyCreadit::a_o_m(Service::auth()->getUser()->id, $request->get('num'),2,2,'划转',1);

            // 用户法币USDT增加
//            UserInfo::addBuyTotal(Service::auth()->getUser()->id, $request->get('num'));
            Account::addAmount(Service::auth()->getUser()->id,1,$true_num);
            AccountLog::addLog(Service::auth()->getUser()->id,'1',$true_num,'34','1',
                '1','积分划转');
            //生态2手续费团队长奖
            $creadit_m->ecology_team_reward(Service::auth()->getUser()->id,$data['service_charge']);
            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();
            \Log::info('积分资产划转异常'.$exception->getMessage());
            $this->responseError(trans('api.wrong_operation'));
        }
        $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 个人推荐信息
    public function info()
    {

        Service::auth()->isLoginOrFail();

        $res = $this->getUserInfo(Service::auth()->getUser()->id);
        if(!$res){
            $this->responseError(trans('api.parameter_is_wrong'));
        }
//         dd($res);
//        if(Service::auth()->getUser()->id == 1){
//
//            $arr = [
//                'pt_user_count' => UserInfo::where('level', 1)->count(),
//                'gj_user_count' => UserInfo::where('level', 2)->count(),
//            ];
//
//            $res = array_merge($res, $arr);
//        }

        return $this->response($res);

    }

    //生态2数据
    public function user_list(Request $request)
    {
        Service::auth()->isLoginOrFail();

        $result  = [];
        // 获取用户的推荐用户
        $user = User::where('pid', Service::auth()->getUser()->id)->paginate($request->get('per_page', 10));

        foreach ($user as $k => $v){

            $res = $this->getUserInfo($v->id);

            if($res){
                $result[] = $res;
            }
        }

        return $this->response($result);
    }

    //用户信息
    public function getUserInfo($uid){
        $user = User::where('id', $uid)->first();
        $info = New EcologyCreadit();
        if(!$user){
            return false;
        }
        if($user){
            return [
                'avatar' => $user->avatar,
                'nickname' => $user->nickname,
                'ecology_lv' => $info->get_ecology_lv($user->ecology_lv), //生态等级
                'team_all' => (string)$info->team_all($uid), //团队总人数
                'new_people' => (string)$info->new_people($uid), //新增人数
                'zong_yj' => (string)$info->zong_yj($uid), //总业绩
                'day_yj' => (string)$info->day_yj($uid), //日业绩
                'month_yj' => (string)$info->month($uid), //月业绩
                'first_ecology' => (string)$info->first_ecology($uid), //一级生态
                'two_ecology' => (string)$info->two_ecology($uid), //二级生态
                'three_ecology' => (string)$info->three_ecology($uid), //三级生态
                'four_ecology' => (string)$info->four_ecology($uid), //四级生态
                'five_ecology' => (string)$info->five_ecology($uid), //五级生态
            ];
        }
    }

    //车奖排行榜
    public function ranking_list(Request $request)
    {
        $list = User::where('car_is_show',1)
            ->where('ecology_lv','>=',3)
            ->orderBy('ecology_lv','desc')
            ->orderBy('ecology_lv_time')
            ->take(50)
            ->get();
        $data['list'][0]['nickname'] = "";
        $data['list'][0]['ecology_lv'] = "";
//        $data = [];

            foreach ($list as $k => $v){
                $data['list'][$k]['nickname'] = $v->nickname;
                $data['list'][$k]['ecology_lv'] = $this->ecology($v->ecology_lv);
                $data['list'][$k]['created_at'] =$v->ecology_lv_time;
            }
        $data['reward'] = EcologyConfigPub::where('id',1)->value('car_surplus');
        return $this->response($data);
    }

    //生态等级
    public function ecology($id)
    {
        switch ($id)
        {
            case 3:
                return "一级生态";
                break;
            case 4:
                return "二级生态";
                break;
            case 5:
                return "三级生态";
                break;
            case 6:
                return "四级生态";
                break;
            case 7:
                return "五级生态";
                break;
        }
    }





}

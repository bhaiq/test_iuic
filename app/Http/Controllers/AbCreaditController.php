<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\EcologyBuyRmb;
use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\EcologyCreaditOrder;
use App\Models\ExOrder;
use App\Models\UserInfo;
use App\Services\Service;
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
        $data['times'] = EcologyConfigPub::where('id',1)->value('point_multiple');
        foreach ($list as $k => $v){
            $data['list'][$k]['num'] = $v->branch_num;
        }
        return $this->response(['data'=>$data]);
    }

    //购买积分(扣除法币可用iuic,加积分,加等额锁定矿池)
    public function buy_creadits(Request $request)
    {
        $time = time();
        if(!empty(session('time'))){
            if($time <= session('time')+5){
                return $this->responseError('请求频繁');
            }
        }
        session(['time'=>$time]);
        //获取购买价格金额
        $price = $request->get('num');
        $uid = Service::auth()->getUser()->id;
//        //获取iuic当前价格
        $now_price = $this->response(ExOrder::market(0, 60));
        $datas = [];
        foreach ($now_price as $k => $v){
            $datas['cny'] = $v;
        }
        Log::info("数据1".$datas);
        dd($now_price[0]->cny);
        //计算赠送冻结的iuic
        $freeze_iuic = $price/$now_price['cny'];
        //计算赠送的冻结积分和所花费的法币可用iuic
        $freeze_creadit = $price * EcologyConfigPub::where('id',1)->value('point_multiple');
        //判断余额是否足够
        $user_iuic_balance = Account::where('uid',$uid)
                            ->where('coin_id',2)
                            ->where('type',1)
                            ->value('amount');
        if($user_iuic_balance < $freeze_creadit){
            return $this->responseError('余额不足');
        }
        //扣可用法币iuic,加积分,加iuic矿池,生成订单
        \DB::beginTransaction();
        try{
            //扣可用法币iuic
            Account::reduceFrozen($uid,'2',$freeze_creadit);
            AccountLog::addLog($uid,2,$freeze_creadit,'33','1','1','购买积分');
            //加积分
            EcologyCreadit::where('uid',$uid)->increment('amount',$user_iuic_balance);
            //加iuic矿池
            UserInfo::where('uid', $uid)->increment('buy_total', $price);
            //生成订单
            $order = New EcologyCreaditOrder();
            $data['uid'] = $uid;
            $data['creadit_amount'] = $user_iuic_balance;
            $data['already_amount'] = 0;
            $data['iuic_amount'] = $freeze_creadit;
            $order->save($data);
            \DB::commit();
        }catch (\Exception $e){
            \DB::rollBack();
            return $this->responseError("数据错误");
        }
        return $this->response("操作成功");
    }

    public function  mu()
    {
        $uid = "1";
        $wallet = New EcologyCreadit();
        $wallet->created_wallet($uid);
    }

    //用户余额
    public function user_balance(Request $request)
    {

    }

    //用户余额记录
    public function balance_log(Request $request)
    {

    }
}

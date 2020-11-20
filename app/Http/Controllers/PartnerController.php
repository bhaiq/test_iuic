<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/19
 * Time: 10:44
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\CommunityDividend;
use App\Models\User;
use App\Models\UserPartner;
use App\Models\UserWallet;
use App\Services\Service;
use Illuminate\Http\Request;

class PartnerController extends Controller
{

    // 合伙人页面
    public function start(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = [
            'cost' => config('user_partner.cost'),
            'max_count' => config('user_partner.count'),
            'purchased_count' => UserPartner::sum('count'),
        ];

        return $this->response($result);

    }

    // 合伙人提交
    public function submit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'num'     => 'required|integer',
        ], [
            'num.required' => trans('api.quantity_cannot_empty'),
        ]);

        // 判断用户是否是合伙人
        $user = Service::auth()->getUser();
        if(!$user || $request->get('num') > 5 || $request->get('num') < 1){
            $this->responseError(trans('api.parameter_is_wrong'));
        }
        if($user->is_partner != 0 || UserPartner::where('uid', $user->id)->exists()){
            $this->responseError(trans('api.user_already_partner_has_applied'));
        }

        // 操作频繁锁
        UserPartner::getSubmitLock($user->uid);

        $coin = Coin::getCoinByName('USDT');
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        // 获取一份的价格
        $cost = config('user_partner.cost');

        // 当前总价钱
        $total = bcmul($cost, $request->get('num'), 4);

        // 判断用户余额是否充足
        if($coinAccount->amount < $total){
            $this->responseError(trans('api.insufficient_user_balance'));
        }

        $upData = [
            'uid' => $user->id,
            'count' => $request->get('num'),
            'num' => $total,
            'coin_id' => $coin->id,
            'status' => 0,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 合伙人表新增
            UserPartner::create($upData);

            //给上级加业绩
            $pid_path=trim(User::where('id',$user->id)->value('pid_path'), ',');
            $pid_arrs = explode(',',$pid_path);
            $pid_arr = array_diff($pid_arrs, ["0"]);

            foreach($pid_arr as $v){
                $ucomm=CommunityDividend::where('uid',$v)->first();
                if($ucomm){
                    CommunityDividend::where('uid',$v)->update(['this_month'=>$ucomm->this_month + $total,
                        'total'=>$ucomm->total + $total,'true_num'=>$ucomm->true_num + $total,
                        'true_total'=>$ucomm->true_total + $total
                        ]);
                }else{
                    $data['uid']=$v;
                    $data['this_month']=$total;
                    $data['true_num']=$total;
                    $data['total']=$total;
                    $data['true_total']=$total;
                    $data['created_at']=date('Y-m-d H:i:s',time());
                    $data['updated_at']=date('Y-m-d H:i:s',time());
                    \DB::table('community_dividends')->insert($data);
                }
            }

            // 用户表状态改变
            $user->is_partner = 2;
            $user->save();

            // 用户余额减少
            Account::reduceAmount($user->id, $coin->id, $total);

            // 用户冻结余额增加
            Account::addFrozen($user->id, $coin->id, $total);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('合伙人申请异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 合伙人收益记录
    public function log(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = AccountLog::select('coin_id', 'amount as num', 'type', 'remark as exp', 'created_at')
            ->where('scene', 18)
            ->where('remark', '合伙人分红')
            ->where('uid', Service::auth()->getUser()->id)
            ->latest('id')
            ->paginate($request->get('per_page', 10))
            ->toArray();

        foreach ($result['data'] as $k => $v){
            $result['data'][$k]['sign'] = $v['type'] ? '+' : '-';
            $result['data'][$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
            $result['data'][$k]['unit'] = 'USDT';

            if($v['coin_id'] == 2){
                $result['data'][$k]['unit'] = 'IUIC';
            }else{
                $result['data'][$k]['unit'] = 'USDT';
            }

            unset($result['data'][$k]['type']);
        }

        $res = [
            'total_num' => 0,
            'usdt_total_num' => AccountLog::where(['uid' => Service::auth()->getUser()->id, 'coin_id' => 1, 'scene' => 18, 'remark' => '合伙人分红'])->sum('amount'),
            'iuic_total_num' => AccountLog::where(['uid' => Service::auth()->getUser()->id, 'coin_id' => 2, 'scene' => 18, 'remark' => '合伙人分红'])->sum('amount'),
        ];

        return $this->response(array_merge($result, $res));

    }

    //
    public function jl_ceshi(Request $request)
    {
        //将原本业绩(this_month)复制一份到(true_num)
//        $list = CommunityDividend::all();
//        foreach ($list as $k => $v)
//        {
//            CommunityDividend::where('id',$v->id)->update(['true_total'=>$v->total]);
//        }
//        $this->responseSuccess(trans('user.auth.exist'));

        //清除所有用户的能量资产
        //能量资产清空
        $users = User::select('id')->get();
        dd($users);
        foreach ($users as $k=>$user){
            UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_frozen_num'=>'0','consumer_num'=>'0','energy_lock_num'=>'0']);
        }

    }

}
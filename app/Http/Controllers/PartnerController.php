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
use App\Models\UserPartner;
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
            'num.required' => '份数不能为空',
        ]);

        // 判断用户是否是合伙人
        $user = Service::auth()->getUser();
        if(!$user || $request->get('num') > 5 || $request->get('num') < 1){
            $this->responseError('数据有误');
        }
        if($user->is_partner != 0 || UserPartner::where('uid', $user->id)->exists()){
            $this->responseError('用户已经是合伙人或已经申请了');
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
            $this->responseError('用户余额不足');
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
            $pid_arr=explode(',',$pid_path);


            foreach($pid_arr as $v){
                $ucomm=CommunityDividend::where('uid',$v)->first();
                if($ucomm){
                    CommunityDividend::where('uid',$v)->update(['this_month'=>$ucomm->this_month + $total,'total'=>$ucomm->total + $total]);
                }else{
                    $data['uid']=$v;
                    $data['this_month']=$total;
                    $data['total']=$total;
                    $data['created_at']=date('Y-m-d H:i:s',time());
                    $data['updated_at']=date('Y-m-d H:i:s',time());
                    DB::table('community_dividends')->insert($data);
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

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

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

}
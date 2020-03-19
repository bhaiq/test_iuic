<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2020/3/18
 * Time: 14:08
 */

namespace App\Http\Controllers;

use App\Models\EnergyLockTransfer;
use App\Models\EnergyLog;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\Service;
use Illuminate\Http\Request;

class EnergyLockController extends Controller
{

    // 锁仓能量转账页面数据获取
    public function transferStart()
    {

        Service::auth()->isLoginOrFail();

        $num = 0;

        // 获取用户能量资产信息
        $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
        if($uw){
            $num = bcmul($uw->energy_lock_num, 1, 4);
        }

        $result = [
            'num' => $num,
        ];

        return $this->response($result);

    }

    // 锁仓能量转账提交
    public function transfer(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'num' => 'required|integer|min:0',
            'new_account' => 'required',
            'paypass' => 'required',
        ], [
            'num.required' => '数量不能为空',
            'num.integer' => '数量必须是整数',
            'num.min' => '数量不能小于1',
            'paypass.required' => '交易密码不能为空',
        ]);

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 验证账号是否正确
        $toUser = User::where('new_account', $request->get('new_account'))->first();
        if(!$toUser){
            $this->responseError('账号不存在');
        }

        // 不能给自己转账
        if($toUser->id == Service::auth()->getUser()->id){
            $this->responseError('不能给自己转账');
        }

        // 获取用户能量资产信息
        $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
        if(!$uw || $uw->energy_lock_num < $request->get('num')){
            $this->responseError('余额不足');
        }

        $data = [
            'uid' => Service::auth()->getUser()->id,
            'account' => $request->get('new_account'),
            'num' => $request->get('num'),
            'to_uid' => $toUser->id,
            'created_at' => now()->toDateTimeString()
        ];

        \DB::beginTransaction();
        try {

            // 锁仓能量转账表新增
            $eat = EnergyLockTransfer::create($data);

            // 锁仓能量减少
            UserWallet::reduceEnergyLockNum(Service::auth()->getUser()->id, $request->get('num'));

            // 能量日志增加
            EnergyLog::addLog(Service::auth()->getUser()->id, 3, 'energy_lock_transfer', $eat->id, '转出', '-', $request->get('num'), 4);

            // 对方锁仓能量增加
            UserWallet::addEnergyLockNum($toUser->id, $request->get('num'));

            // 能量日志增加
            EnergyLog::addLog($toUser->id, 3, 'energy_lock_transfer', $eat->id, '转入', '+', $request->get('num'), 4);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('锁仓能量转账异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 获取锁仓能量转账日志
    public function transferLog(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = EnergyLockTransfer::from('energy_lock_transfer as elt')
            ->select('elt.num', 'elt.created_at', 'u.nickname')
            ->join('user as u', 'u.id', 'elt.to_uid')
            ->where('uid', Service::auth()->getUser()->id)
            ->latest()
            ->paginate($request->get('per_page', 10))
            ->toArray();

        foreach ($result['data'] as $k => $v){

            $result['data'][$k]['exp'] = '能量资产转出';
            $result['data'][$k]['status'] = 1;
            $result['data'][$k]['status_name'] = '已完成';
            $result['data'][$k]['created_at'] = date('Y/m/d H:i:s', strtotime($v['created_at']));

        }

        return $this->response($result);

    }

}
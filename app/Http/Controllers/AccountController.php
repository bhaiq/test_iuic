<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\EcologyCreadit;
use App\Models\EcologyCreaditLog;
use App\Models\EnergyLog;
use App\Models\UserWallet;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;

class AccountController extends Controller
{
    public function _listSecret()
    {
        $data['account'][] = ['amount' => 0.0000, 'amount_freeze' => 0.0000, 'amount_cny' => 0.0000, 'amount_freeze_cny' => 0.0000, 'cny' => 0.0000, 'coin' => ['name' => 'BTC']];
        $data['account'][] = ['amount' => 0.0000, 'amount_freeze' => 0.0000, 'amount_cny' => 0.0000, 'amount_freeze_cny' => 0.0000, 'cny' => 0.0000, 'coin' => ['name' => 'ETH']];
        return $this->response($data);
    }

    public function _list(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $type            = $request->get('type', 0);

        if(!in_array($type, [0, 1, 2,3])){
            $this->responseError(trans('api.parameter_is_wrong'));
        }

        if($type == 2){

            $result['account'][] = [
                'coin_id' => 1001,
                'type' => 0,
                'amount' => 0,
                'amount_freeze' => 0,
                'total' => 0,
                'amount_cny' => 0,
                'amount_freeze_cny' => 0,
                'cny' => 0,
                'coin' => [
                    'id' => 1001,
                    'name' => trans('api.energy')
                ],
                'is_open' => 1,

            ];

            $result['account'][] = [
                'coin_id' => 1003,
                'type' => 0,
                'amount' => 0,
                'amount_freeze' => 0,
                'total' => 0,
                'amount_cny' => 0,
                'amount_freeze_cny' => 0,
                'cny' => 0,
                'coin' => [
                    'id' => 1003,
                    'name' => trans('api.lock_up_energy')
                ],
                'is_open' => 1,

            ];

            $result['account'][] = [
                'coin_id' => 1002,
                'type' => 0,
                'amount' => 0,
                'amount_freeze' => 0,
                'total' => 0,
                'amount_cny' => 0,
                'amount_freeze_cny' => 0,
                'cny' => 0,
                'coin' => [
                    'id' => 1001,
                    'name' => trans('api.consumption_points')
                ],
                'is_open' => 1,

            ];

            // 先验证用户是否有能量账户，没有则创建
            $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
            if(!$uw){
                $uwData = [
                    'uid' => Service::auth()->getUser()->id,
                    'created_at' => now()->toDateTimeString(),
                ];
                $uw = UserWallet::create($uwData);
            }

            $result['account'][0]['amount'] = bcmul($uw->energy_num, 1, 4);
            $result['account'][0]['amount_freeze'] = bcmul($uw->energy_frozen_num, 1, 4);
            $result['account'][0]['total'] = bcmul($uw->total, 1, 4);
            $result['account'][0]['amount_cny'] = bcmul($uw->energy_cny, 1, 4);
            $result['account'][0]['amount_freeze_cny'] = bcmul($uw->energy_frozen_cny, 1, 4);
            $result['account'][0]['cny'] = bcmul($uw->total_cny, 1, 4);


            $result['account'][1]['amount'] = bcmul($uw->energy_lock_num, 1, 4);
            $result['account'][1]['amount_freeze'] = 0;
            $result['account'][1]['total'] = bcmul($uw->energy_lock_num, 1, 4);
            $result['account'][1]['amount_cny'] = bcmul($uw->energy_lock_num, 1, 4);
            $result['account'][1]['amount_freeze_cny'] = 0;
            $result['account'][1]['cny'] = bcmul($uw->energy_lock_num, 1, 4);

            $result['account'][2]['amount'] = bcmul($uw->consumer_num, 1, 4);
            $result['account'][2]['amount_freeze'] = 0;
            $result['account'][2]['total'] = bcmul($uw->consumer_num, 1, 4);
            $result['account'][2]['amount_cny'] = bcmul($uw->consumer_cny, 1, 4);
            $result['account'][2]['amount_freeze_cny'] = 0;
            $result['account'][2]['cny'] = bcmul($uw->consumer_cny, 1, 4);

            $result['cur_total'] = bcdiv(bcadd($uw->total_cny, $uw->consumer_cny, 8), Account::getRate(), 4);
            $result['cur_total_cny'] = bcadd($uw->total_cny, $uw->consumer_cny, 4);
            $result['all_total'] = 0;
            $result['all_total_cny'] = 0;

            $data_other = Service::auth()->getUser()->account()->with('coin')->get()->toArray();

            foreach ($data_other as $k => $v) {
                $result['all_total']     = bcadd($result['all_total'], $v['cny'], 4);
                $result['all_total_cny'] = bcadd($result['all_total_cny'], $v['cny'], 4);
            }

            $result['all_total'] = bcdiv($result['all_total'], Account::getRate(), 4);

            return $this->response($result);

        }

        //积分
        if($type == 3){

            $result['account'][] = [
                'coin_id' => 1004,
                'type' => 0,
                'amount' => 0,
                'amount_freeze' => 0,
                'total' => 0,
                'amount_cny' => 0,
                'amount_freeze_cny' => 0,
                'cny' => 0,
                'coin' => [
                    'id' => 1004,
                    'name' => trans('api.creadit')
                ],
                'is_open' => 1,

            ];

            // 积分账户
            $uw = EcologyCreadit::where('uid', Service::auth()->getUser()->id)->first();
//            if(!$uw){
//                $uwData = [
//                    'uid' => Service::auth()->getUser()->id,
//                    'created_at' => now()->toDateTimeString(),
//                ];
//                $uw = UserWallet::create($uwData);
//            }

            $result['account'][0]['amount'] = bcmul($uw->amount, 1, 4);
            $result['account'][0]['amount_freeze'] = bcmul($uw->amount_freeze, 1, 4);
            $result['account'][0]['total'] = bcmul($uw->total, 1, 4);
            $result['account'][0]['amount_cny'] = bcmul($uw->creadit_cny, 1, 4);
            $result['account'][0]['amount_freeze_cny'] = bcmul($uw->creadit_freeze_cny, 1, 4);
            $result['account'][0]['cny'] = bcmul($uw->total_cny, 1, 4);

            $result['cur_total'] = bcdiv($uw->total_cny, Account::getRate(), 4);
            $result['cur_total_cny'] = bcmul($uw->total_cny, 1, 4);
            $result['all_total'] = bcdiv($uw->total_cny,Account::getRate(),4);
            $result['all_total_cny'] = bcmul($uw->total_cny, 1, 4);

            $data_other = Service::auth()->getUser()->account()->with('coin')->get()->toArray();

            foreach ($data_other as $k => $v) {
                $result['all_total']     = bcadd($result['all_total'], $v['cny'], 4);
                $result['all_total_cny'] = bcadd($result['all_total_cny'], $v['cny'], 4);
            }
            $result['all_total'] = bcdiv($result['all_total'], Account::getRate(), 4);

            return $this->response($result);

        }


        $data['account'] = Service::auth()->getUser()->account()->whereType($type)->with('coin')->get()->toArray();
        $coin_num        = Coin::count();
        if ($coin_num * 2 > count($data['account'])) {
            $coin_ids = array_unique(Arr::pluck($data['account'], 'coin_id'));
            Coin::all()->each(function (Coin $item) use ($coin_ids) {
                if (!in_array($item->id, $coin_ids)) {
                    $account[] = ['uid' => Service::auth()->getUser()->id, 'coin_id' => $item->id, 'created_at' => Carbon::now(), 'type' => 0];
                    $account[] = ['uid' => Service::auth()->getUser()->id, 'coin_id' => $item->id, 'created_at' => Carbon::now(), 'type' => 1];
                    Account::insert($account);
                }

            });
            $data['account'] = Service::auth()->getUser()->account()->whereType($type)->with('coin')->get()->toArray();
        }
        $data['all_total']     = 0;
        $data['all_total_cny'] = 0;
        $data['cur_total']     = 0;
        $data['cur_total_cny'] = 0;

        foreach ($data['account'] as $k => $v) {
            $data['all_total']     = bcadd($data['all_total'], $v['cny'], 4);
            $data['all_total_cny'] = bcadd($data['all_total_cny'], $v['cny'], 4);
            $data['cur_total']     = bcadd($data['cur_total'], $v['cny'], 4);
            $data['cur_total_cny'] = bcadd($data['cur_total_cny'], $v['cny'], 4);
        }

        $data_other = Service::auth()->getUser()->account()->whereType(!$type)->with('coin')->get()->toArray();

        foreach ($data_other as $k => $v) {
            $data['all_total']     = bcadd($data['all_total'], $v['cny'], 4);
            $data['all_total_cny'] = bcadd($data['all_total_cny'], $v['cny'], 4);
        }

        // 转换成USDT数量
        $data['cur_total'] = bcdiv($data['cur_total'], Account::getRate(), 4);
        $data['all_total'] = bcdiv($data['all_total'], Account::getRate(), 4);

        return $this->response($data);
    }

    public function log($coin_id, Request $request)
    {

        Service::auth()->isLoginOrFail();

        if($coin_id == '1001'){

            $eLogs = EnergyLog::where('wallet_type', 1)->where('uid', Service::auth()->getUser()->id)->latest()->paginate()->toArray();
            foreach ($eLogs['data'] as $k => $v){

                $eLogs['data'][$k] = [
                    'uid' => $v['uid'],
                    'coin_id' => $coin_id,
                    'amount' => $v['num'],
                    'type' => $v['sign'] == '+' ? 1 : 0,
                    'remark' => $v['exp'],
                    'created_at' => strtotime($v['created_at'])
                ];
            }

            $result = [
                'amount' => 0,
                'amount_freeze' => 0,
                'cny' => 0
            ];

            $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
            if($uw){
                $result = [
                    'amount' => $uw->energy_num,
                    'amount_freeze' => $uw->energy_frozen_num,
                    'cny' => $uw->total_cny,
                ];
            }

            return $this->response(array_merge($eLogs, $result));

        }else if($coin_id == '1002'){

            $eLogs = EnergyLog::where('wallet_type', 2)->where('uid', Service::auth()->getUser()->id)->latest()->paginate()->toArray();
            foreach ($eLogs['data'] as $k => $v){

                $eLogs['data'][$k] = [
                    'uid' => $v['uid'],
                    'coin_id' => $coin_id,
                    'amount' => $v['num'],
                    'type' => $v['sign'] == '+' ? 1 : 0,
                    'remark' => $v['exp'],
                    'created_at' => strtotime($v['created_at'])
                ];
            }

            $result = [
                'amount' => 0,
                'amount_freeze' => 0,
                'cny' => 0
            ];

            $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
            if($uw){
                $result = [
                    'amount' => $uw->consumer_num,
                    'amount_freeze' => 0,
                    'cny' => $uw->consumer_cny,
                ];
            }

            return $this->response(array_merge($eLogs, $result));

        }else if($coin_id == '1003'){

            $eLogs = EnergyLog::where('wallet_type', 3)->where('uid', Service::auth()->getUser()->id)->latest()->paginate()->toArray();
            foreach ($eLogs['data'] as $k => $v){

                $eLogs['data'][$k] = [
                    'uid' => $v['uid'],
                    'coin_id' => $coin_id,
                    'amount' => $v['num'],
                    'type' => $v['sign'] == '+' ? 1 : 0,
                    'remark' => $v['exp'],
                    'created_at' => strtotime($v['created_at'])
                ];
            }

            $result = [
                'amount' => 0,
                'amount_freeze' => 0,
                'cny' => 0
            ];

            $uw = UserWallet::where('uid', Service::auth()->getUser()->id)->first();
            if($uw){
                $result = [
                    'amount' => $uw->energy_lock_num,
                    'amount_freeze' => 0,
                    'cny' => $uw->energy_lock_num,
                ];
            }

            return $this->response(array_merge($eLogs, $result));

        }else if($coin_id == '1004'){

            $eLogs = EcologyCreaditLog::where('uid', Service::auth()->getUser()->id)->latest()->paginate()->toArray();
            foreach ($eLogs['data'] as $k => $v){

                $eLogs['data'][$k] = [
                    'uid' => $v['uid'],
                    'coin_id' => $coin_id,
                    'amount' => $v['amount'],
                    'type' => $v['type'] == '1' ? 1 : 0,
                    'remark' => $v['exp'],
                    'created_at' => strtotime($v['created_at'])
                ];
            }

            $result = [
                'amount' => 0,
                'amount_freeze' => 0,
                'cny' => 0
            ];

            $uw = EcologyCreadit::where('uid', Service::auth()->getUser()->id)->first();
            if($uw){
                $result = [
                    'amount' => $uw->amount,
                    'amount_freeze' => $uw->amount_freeze,
                    'cny' => $uw->total,
                ];
            }

            return $this->response(array_merge($eLogs, $result));

        }


        $coinType = $request->get('coin_type', 0);

        $account = AccountLog::with('coin')->whereUid(Service::auth()->getUser()->id)->whereCoinId($coin_id)->where('coin_type', $coinType)->orderBy('id', 'desc')->paginate($request->get('per_page'))->toArray();

        $result = [
            'amount' => 0,
            'amount_freeze' => 0,
            'cny' => 0
        ];

        $a = Account::where('coin_id', $coin_id)->where('type', $coinType)->where('uid', Service::auth()->getUser()->id)->first();
        if($a){
            $result = [
                'amount' => $a->amount,
                'amount_freeze' => $a->amount_freeze,
                'cny' => $a->cny,
            ];
        }

        return $this->response(array_merge($account, $result));

        /*Service::auth()->isLoginOrFail();
        $scene   = $request->get('scene');
        $scene   = explode(',', $scene);

        $scene[] = 16;
        $scene[] = 17;

        $account = AccountLog::with('coin')->whereUid(Service::auth()->getUser()->id)->whereCoinId($coin_id)->whereIn('scene', $scene)->orderBy('id', 'desc')->paginate($request->get('per_page'))->toArray();

        $result = [
            'amount' => 0,
            'amount_freeze' => 0,
            'cny' => 0
        ];

        $a = Account::where('coin_id', $coin_id)->where('uid', Service::auth()->getUser()->id)->first();
        if($a){
            $result = [
                'amount' => $a->amount,
                'amount_freeze' => $a->amount_freeze,
                'cny' => $a->cny,
            ];
        }

        return $this->response(array_merge($account, $result));*/
    }

    public function trans(Request $request)
    {
        $check_time = Session::get('times');
        \Log::info('上次'.$check_time);
        if(!empty($check_time)){
            $now_time = time();
            if(($now_time-$check_time)<=5){
                // respon(0,'操作频繁，稍后再试','Frequent operation and try again later');
                return $this->responseError("请求频繁1");
            }
        }
        Session::put('times',time());
        Session::save();
        \Log::info('上次'.Session::get('times'));
//        dd($check_time);
        $times = time();
        $last_log = AccountLog::where('uid',1)
                    ->where('scene',4)
                    ->where('type',1)
                    ->where('coin_type',1)
                    ->orderBy('id','desc')
                    ->first();
//                    ->value('created_at');
        \Log::info('数据库时间'.$last_log['created_at'],['当前time'=>$times,'相减'=>$times - $last_log['created_at']]);
//        dd(strtotime($last_log['created_at']));

        if($times - $last_log['created_at'] <= 5){
            return $this->responseError("请求频繁");
        }


        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'action'  => 'required|integer|between:0,1',
            'amount'  => 'required|numeric|min:1',
            'coin_id' => 'required|integer'
        ]);

        $coin_id = $request->get('coin_id');
        $action  = $request->get('action');
        $amount  = $request->get('amount');
        $account = Service::auth()->account($coin_id, $action);

        $this->validate($request->all(), [
            'amount' => 'numeric|max:' . $account->amount,
        ]);

        Service::auth()->account($coin_id, $action)->decrement('amount', $amount);
        Service::auth()->account($coin_id, intval(!$action))->increment('amount', $amount);

        $scene = $action ? AccountLog::SCENE_TO_COIN_COIN : AccountLog::SCENE_TO_LEGAL_COIN;
//        Service::account()->createLog(Service::auth()->getUser()->id, $coin_id, $amount, $scene);

        AccountLog::addLog(Service::auth()->getUser()->id, $coin_id, $amount, $scene, $action, intval($action), AccountLog::getRemark($scene));
        AccountLog::addLog(Service::auth()->getUser()->id, $coin_id, $amount, $scene, intval(!$action), intval(!$action), AccountLog::getRemark($scene));

        return $this->response(Account::whereUid(Service::auth()->getUser()->id)->whereCoinId($coin_id)->get()->toArray());
    }


}

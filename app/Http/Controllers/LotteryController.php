<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/12
 * Time: 11:13
 */

namespace App\Http\Controllers;


use App\Libs\StringLib;
use App\Models\AccessToken;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\LotteryGoods;
use App\Models\LotteryLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function Psy\debug;

class LotteryController extends Controller
{

    // 抽奖首页
    public function index(Request $request)
    {

        $access_token = AccessToken::whereToken($request->get('x-token', 'xx'))->first();
        if(!$access_token){
            return '服务器错误';
        }

        // 获取用户今日抽奖次数
        $data['lottery_count'] = LotteryLog::where('uid', $access_token->uid)
                            ->where('created_at', '>', now()->toDateString() . ' 00:00:00')
                            ->count();

        // 获取抽奖商品信息
        $data['goods'] = LotteryGoods::get()->toArray();

        // 获取用户余额信息
        $data['wallet_num'] = 0;

        $account = Account::whereUid($access_token->uid)->whereCoinId(2)->whereType(Account::TYPE_LC)->first();
        if($account){
            $data['wallet_num'] = bcmul($account->amount, 1, 4);
        }

        // 获取每次抽奖需要的数量
        $data['one_num'] = config('lottery.lottery_one_num', 100);

        // 获取中奖记录
        $xcArr = [];
        foreach ($data['goods'] as $k => $v){
            if($v['is_xc'] == 1){
                $xcArr[] = $v['id'];
            }
        }
        $data['logs'] = LotteryLog::with(['user', 'goods'])
                        ->whereIn('goods_id', $xcArr)
                        ->latest('id')
                        ->limit(10)
                        ->get()
                        ->toArray();

        $data['x_token'] = $access_token->token;

        return view('lottery.index', $data);

    }

    // 抽奖提交
    public function submit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'count' => 'required|integer|min:1',
            'x-token' => 'required',
            'paypass' => 'required',
        ], [
            'count.required' => '抽奖次数不能为空',
            'count.integer' => '抽奖次数格式不正确',
            'count.min' => '抽奖次数最小为1',
            'x-token.required' => '令牌信息不能为空',
            'paypass.required' => '交易密码不能为空',
        ]);

        if ($validator->fails()) {
            return returnJson(0, $validator->errors()->first());
        }

        $access_token = AccessToken::whereToken($request->get('x-token', 'xx'))->first();
        if(!$access_token){
            return returnJson(0, '数据有误');
        }

        // 验证支付密码是否正确
        $newPass = StringLib::password($request->get('paypass'));
        $user = User::find($access_token->uid);
        if(!$user || $user->transaction_password != $newPass){
            return returnJson(0, '交易密码错误');
        }

        $oneNum = config('lottery.lottery_one_num', 100);
        $totalNum = bcmul($oneNum, $request->get('count'), 8);

        // 验证用户余额是否充足
        $account = Account::whereUid($access_token->uid)->whereCoinId(2)->whereType(Account::TYPE_LC)->first();
        if(!$account || $account->amount < $totalNum){
            return returnJson(0, '余额不足');
        }

        \DB::beginTransaction();
        try {

            // 先把钱扣了先
            Account::reduceAmount($access_token->uid, 2, $totalNum);

            // 钱包余额日志增加
            AccountLog::addLog($access_token->uid, 2, $totalNum, 26, 0, Account::TYPE_LC, '抽奖');

            // 进行抽奖,获取中奖信息
            $result = $this->toLottery($access_token->uid, $request->get('count'), $oneNum);
            if(empty($result)){
                new \Exception('操作异常');
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('抽奖出现异常');

            return returnJson(0, '操作异常');

        }

        return returnJson(1, '操作成功', $result);

    }

    // 进行抽奖
    private function toLottery($uid, $count, $oneNum)
    {

        // 号池数组
        $noArr = [];

        // 临时号数
        $lsNo = 0;

        // 中奖数组
        $result = [];

        // 记录数组
        $llData = [];

        // 获取商品信息
        $goods = LotteryGoods::get();
        foreach ($goods as $v){

            $goodsNo = bcmul($v->zj_bl, 10000000);
            $min = $lsNo;
            $lsNo += $goodsNo;
            $max = $lsNo;

            $noArr[] = [
                'goods_id' => $v->id,
                'goods_name' => $v->name,
                'goods_img' => $v->img,
                'min' => $min,
                'max' => $max
            ];

        }

        // 判断抽奖次数
        for ($i = 0; $i < $count; $i++){

            // 随机生成中奖号码
            $lotteryNo = rand(1, 10000000);

            foreach ($noArr as $v){

                if($lotteryNo > $v['min'] && $lotteryNo <= $v['max']){
                    $result[] = [
                        'goods_id' => $v['goods_id'],
                        'goods_name' => $v['goods_name'],
                        'goods_img' => $v['goods_img'],
                        'ds' => $this->getDushu($v['goods_id']),
                    ];

                    // 记录到记录数组
                    $llData[] = [
                        'uid' => $uid,
                        'goods_id' => $v['goods_id'],
                        'num' => $oneNum,
                        'created_at' => now()->toDateTimeString(),
                    ];

                }

            }

        }

        // 记录到日志里
        LotteryLog::insert($llData);

        return $result;

    }

    // 获取调整的度数
    private function getDushu($id)
    {
        switch ($id){

            case 1:
                $result = 0;
                break;

            case 2:
                $result = 45;
                break;

            case 3:
                $result = 90;
                break;

            case 4:
                $result = 135;
                break;

            case 5:
                $result = 180;
                break;

            case 6:
                $result = 225;
                break;

            case 7:
                $result = 270;
                break;

            case 8:
                $result = 315;
                break;

            default:
                $result = 360;
                break;

        }

        return $result;
    }

    // 抽奖记录
    public function log(Request $request){

        $access_token = AccessToken::whereToken($request->get('x-token', 'xx'))->first();
        if(!$access_token){
            return '服务器错误';
        }

        $page = $request->get('page', 1);

        if($page != 'all'){

            // 获取用户抽奖记录
            $log = LotteryLog::with('goods')
                ->where('uid', $access_token->uid)
                ->latest('id')
                ->offset(($page - 1) * 50)
                ->limit(50)
                ->get()
                ->toArray();

        }else{

            // 获取用户抽奖记录
            $log = LotteryLog::with('goods')
                ->where('uid', $access_token->uid)
                ->latest('id')
                ->get()
                ->toArray();

        }


        $result = [];
        foreach ($log as $k => $v){
            $result[] = [
                'created_at' => date( 'Y-m-d H:s', strtotime($v['created_at'])),
                'goods_name' => $v['goods']['name'],
            ];
        }

        $data = [
            'logs' => $result,
            'x_token' => $access_token->token,
            'page' => $page,
        ];

        return view('lottery.log', $data);
    }

    // 抽奖规则
    public function info(Request $request)
    {

        $access_token = AccessToken::whereToken($request->get('x-token', 'xx'))->first();
        if(!$access_token){
            return '服务器错误';
        }

        // 获取每次抽奖需要的数量
        $oneNum = config('lottery.lottery_one_num', 100);

        $data = [
            'x_token' => $access_token->token,
            'one_num' => $oneNum,
        ];

        return view('lottery.info', $data);
    }

}
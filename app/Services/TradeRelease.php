<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/6
 * Time: 15:37
 */

namespace App\Services;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\KuangjiLinghuo;
use App\Models\ReleaseOrder;
use App\Models\UserInfo;
use App\Models\UserWalletLog;

class TradeRelease
{

    private $ro = false;
    private $ui = '';
    private $r_bool = true;

    // 释放
    /*public function release($uid, $num)
    {

        // 判断当日释放次数是否超限
        $userInfo = UserInfo::where('uid', $uid)->first();
        if(!$userInfo){
            \Log::info('用户数据有误，不进行是释放', ['uid' => $uid]);
            return false;
        }

        if(!config('kuangji.trade_release_status', 0)){
            \Log::info('交易释放总开关关闭，不进行释放', ['uid' => $uid]);
            return false;
        }

        // 判断用户是否购买矿机
        if($userInfo->release_status != 0){
            \Log::info('用户不能进行交易释放', ['uid' => $uid]);
            return false;
        }

        $this->ui = $userInfo;

        if(now()->toDateString() == substr($userInfo->release_time, 0, 10)){
            $rCount = config('release.today_release_count', 0);
            if($rCount <= $userInfo->today_count){
                \Log::info('今天释放次数超限，不进行是释放', ['uid' => $uid, 'r_count' => $rCount]);
                return false;
            }
        }

        // 先增加释放时间和次数
        $uiNewData = [];
        if(now()->toDateString() == substr($userInfo->release_time, 0, 10)){
            $uiNewData['today_count'] = $userInfo->today_count + 1;
        }else{
            $uiNewData['today_count'] = 1;
        }
        $uiNewData['release_time'] = now()->toDateTimeString();
        UserInfo::where('uid', $uid)->update($uiNewData);

        \Log::info('=====   开始交易释放   =====');

        // 获取本次释放的有效数量
        $newNum = $this->getReleaseMaxNum($uid, $num);
        \Log::info('本次释放的数量' . $newNum);

        if ($newNum <= 0) {
            \Log::info('可释放数量小于或等于0，放弃本次释放');
            return false;
        }

        if ($this->ro) {
            \Log::info('查到有当天释放未完全的，优先释放未完全释放的');
            $newNum = $this->toRelease($newNum, $this->ro->id, $uid);
        }

        if ($newNum <= 0) {
            \Log::info('-----   释放完成   -----');
            return true;
        }

        $rOrder = ReleaseOrder::unFinish()->where('uid', $uid)->where('release_time', '<', now()->toDateString() . ' 00:00:00')->oldest('type')->get();
        if ($rOrder->isEmpty()) {
            return false;
        }

        foreach ($rOrder as $k => $v) {
            if ($newNum > 0) {
                $newNum = $this->toRelease($newNum, $v->id, $uid);
            } else {
                break;
            }

        }

        \Log::info('=====   交易释放完成   =====');

        return true;

    }

    // 获取本次释放的数量
    private function getReleaseMaxNum($uid, $num)
    {

        // 获取本次交易的可释放值
        $tradeReleaseNum = $num * config('release.trade_bl');

        // 获取用户今天没有释放的订单数量
        $todayReleaseMaxNum = ReleaseOrder::unFinish()->where(['uid'=> $uid, 'type' => 0])->where('release_time', '<', now()->toDateString() . ' 00:00:00')->sum('today_max');

        // 获取用户今天释放了一半的订单
        $ro = ReleaseOrder::unFinish()->where(['uid'=> $uid, 'type' => 0])->whereDate('release_time', now()->toDateString())->whereRaw('today_max > today_num')->first();
        if ($ro) {
            $todayReleaseMaxNum = bcadd($todayReleaseMaxNum, $ro->today_max - $ro->today_num, 8);
            $this->ro = $ro;
        }

        // 当推荐的没有的情况下再进行推荐的释放
        $rBool = ReleaseOrder::unFinish()->where(['uid'=> $uid, 'type' => 0])->exists();
        $this->r_bool = $rBool;
        if(!$rBool && $this->ui){

            \Log::info('用户开始释放推荐奖励的矿池');

            // 获取用户今天释放了一半的订单
            $ro = ReleaseOrder::unFinish()->where(['uid'=> $uid, 'type' => 1])->whereDate('release_time', now()->toDateString())->whereRaw('today_max > today_num')->first();
            if ($ro) {
                $this->ro = $ro;
            }

            $maxNum = 0;

            // 用户为普通会员
            if($this->ui->level == 1){
                $maxNum = 20;
            }

            // 用户为高级会员
            if($this->ui->level == 2){
                $maxNum = 100;
            }

            return $maxNum;

        }

        return $tradeReleaseNum > $todayReleaseMaxNum ? $todayReleaseMaxNum : $tradeReleaseNum;

    }

    // 释放
    private function toRelease($num, $id, $uid)
    {

        // 查询释放订单信息
        $res = $this->getOneOrderReleaseNum($id, $num);

        if ($res['num'] <= 0) {
            return $num;
        }

        // 判断释放的数量和订单实际能释放的数量对比
        $realNum = $res['num'] <= $num ? $res['num'] : $num;


        // 更新释放订单信息
        $rOrder = ReleaseOrder::unFinish()->where('id', $id)->first();
        if ($res['status']) {
            $rOrder->status = 1;
        }

        if ($res['today']) {
            $rOrder->today_num = $rOrder->today_num + $realNum;
        } else {
            $rOrder->today_num = $realNum;
        }
        $rOrder->release_num = $rOrder->release_num + $realNum;
        $rOrder->release_time = now()->toDateTimeString();

        $rOrder->save();

        // 获取那个IUIC的币种ID
        $coin = Coin::getCoinByName('IUIC');

        // 用户余额表更新
        Account::addAmount($uid, $coin->id, $realNum, Account::TYPE_LC);

        // 用户余额日志表更新
        Service::account()->createLog($uid, $coin->id, $realNum, AccountLog::SCENE_Trade_RELEASE);

        // 用户附属表变化
        $userInfo = UserInfo::where('uid', $uid)->first();

        $uiData = [
            'release_total' => bcadd($userInfo->release_total, $realNum, 8),
        ];

        UserInfo::where('uid', $uid)->update($uiData);

        // 用户矿池日志表更新
        UserWalletLog::addLog($uid, null, null, '交易释放', '-', $realNum, 2, 1);

        \Log::info('订单ID为' . $id . '的ID释放' . $realNum);

        return $num - $realNum;

    }

    // 查询当个订单的可释放信息
    private function getOneOrderReleaseNum($id, $realNum)
    {

        // 查询需要释放的订单
        $rOrder = ReleaseOrder::unFinish()->where('id', $id)->first();
        if (!$rOrder) {
            return [
                'num' => 0,
                'status' => false,
                'today' => false,
            ];
        }

        // 判断用户今日释放是否释放
        if (substr($rOrder->release_time, 0, 10) == now()->toDateString()) {

            $num = bcsub($rOrder->today_max, $rOrder->today_num, 8);

            $num = $num > $realNum ? $realNum : $num;

            if (($rOrder->release_num + $num) > $rOrder->total_num) {
                return [
                    'num' => bcsub($rOrder->total_num, $rOrder->release_num, 8),
                    'status' => true,
                    'today' => true,
                ];
            }

            return [
                'num' => $num,
                'status' => false,
                'today' => true,
            ];

        }

        $num = $rOrder->today_max > $realNum ? $realNum : $rOrder->today_max;

        if (($rOrder->release_num + $num) > $rOrder->total_num) {
            return [
                'num' => bcsub($rOrder->total_num, $rOrder->release_num, 8),
                'status' => true,
                'today' => false,
            ];
        }

        return [
            'num' => $num,
            'status' => false,
            'today' => false,
        ];

    }

    */

    // 新的释放方式
    public function release($uid, $num)
    {

        \Log::info('=====   开始交易释放   =====');

        // 判断当日释放次数是否超限
        $userInfo = UserInfo::where('uid', $uid)->first();
        if(!$userInfo){
            \Log::info('用户数据有误，不进行是释放', ['uid' => $uid]);
            return false;
        }

        if(!config('kuangji.trade_release_status', 0)){
            \Log::info('交易释放总开关关闭，不进行释放', ['uid' => $uid]);
            return false;
        }

        // 先判断当日可释放多少次
        $rCount = config('release.today_release_count', 0);
        if($rCount <= 0){
            \Log::info('当日设置不能释放', ['uid' => $uid, 'r_count' => $rCount]);
            return false;
        }

        if(now()->toDateString() == substr($userInfo->release_time, 0, 10)){
            if($rCount <= $userInfo->today_count){
                \Log::info('今天释放次数超限，不进行是释放', ['uid' => $uid, 'r_count' => $rCount]);
                return false;
            }
        }

        // 获取本次释放的有效数量
        $newNum = bcmul($num, config('release.trade_bl'), 8);

        // 在矿池数量未释放完成的情况下, 释放矿池的IUIC, 未完成的情况下释放灵活矿机的质押IUIC
        if($userInfo->release_total < $userInfo->buy_total){

            if(bcadd($userInfo->release_total, $newNum, 8) > $userInfo->buy_total){
                $newNum = bcsub($userInfo->buy_total, $userInfo->release_total, 8);
            }

            // 获取一天最多释放数量
            $todayReleaseNum = config('release.today_release_num', 10000);
            if(bcadd($userInfo->today_release, $newNum, 8) > $todayReleaseNum){
                $newNum = bcsub($todayReleaseNum, $userInfo->today_release, 8);
            }

            if ($newNum <= 0) {
                \Log::info('可释放数量小于或等于0，放弃本次释放');
                return false;
            }

            \Log::info('本次释放的数量' . $newNum);

            // 先增加释放时间和次数
            $uiNewData = [];
            if(now()->toDateString() == substr($userInfo->release_time, 0, 10)){
                $uiNewData['today_count'] = $userInfo->today_count + 1;
            }else{
                $uiNewData['today_count'] = 1;
            }
            $uiNewData['release_time'] = now()->toDateTimeString();
            UserInfo::where('uid', $uid)->update($uiNewData);

            // 获取那个IUIC的币种ID
            $coin = Coin::getCoinByName('IUIC');

            // 用户余额表更新
            Account::addAmount($uid, $coin->id, $newNum, Account::TYPE_LC);

            // 用户余额日志表更新
            Service::account()->createLog($uid, $coin->id, $newNum, AccountLog::SCENE_Trade_RELEASE);

            UserInfo::where('uid', $uid)->increment('release_total', $newNum);

            UserInfo::where('uid', $uid)->increment('today_release', $newNum);

            // 用户矿池日志表更新
            UserWalletLog::addLog($uid, null, null, '交易释放', '-', $newNum, 2, 1);

        }else{

            \Log::info('交易释放，没有矿池的情况下进了灵活矿机质押页面');

            // 获取灵活矿机信息
            $kjLinghuo = KuangjiLinghuo::where('uid', $uid)->first();
            if(!$kjLinghuo){
                \Log::info('没有矿池的情况下也没有灵活矿机，结束');
                return false;
            }

            // 判断还有没有质押的IUIC
            if($kjLinghuo->num <= 0){
                \Log::info('没有矿池的情况下已经没有质押的IUIC了，结束');
                return false;
            }

            // 判断本次释放是否超过质押数量
            if($newNum > $kjLinghuo->num){
                $newNum = $kjLinghuo->num;
            }

            if ($newNum <= 0) {
                \Log::info('可释放数量小于或等于0，放弃本次释放');
                return false;
            }

            // 先增加释放时间和次数
            $uiNewData = [];
            if(now()->toDateString() == substr($userInfo->release_time, 0, 10)){
                $uiNewData['today_count'] = $userInfo->today_count + 1;
            }else{
                $uiNewData['today_count'] = 1;
            }
            $uiNewData['release_time'] = now()->toDateTimeString();
            UserInfo::where('uid', $uid)->update($uiNewData);

            // 获取那个IUIC的币种ID
            $coin = Coin::getCoinByName('IUIC');

            // 用户余额表更新
            Account::addAmount($uid, $coin->id, $newNum, Account::TYPE_LC);

            // 用户余额日志表更新
            Service::account()->createLog($uid, $coin->id, $newNum, AccountLog::SCENE_Trade_RELEASE);

            // 灵活矿机质押数量减少
            KuangjiLinghuo::where('uid', $uid)->decrement('num', $newNum);

            // 用户矿池日志表更新
            UserWalletLog::addLog($uid, null, null, '交易灵活矿机释放', '-', $newNum, 2, 1);

        }

        \Log::info('=====   交易释放完成   =====');

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/19
 * Time: 14:58
 */

namespace App\Services;


use App\Models\EnergyAppointUser;
use App\Models\EnergyLog;
use App\Models\EnergyOrder;
use App\Models\EnergyReleaseLog;
use App\Models\UserWallet;

class EnergyService
{

    // 能量订单进行释放
    public static function orderRelease($uid, $num, $orderId, $exp, $dyUid = 0)
    {

        // 获取可用能量释放比例
        $kyReleaseBl = config('energy.energy_ky_release_bl', 0.8);

        // 获取消费积分释放比例
        $xfReleaseBl = config('energy.energy_xfjf_release_bl', 0.1);

        // 获取公益金积分释放比例
        $gyjjfReleaseBl = config('energy.energy_gyjjf_release_bl', 0.1);

        // 计算可用能量
        $kyNum = bcmul($num, $kyReleaseBl, 8);

        // 计算消费者积分
        $xfzNum = bcmul($num, $xfReleaseBl, 8);

        // 计算公益金
        $gyjNum = bcmul($num, $gyjjfReleaseBl, 8);

        // 记录日志
        $erlData = [
            'uid' => $uid,
            'num' => $num,
            'ky_num' => $kyNum,
            'xfjf_num' => $xfzNum,
            'gyj_num' => $gyjNum,
            'created_at' => now()->toDateTimeString(),
        ];
        EnergyReleaseLog::create($erlData);

        // 用户可用余额增加
        UserWallet::addEnergyNum($uid, $kyNum);
        EnergyLog::addLog($uid, 1, 'energy_order', $orderId, $exp, '+', $kyNum, 2, $dyUid);

        // 用户冻结余额减少
        UserWallet::reduceEnergyFrozenNum($uid, $num);

        // 用户消费者积分增加
        UserWallet::addConsumerNum($uid, $xfzNum);
        EnergyLog::addLog($uid, 2, 'energy_order', $orderId, $exp, '+', $xfzNum, 2, $dyUid);

        return true;

    }

    // 能量订单进行加速释放
    public static function orderSpeedRelease($uid, $num, $exp, $dyUid = 0)
    {

        if ($num <= 0) {
            \Log::info('进来的数据有误,跳过');
            return false;
        }

        // 获取用户的报单信息
        $eo = EnergyOrder::where('uid', $uid)->where('status', 0)->get();
        if ($eo->isEmpty()) {
            \Log::info('用户没有合适的能量订单,跳过');
            return false;
        }

        // 获取指定的用户
        $uidArr = EnergyAppointUser::pluck('uid')->toArray();

        foreach ($eo as $k => $v) {

            if ($num <= 0) {
                break;
            }

            if (bcadd($num, $v->release_num, 8) >= $v->add_num) {

                // 重新计算可以得到的数量
                $oneNum = bcsub($v->add_num, $v->release_num, 8);
                if ($oneNum <= 0) {

                    // 订单异常，更正异常
                    EnergyOrder::where('id', $v->id)->update(['status' => 1]);

                    \Log::info('ID为' . $v->id . '的订单异常,订单更正');

                    continue;

                }

                // 进行释放
                if (in_array($v->uid, $uidArr) && $v->type == 2) {
                    EnergyService::orderLockRelease($v->uid, $oneNum, $v->id, $exp, $dyUid);
                } else {
                    EnergyService::orderRelease($v->uid, $oneNum, $v->id, $exp, $dyUid);
                }

                // 订单状态更改
                $updData = [
                    'release_num' => $v->add_num,
                    'status' => 1,
                ];
                EnergyOrder::where('id', $v->id)->update($updData);

                $num = bcmul($num, $oneNum, 8);

            } else {

                // 进行释放
                if (in_array($v->uid, $uidArr) && $v->type == 2) {
                    EnergyService::orderLockRelease($v->uid, $num, $v->id, $exp, $dyUid);
                } else {
                    EnergyService::orderRelease($v->uid, $num, $v->id, $exp, $dyUid);
                }

                // 订单释放量增加
                EnergyOrder::where('id', $v->id)->increment('release_num', $num);

                break;

            }

        }

    }

    // 能量订单进行锁仓释放
    public static function orderLockRelease($uid, $num, $orderId, $exp, $dyUid = 0)
    {

        // 获取可用能量释放比例
        $kyReleaseBl = config('energy.energy_ky_release_bl', 0.8);

        // 获取消费积分释放比例
        $xfReleaseBl = config('energy.energy_xfjf_release_bl', 0.1);

        // 获取公益金积分释放比例
        $gyjjfReleaseBl = config('energy.energy_gyjjf_release_bl', 0.1);

        // 计算可用能量
        $kyNum = bcmul($num, $kyReleaseBl, 8);

        // 计算消费者积分
        $xfzNum = bcmul($num, $xfReleaseBl, 8);

        // 计算公益金
        $gyjNum = bcmul($num, $gyjjfReleaseBl, 8);

        // 记录日志
        $erlData = [
            'uid' => $uid,
            'num' => $num,
            'ky_num' => $kyNum,
            'xfjf_num' => $xfzNum,
            'gyj_num' => $gyjNum,
            'type' => 2,
            'created_at' => now()->toDateTimeString(),
        ];
        EnergyReleaseLog::create($erlData);

        // 用户可用余额增加
        UserWallet::addEnergyLockNum($uid, $kyNum);
        EnergyLog::addLog($uid, 3, 'energy_order', $orderId, $exp, '+', $kyNum, 2, $dyUid);

        // 用户冻结余额减少
        UserWallet::reduceEnergyFrozenNum($uid, $num);

        // 用户消费者积分增加
        UserWallet::addConsumerNum($uid, $xfzNum);
        EnergyLog::addLog($uid, 2, 'energy_order', $orderId, $exp, '+', $xfzNum, 2, $dyUid);

        return true;

    }

}
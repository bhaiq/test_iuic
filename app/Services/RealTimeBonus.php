<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 18:02
 */

namespace App\Services;


use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\ExtraBonus;
use App\Models\UserBonus;
use App\Models\UserPartner;

class RealTimeBonus
{

    // 实时分红
    public function handle($num)
    {

        \Log::info('=====   开始执行分红   =====');

        // 第一种分红,节点奖分红
//        $this->bonus($num);

        // 第二种分红，管理奖分红
//        $this->adminBonus($num);

        // 后面增加的额外分红
//        $this->extraBonus($num);

        // 合伙人收益分红
        $this->partnerBonus($num);

        // 小节点奖分红
//        $this->minBonus($num);

        // 大节点奖分红
//        $this->bigBonus($num);

        // 超级节点奖分红
//        $this->superBonus($num);

        \Log::info('=====   执行分红结束   =====');

    }

    // 第一种分红,分红奖分红
    private function bonus($num)
    {

        // 获取可以分红的用户数量
        $bonusCount = UserBonus::where('type', '=', 1)->where('node_type', 0)->get(['uid']);
        if ($bonusCount->isEmpty()) {
            \Log::info('没有用户达到节点奖分红条件,放弃本次节点奖分红');
            return false;
        }

        // 获取分红的人数
        $userCount = $bonusCount->count();

        // 获取每一份分红多少钱
        $oneNum = bcmul(config('shop.bonus_bl'), bcdiv($num, $userCount, 8), 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次分红奖分红');
            return false;
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');

        foreach ($bonusCount as $v) {

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志表更新
            Service::account()->createLog($v->uid, $coin->id, $oneNum, AccountLog::SCENE_SCENE_BONUS);

        }

        return true;

    }

    // 第二种分红，管理奖分红
    private function adminBonus($num)
    {

        // 获取可以分红的用户数量
        $bonusCount = UserBonus::where('type', '=', 2)->get(['uid']);
        if ($bonusCount->isEmpty()) {
            \Log::info('没有用户达到分红条件,放弃本次管理奖分红');
            return false;
        }

        // 获取分红的人数
        $userCount = $bonusCount->count();

        // 获取每一份分红多少钱
        $oneNum = bcmul(config('shop.admin_bonus_bl'), bcdiv($num, $userCount, 8), 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次管理奖分红');
            return false;
        }

        foreach ($bonusCount as $v) {

            // 获取那个USDT的币种ID
            $coin = Coin::getCoinByName('USDT');

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志表更新
            Service::account()->createLog($v->uid, $coin->id, $oneNum, AccountLog::SCENE_SCENE_ADMIN_BONUS);

        }

        return true;

    }

    // 后面增加额外
    private function extraBonus($num)
    {

        // 判断有没有需要额外释放的奖励
        $eb = ExtraBonus::get();
        if ($eb->isEmpty()) {
            \Log::info('没有额外的奖励');
            return false;
        }

        foreach ($eb->toArray() as $v) {

            // 判断数据是否齐全
            if (empty($v['name'] || empty($v['tip']) || empty($v['users']))) {
                \Log::info('本次数据不齐全', $v);
                continue;
            }

            // 判断用户信息是否是一个数组
            if (!is_array($v['users'])) {
                \Log::info('用户信息有误,不是一个数组');
                continue;
            }

            // 获取总共能分的奖励
            $totalNum = bcmul($num, $v['tip'], 8);

            // 获取能分的用户数量
            $userCount = count($v['users']);

            // 平均每个人能分的数量
            $oneNum = bcdiv($totalNum, $userCount, 8);

            // 获取那个USDT的币种ID
            $coin = Coin::getCoinByName('USDT');

            // 给在座的每个人增加余额
            foreach ($v['users'] as $val) {

                if (!empty($val)) {
                    // 用户余额表新增
                    Account::addAmount($val, $coin->id, $oneNum, Account::TYPE_LC);

                    // 用户余额日志表更新
                    AccountLog::addLog($val, $coin->id, $oneNum, 18, 1, Account::TYPE_LC, $v['name']);
                }


            }

        }

    }

    // 合伙人收益分红
    private function partnerBonus($num)
    {

        // 获取需要分红的用户
        $up = UserPartner::where('status', 1)->get();
        if($up->isEmpty()){
            \Log::info('没有合伙人需要分红');
            return false;
        }

        // 获取需要分红的用户数量
//        $userCount = config('user_partner.count', 50);
        $userCount = UserPartner::where('status', 1)->sum('count');

        // 获取本次分红的比例
        $tip = config('user_partner.tip_partner', 0.1);

        // 获取本次分红的总数
        $totalNum = bcmul($tip, $num, 8);

        // 获取本次分红一份的分红数量
        $oneNum = bcdiv($totalNum, $userCount, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次分红奖分红');
            return false;
        }

        \Log::info('合伙人分红的数据', ['num' => $num, 'count' => $userCount, 'tip' => $tip, 'one' => $oneNum]);

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');

        foreach ($up as $v){

            $newNum = bcmul($oneNum, $v->count, 8);

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $newNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, $coin->id, $newNum, 18, 1, Account::TYPE_LC, '合伙人分红');

        }

        return true;

    }

    // 小节点奖分红
    private function minBonus($num)
    {

        // 获取可以分红的用户数量
        $bonusCount = UserBonus::where('type', '=', 1)->where('node_type', 1)->get(['uid']);
        if ($bonusCount->isEmpty()) {
            \Log::info('没有用户达到小节点奖分红条件,放弃本次小节点奖分红');
            return false;
        }

        // 获取分红的人数
        $userCount = $bonusCount->count();

        // 获取每一份分红多少钱
        $oneNum = bcmul(config('node.small_node', 0.05), bcdiv($num, $userCount, 8), 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次小节点奖分红');
            return false;
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');

        foreach ($bonusCount as $v) {

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, $coin->id, $oneNum, 14, 1, Account::TYPE_LC, '小节点奖分红');

        }

        return true;

    }

    // 大节点奖分红
    private function bigBonus($num)
    {

        // 获取可以分红的用户数量
        $bonusCount = UserBonus::where('type', '=', 1)->where('node_type', 2)->get(['uid']);
        if ($bonusCount->isEmpty()) {
            \Log::info('没有用户达到大节点奖分红条件,放弃本次大节点奖分红');
            return false;
        }

        // 获取分红的人数
        $userCount = $bonusCount->count();

        // 获取每一份分红多少钱
        $oneNum = bcmul(config('node.big_node', 0.04), bcdiv($num, $userCount, 8), 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次大节点奖分红');
            return false;
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');

        foreach ($bonusCount as $v) {

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, $coin->id, $oneNum, 14, 1, Account::TYPE_LC, '大节点奖分红');

        }

        return true;

    }

    // 超级节点奖分红
    private function superBonus($num)
    {

        // 获取可以分红的用户数量
        $bonusCount = UserBonus::where('type', '=', 1)->where('node_type', 3)->get(['uid']);
        if ($bonusCount->isEmpty()) {
            \Log::info('没有用户达到超级节点奖分红条件,放弃本次超级节点奖分红');
            return false;
        }

        // 获取分红的人数
        $userCount = $bonusCount->count();

        // 获取每一份分红多少钱
        $oneNum = bcmul(config('node.super_node', 0.03), bcdiv($num, $userCount, 8), 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次超级节点奖分红');
            return false;
        }

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName('USDT');

        foreach ($bonusCount as $v) {

            // 用户余额表更新
            Account::addAmount($v->uid, $coin->id, $oneNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, $coin->id, $oneNum, 14, 1, Account::TYPE_LC, '超级节点奖分红');

        }

        return true;

    }

}
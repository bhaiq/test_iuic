<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\ExtraBonus;
use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserInfo;
use App\Models\UserPartner;
use App\Services\LevelService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateAdminBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;
    private $num;
    private $xxUid = [
        1, 34, 125, 126, 105
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $num)
    {
        $this->uid = $uid;
        $this->num = $num;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        \Log::info('===== 用户递归更新用户节点奖 =====');

        $this->updateNode($this->uid);

        \Log::info('===== 用户递归更新用户节点奖 =====');

        \Log::info('===== 用户递归更新用户管理奖 =====');

        $this->updateAdmin($this->uid);

        \Log::info('===== 用户递归更新用户管理奖 =====');

        \Log::info('===== 直推分享奖 =====');

        $this->toShareReward($this->uid, $this->num);

        \Log::info('===== 直推分享奖 =====');

        \Log::info('===== 直推合伙人奖励 =====');

        $this->toPartnerReward($this->num);

        \Log::info('===== 直推合伙人奖励 =====');

        \Log::info('===== 直推额外奖励 =====');

        $this->toExtraReward($this->num);

        \Log::info('===== 直推额外奖励 =====');

    }

    // 更新用户节点奖信息
    private function updateNode($uid, $type = 0)
    {

        // 获取用户信息
        $user = User::with('user_info')->find($uid);
        if (!$user) {
            \Log::info('用户数据不存在');
            return false;
        }

        // 判断用户是否属于非升级的用户
        if (in_array($user->id, $this->xxUid)) {
            \Log::info('用户属于非升级用户，跳过', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        if (!$user->user_info) {
            \Log::info('用户未报单', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        $ub = '';

        // 判断用户之前是否有节点奖
        if (isset($user->user_info->is_bonus) && $user->user_info->is_bonus == 1) {

            // 获取用户节点奖信息
            $ub = UserBonus::where(['uid' => $uid, 'type' => 1])->first();
            if ($ub) {
                $type = bcadd($ub->node_type, 1);
            }

        }

        $lsRes = LevelService::checkNode($uid, $type);
        if (!$lsRes) {
            \Log::info('用户不满足升级信息', ['uid' => $uid, 'type' => $type]);
            return $this->updateNode($user->pid);
        }

        \DB::beginTransaction();
        try {

            // 判断用户之前是否有节点奖
            if ($ub) {

                $ub->node_type = $type;
                $ub->save();

            } else {

                $ubData = [
                    'uid' => $uid,
                    'type' => 1,
                    'node_type' => $type,
                    'created_at' => now()->toDateTimeString(),
                ];

                UserBonus::create($ubData);

                // 附属表更新
                $ulData = [
                    'is_bonus' => 1,
                ];

                UserInfo::where('uid', $uid)->update($ulData);

            }


            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('处理节点更新信息异常', ['uid' => $uid, 'type' => $type]);

        }

        // 处理成功则再处理一遍
        return $this->updateNode($uid);

    }

    // 更新用户管理奖信息
    private function updateAdmin($uid)
    {

        // 获取用户信息
        $user = User::with('user_info')->find($uid);
        if (!$user) {
            \Log::info('用户数据不存在');
            return false;
        }

        // 判断用户是否属于非升级的用户
        if (in_array($user->id, $this->xxUid)) {
            \Log::info('用户属于非升级用户，跳过', ['uid' => $uid]);
            return $this->updateNode($user->pid);
        }

        if (!$user->user_info) {
            \Log::info('用户未报单', ['uid' => $uid]);
            return $this->updateAdmin($user->pid);
        }

        // 判断用户之前是否有节点奖
        if (isset($user->user_info->is_admin) && $user->user_info->is_admin == 1) {
            \Log::info('用户已经是管理奖用户了');
            return $this->updateAdmin($user->pid);
        }

        $lsRes = LevelService::checkAdmin($uid);
        if (!$lsRes) {
            \Log::info('用户不满足升级信息', ['uid' => $uid]);
            return $this->updateAdmin($user->pid);
        }

        \DB::beginTransaction();
        try {

            $ubData = [
                'uid' => $uid,
                'type' => 2,
                'node_type' => 0,
                'created_at' => now()->toDateTimeString(),
            ];

            UserBonus::create($ubData);

            // 附属表更新
            $ulData = [
                'is_admin' => 1,
            ];

            UserInfo::where('uid', $uid)->update($ulData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('处理管理奖更新信息异常', ['uid' => $uid]);

        }

        // 处理成功则换用户上级
        return $this->updateAdmin($user->pid);

    }

    // 直推分享奖
    public function toShareReward($uid, $num)
    {

        \Log::info('直推分享奖进来的信息', ['uid' => $uid, 'num' => $num]);

        // 获取用户信息
        $user = User::find($uid);
        if (!$user) {
            \Log::info('用户信息有误');
            return false;
        }

        // 获取用户上级信息
        $pidUser = User::find($user->pid);
        if (!$pidUser) {
            \Log::info('用户上级信息有误');
            return false;
        }

        // 验证用户是否报过单
        if (!UserInfo::checkUserValid($pidUser->id)) {
            \Log::info('用户上级没有报过单');
            return false;
        }

        // 获取分享奖的比例
        $bl = config('recommend.recommend_share_bl', 0.1);

        // 计算本次得到的数量
        $oneNum = bcmul($num, $bl, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('得到的数量太小，不处理');
            return false;
        }

        \DB::beginTransaction();
        try {

            // 用户余额增加
            Account::addAmount($pidUser->id, 1, $oneNum);

            // 用户余额日志增加
            AccountLog::addLog($pidUser->id, 1, $oneNum, 12, 1, Account::TYPE_LC, '分享奖励');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('处理直推分享奖异常', ['uid' => $uid]);

        }

        return true;

    }

    // 合伙人奖励
    public function toPartnerReward($num)
    {
        \Log::info('进来直推合伙人奖励的数据', ['num' => $num]);

        // 获取需要分红的用户
        $up = UserPartner::where('status', 1)->get();
        if ($up->isEmpty()) {
            \Log::info('没有合伙人需要分红');
            return false;
        }

        // 获取需要分红的用户数量
        $userCount = UserPartner::where('status', 1)->sum('count');

        // 获取本次分红的比例
        $bl = config('recommend.recommend_partner_bl', 0.05);

        // 获取本次分红的总数
        $totalNum = bcmul($bl, $num, 8);

        // 获取本次分红一份的分红数量
        $oneNum = bcdiv($totalNum, $userCount, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('分红数量少于0.00000001,放弃本次分红奖分红');
            return false;
        }

        \Log::info('合伙人分红的数据', ['num' => $num, 'count' => $userCount, 'bl' => $bl, 'one' => $oneNum]);

        foreach ($up as $v) {

            $newNum = bcmul($oneNum, $v->count, 8);

            // 用户余额表更新
            Account::addAmount($v->uid, 1, $newNum, Account::TYPE_LC);

            // 用户余额日志表更新
            AccountLog::addLog($v->uid, 1, $newNum, 18, 1, Account::TYPE_LC, '合伙人分红');

        }

        return true;

    }

    // 额外的奖励
    public function toExtraReward($num)
    {

        \Log::info('进来直推额外的奖励的数据', ['num' => $num]);

        // 判断有没有需要额外释放的奖励
        $eb = ExtraBonus::get();
        if ($eb->isEmpty()) {
            \Log::info('没有额外的奖励');
            return false;
        }

        foreach ($eb->toArray() as $v) {

            // 判断数据是否齐全
            if (empty($v['name'] || empty($v['recommend_bl']) || empty($v['users']))) {
                \Log::info('本次数据不齐全', [$v]);
                continue;
            }

            // 判断用户信息是否是一个数组
            if (!is_array($v['users'])) {
                \Log::info('用户信息有误,不是一个数组');
                continue;
            }

            // 获取总共能分的奖励
            $totalNum = bcmul($num, $v['recommend_bl'], 8);

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

}

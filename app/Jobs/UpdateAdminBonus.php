<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\ExtraBonus;
use App\Models\SeniorAdmin;
use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserInfo;
use App\Models\UserPartner;

use App\Models\ShopOrder;
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
    private $extraRewardUsers;
    private $extraRewardBl;

    private $seniorAdminUsers;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $num)
    {
        $this->uid = $uid;
        $this->num = $num;
        $this->extraRewardUsers = [];
        $this->extraRewardBl = 0;
        $this->seniorAdminUsers = [];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		
      
      	\Log::info('===== 独立团队长奖 =====');

        $this->indeHead($this->uid,$this->num);

        \Log::info('===== 独立团队长奖 =====');
      
      
      	\Log::info('===== 独立管理奖 =====');

       	$this->indeMana($this->uid,$this->num);

        \Log::info('===== 独立管理奖 =====');
      
      
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

        // 先获取额外奖励的人员
        $eb = ExtraBonus::find(1);
        if($eb){
            $this->extraRewardUsers = $eb->users;
            $this->extraRewardBl = $eb->recommend_bl;
        }

        \Log::info('===== 直推额外奖励 =====');

        $this->toExtraReward($this->uid, $this->num);

        \Log::info('===== 直推额外奖励 =====');

        \Log::info('===== 直推高级管理奖 =====');

        $this->toSeniorAdmin($this->uid, $this->num);

        \Log::info('===== 直推高级管理奖 =====');

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
    public function toExtraReward($uid, $num)
    {

        \Log::info('进来直推额外的奖励的数据', ['uid' => $uid, 'num' => $num]);

        // 判断有没有团队信息
        if(empty($this->extraRewardUsers) || empty($this->extraRewardBl)){
            \Log::info('没有团队奖奖励信息', ['users' => $this->extraRewardUsers, 'bl' => $this->extraRewardBl]);
            return false;
        }

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

        // 判断用户上级是否有团队奖权限
        if(!in_array($user->pid, $this->extraRewardUsers)){
            \Log::info('用户没有团队奖权限，跳过');
            return $this->toExtraReward($user->pid, $num);
        }

        // 计算用户得到的奖励数量
        $oneNum = bcmul($num, $this->extraRewardBl, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('数量太少，放弃', ['num' => $oneNum]);
            return false;
        }

        // 用户余额表更新
        Account::addAmount($user->pid, 1, $oneNum, Account::TYPE_LC);

        // 用户余额日志表更新
        AccountLog::addLog($user->pid, 1, $oneNum, 18, 1, Account::TYPE_LC, '直推团队奖');

        return true;
    }

    // 直推高级管理奖
    public function toSeniorAdmin($uid, $num, $oldLevel = 0, $oldBl = 0)
    {

        \Log::info('进来直推高级管理奖的数据', ['uid' => $uid, 'num' => $num]);

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

        // 判断上级有没有高级管理奖
        $sa = SeniorAdmin::where(['uid' => $pidUser->id, 'status' => 1])->first();
        if(!$sa){
            \Log::info('用户没有高级管理奖权限，跳过');
            return $this->toSeniorAdmin($user->pid, $num, $oldLevel, $oldBl);
        }

        // 判断当前级别有没有大于之前的级别
        if($sa->type <= $oldLevel){
            \Log::info('当前级别小于或等于上一个级别，跳过');
            return $this->toSeniorAdmin($user->pid, $num, $oldLevel, $oldBl);
        }

        // 获取奖励的比例
        if($sa->type == 2){
            $rewardBl = config('senior_admin.senior_admin_2_reward_bl', 0.2);
        }else if($sa->type == 3){
            $rewardBl = config('senior_admin.senior_admin_3_reward_bl', 0.25);
        }else{
            $rewardBl = config('senior_admin.senior_admin_1_reward_bl', 0.15);
        }

        // 换算新的比例
        $newBl = bcsub($rewardBl, $oldBl, 8);

        // 计算用户得到的奖励数量
        $oneNum = bcmul($num, $newBl, 8);
        if ($oneNum < 0.00000001) {
            \Log::info('数量太少，放弃', ['num' => $oneNum]);
            return false;
        }

        // 用户余额表更新
        Account::addAmount($user->pid, 1, $oneNum, Account::TYPE_LC);

        // 用户余额日志表更新
        AccountLog::addLog($user->pid, 1, $oneNum, 18, 1, Account::TYPE_LC, '直推高级管理奖');

        if($sa->type == 3){
            \Log::info('达到最高级，结束', ['num' => $oneNum]);
            return false;
        }

        return $this->toSeniorAdmin($user->pid, $num, $sa->type, $rewardBl);

    }
  
  	//独立团队长奖
	public function indeHead($uid,$num)
	{
		$pid_path = trim(User::where('id',$uid)->value('pid_path'),',');
		$pid_arr=explode(',',$pid_path);
		$pid_list=User::where('is_independent_head',1)->whereIn('id',$pid_arr)->get();
		
		$admin_mall_head_bl = config('senior_admin.admin_mall_head_bl');
		$reward_num=$energy_num=bcmul($admin_mall_head_bl, $num, 2);
		
		foreach($pid_list as $v){
			//为给个合伙人账户加usdt
			$m=Account::addAmount($v->id,1,$reward_num);
			// 用户余额日志表更新
			$n=AccountLog::addLog($v->id,1,$reward_num, 26, 1, Account::TYPE_LC, '独立团队长奖');
		}
	}
	
	//独立管理奖
	public function indeMana($uid,$num)
	{
		
      $mana_list=User::where('is_independent_management',1)->get();

      //$admin_mall_mana_bl = config('senior_admin.admin_mall_mana_bl');
      //$reward_num=$energy_num=bcmul($admin_mall_mana_bl, $num, 2);

      foreach($mana_list as $v){
        
        $admin_mall_mana_bl = User::where('id',$v->id)->value('independent_management_bl');
        
        if($admin_mall_mana_bl<=0){
        	continue;
        }
        
      	$reward_num=bcmul($admin_mall_mana_bl, $num, 2);

        
        //为给个合伙人账户加usdt
        $m=Account::addAmount($v->id,1,$reward_num);
        // 用户余额日志表更新
        $n=AccountLog::addLog($v->id,1,$reward_num, 27, 1, Account::TYPE_LC, '独立管理奖');
      }

		      	
	}


}

<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\EnergyOrder;
use App\Models\PledgeLevel;
use App\Services\Service;
use App\Models\User;
use App\Models\Coin;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Models\UserPartner;
use App\Models\EnergyLog;

use App\Services\EnergyService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnergyDynamicRelease implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;
    private $num;
	private $totalPrice;
  	private $orid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $num,$totalPrice,$orid)
    {
        $this->uid = $uid;
        $this->num = $num;
      	$this->totalPrice = $totalPrice;
      	$this->orid = $orid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        \Log::info('=-=-=-=-=-  进行动态释放操作  -=-=-=-=-=');

        // 获取用户信息
        $user = User::find($this->uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }
      
      	\Log::info('=-=-=-=-=-  能量 独立团队长分红-新增  -=-=-=-=-=');
      	$this->energyHeadLv($this->uid,$this->totalPrice);
      	\Log::info('=-=-=-=-=-  能量 独立团队长分红-新增  -=-=-=-=-=');
          
        \Log::info('=-=-=-=-=-  全网首次报单合伙人奖励-新增  -=-=-=-=-=');
      	$this->allFirstPartner($this->uid);
      	\Log::info('=-=-=-=-=-  全网首次报单合伙人奖励-新增  -=-=-=-=-=');
      
		\Log::info('=-=-=-=-=-  额外一代加速开始-新增  -=-=-=-=-=');
      	$this->additionalSpeed($this->uid, $this->totalPrice);
      	\Log::info('=-=-=-=-=-  额外一代加速结束-新增  -=-=-=-=-=');
      	
      	 \Log::info('=-=-=-=-=-  能量团长奖-新增  -=-=-=-=-=');
         $this->getEnergryaward($this->uid,$this->totalPrice);
        \Log::info('=-=-=-=-=-  能量团长奖-新增  -=-=-=-=-=');
      
        $this->toRecommendReward($user->pid, $this->num, 1);

        $this->toCommunityReward($user->pid, $this->num);
      
      	
      
       
        
        \Log::info('=-=-=-=-=-  结束动态释放操作  -=-=-=-=-=');

    }

    // 进行直推奖操作
    private function toRecommendReward($uid, $num, $layer)
    {

        \Log::info('进行代数奖进来的数据', ['uid' => $uid, 'num' => $num, 'layer' => $layer]);

        // 先判断层数是否超过3层
        if($layer > 2){
            \Log::info('已经进行到3代了，结束奖励');
            return false;
        }

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        // 获取用户推荐的有效用户数
        $eoCount = EnergyOrder::getEnergyValidNum($uid);
        if($eoCount < 1){
            \Log::info('用户直推的有效用户数量没有达到层数,跳过', ['count' => $eoCount, 'layer' => $layer]);
            $layer++;
            return $this->toRecommendReward($user->pid, $num, $layer);
        }

        \DB::beginTransaction();
        try {

            $exp = $layer == 1 ? '一层加速奖励' : '二层加速奖励';

            // 获取用户本代能拿到的比例
            $bl = $this->getRecommendLayerBl($layer);
            $oneNum = bcmul($num, $bl, 8);
            if($oneNum > 0){
                EnergyService::orderSpeedRelease($uid, $oneNum, $exp, $this->uid);
            }else{
                \Log::info('代数奖层数奖励比例异常');
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('代数奖加速释放出现异常');

        }

        $layer++;
        return $this->toRecommendReward($user->pid, $this->num, $layer);

    }

    // 获取直推层数比例
    private function getRecommendLayerBl($layer)
    {

        switch ($layer){

            case 1:
                $num = config('energy.energy_recommend_1_reward_bl', 0.05);
                break;

            case 2:
                $num = config('energy.energy_recommend_2_reward_bl', 0.1);
                break;

            default:
                $num = 0;
                break;

        }

        return $num;

    }

    // 进行社区节点奖操作
    private function toCommunityReward($uid, $num, $oldBl = 0)
    {

        \Log::info('进行社区节点奖进来的数据', ['uid' => $uid, 'num' => $num, 'bl' => $oldBl]);
        if($oldBl >= config('energy.energy_community_50000_reward_bl', 0.05)){
            \Log::info('已经发放完成，结束发放');
            return false;
        }

        // 获取用户信息
        $user = User::find($uid);
        if(!$user){
            \Log::info('用户信息不存在');
            return false;
        }

        // 获取用户本次加速的比例
        $bl = $this->getCommunityRewardBl($user->pledge_num);
        if($bl <= 0){
            \Log::info('用户持币数量不够,跳过', ['cb_num' => $user->pledge_num]);
            return $this->toCommunityReward($user->pid, $num, $oldBl);
        }

        // 判断该用户实际拿到的比例
        if($bl <= $oldBl){
            \Log::info('用户持币数量级别的奖励已被领取,跳过', ['cb_num' => $user->pledge_num]);
            return $this->toCommunityReward($user->pid, $num, $oldBl);
        }else{
            $nBl = bcsub($bl, $oldBl, 4);
            $oldBl = $bl;
            $bl = $nBl;
        }

        \DB::beginTransaction();
        try {

            $oneNum = bcmul($num, $bl, 8);
            if($oneNum > 0){
                EnergyService::orderSpeedRelease($uid, $oneNum, '社区加速奖励', $this->uid);
            }else{
                \Log::info('社区节点奖比例异常');
            }

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('社区节点奖加速释放出现异常');

        }

        return $this->toCommunityReward($user->pid, $this->num, $oldBl);

    }

    // 获取社区节点奖奖励比例
    private function getCommunityRewardBl($num)
    {

        $bl = 0;

        $pl = PledgeLevel::where('num', '<=', $num)->latest('num')->first();
        if($pl){
            $bl = $pl->pledge_bl;
        }

        return $bl;

    }
  
  	//能量团队长奖(判断购买的人是否是首次购买,是触发(找所有上级,如果被设置为队长则给奖励))
    private function getEnergryaward($uid,$totalPrice)
    {
              	
       //判断是否是首次报单
        $count = EnergyOrder::where('uid',$uid)->count();
      	
        if($count == 1){
          	$pid_path = trim(User::where('id',$uid)->value('pid_path'),',');
			$pid_arr=explode(',',$pid_path);
			$pid_list=User::where('energy_captain_award',1)->whereIn('id',$pid_arr)->get();
          	
          	$bl = config('energy.energy_captain_bl');
          	$reward_num=bcmul($bl, $totalPrice, 2);
          
          	foreach($pid_list as $v){
              //为给个合伙人账户加usdt
              $m=Account::addAmount($v->id,1,$reward_num);
              // 用户余额日志表更新
              $n=AccountLog::addLog($v->id,1,$reward_num, 100, 1, Account::TYPE_LC, '能量团队长奖返利');
          	}
        }
    }
  
  	public function additionalSpeed($uid,$totalPrice)
	{	
		//一代加速设置
		$userPid=User::where('id',$uid)->value('pid');	//pid
		$userp=User::where('id',$userPid)->first();	//pid用户的数据
		
      	//受否开通加速
		if($userp->is_open_speedup){
			//查询加速比例
			$jiasu_bl = config('energy.energy_jiasu_bl');
			
			//加速能量数量
			$energy_num=bcmul($totalPrice, $jiasu_bl, 2);
          	
          	//先验证用户是否有能量账户
			if(UserWallet::where('uid', $userPid)->exists()){
				
				$pidWallet=UserWallet::where('uid',$userPid)->first();
				//pid账户余额是否足够
				if($pidWallet->energy_frozen_num < $energy_num){
					$energy_num=$pidWallet->energy_frozen_num;
				}
              	
				$m=UserWallet::reduceEnergyFrozenNum($userPid, $energy_num);
				$n=UserWallet::addEnergyNum($userPid, $energy_num);
            	// 能量资产余额表日志新增
				EnergyLog::addLog($userPid, 1, 'energy_goods', $this->orid, '额外一代加速释放', '+', $energy_num, 2,$uid);

			}
		} 
	}
  
  	//全网首次能量报单合伙人奖励
	public function allFirstPartner($uid)
	{
      	//是否为该用户首次报单
		$user_order_num = EnergyOrder::where('uid', $uid)->count();
	
		if($user_order_num == 1){
			
			//查询用户订单信息
			$user_order = EnergyOrder::where('uid', $uid)->first();
			
			//根据报单计算一共有多少奖励
			$total_price = bcmul($user_order->goods_price, $user_order->goods_num, 2);
			$bl=config("user_partner.recommend_all_first_bl");
          
          	$all_reward=bcmul($total_price, $bl, 2);
          	
			//计算总分数
			$copies = UserPartner::sum('count');
			
			//计算一份的奖励数量
			$one_reward = bcdiv($all_reward,$copies,2);
			
			//查询合伙人
			$partner = UserPartner::all()->pluck('count','uid')->toArray();
			
			foreach($partner as $k=>$v){
				
				//查询每个合伙人持有几份
				$one_copies = UserPartner::where('uid',$k)->value('count');
				
				//计算每个合伙人可以拿到多少奖励
				$one_partner = bcmul($one_reward, $one_copies, 2);
				
				//为给个合伙人账户加usdt
				Account::addAmount($k,1,$one_partner);
              	// 用户余额日志表更新
			  	AccountLog::addLog($k,1,$one_partner, 28,1, Account::TYPE_LC, '全网首次能量报单合伙人奖励');
			}
		}
	}
  
  	public function star_bl($uid)
    {
        $star_community = User::where('id',$uid)->value('energy_head_lv');
        $star_bl = config("energy.energy_head_lv{$star_community}");
        return $star_bl;
    }
  
  	
  	//能量独立团队长分红
    public function energyHeadLv($uid,$bd_price)
    {
      $pid_path = trim(User::where('id',$uid)->value('pid_path'),',');
      $pids = explode(",",$pid_path);
      $teams = User::where('energy_head_lv','>','0')->whereIn('id',$pids)->pluck('id')->toArray();
      if(count($teams)<=0){
      	\Log::info('上面没有能量团队长等级');
         return false;
      }
      $teams = array_reverse($teams);
      //所得总比例
      	$all_star_bl = config("energy.energy_head_lv1");
      	//$all_jl=bcmul($bd_price, $all_star_bl, 2);
		$yf_bl=0;
      	$data_yfbl=[];
      	$nn="";
      	for($i=0;$i<count($teams);$i++){
          
          	if($yf_bl>=$all_star_bl){
            	exit;
            }
          	
          	$bl=$this->star_bl($teams[$i]);
          	//判断该比例是否返过
       	 	if(in_array($bl,$data_yfbl)){
        		continue;
        	}
          	//如果已经返过0.2则不返0.1
        	if(count($data_yfbl)>0){
        		if(max($data_yfbl)>$bl){
            		continue;
        		}
        	}
          
          	$jicha=bcsub($bl,end($data_yfbl),4);	//级差比例
          
          	$sy_bl=bcsub($all_star_bl,$yf_bl,4);	//剩余比例
          
			//$my_bl=bcsub($sy_bl,$jicha,4);	//本次应给的比例
			
          	if($sy_bl<=$jicha){
            	$my_bl=$sy_bl;
            }else{
            	$my_bl=$jicha;
            }
          	
          	$my_jl=bcmul($bd_price, $my_bl, 4);
          	$m=Account::addAmount($teams[$i],1,$my_jl);
			\Log::info("为".$teams[$i]."用户添加了".$my_jl);
          
          	// 用户余额日志增加
          	AccountLog::addLog($teams[$i], 1, $my_jl, 103, 1, Account::TYPE_LC, '能量独立团队长分红');
          	
          	array_push($data_yfbl,$bl);
          	$yf_bl = bcadd($yf_bl,$my_bl,4);
          	
          	//$nn.=$jicha.",".$sy_bl.','.$my_bl.",".$my_jl.','.$bd_price." ";

        }
      	//return $nn;
    }
	  

}

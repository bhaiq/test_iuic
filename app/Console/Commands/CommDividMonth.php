<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Models\CommunityDividend;
use App\Models\User;
use App\Models\Account;
use App\Models\AccountLog;
use Illuminate\Support\Facades\Log;

class CommDividMonth extends Command {

    protected $name = 'CommDividMonth';//命令名称

    protected $description = '每月5号按分红等级结算分红'; // 命令描述，没什么用

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {	
		\Log::info('结算每月分红业绩开始');

		    $list=CommunityDividend::orderBy('uid','desc')->get()->toArray();
			
          	//$mm="";

          	for($j=0;$j<count($list);$j++){
              	
              	$total_yj_one=CommunityDividend::where('uid',$list[$j]['uid'])->value('last_month');
                    
              	if($total_yj_one<=0){
                    \Log::info('业绩小于0跳过');
                	continue;
              	}	
                    
           		$pid_path = trim(User::where('id',$list[$j]['uid'])->value('pid_path').$list[$j]['uid'] .',',',');
      			$pids = explode(",",$pid_path);
      			$teams = User::where('star_community','>','0')->whereIn('id',$pids)->pluck('id')->toArray();
              	
      			if(count($teams)<=0){
      				\Log::info('上面没有IUIC社群月度分红奖');
         			continue;
      			}
              
                $teams = array_reverse($teams);

                //所得总比例
                $all_star_bl = config("senior_admin.community_bl_lv4");
                $yf_bl=0;
                $data_yfbl=[];
				//$nn="";
                for($i=0;$i<count($teams);$i++){
          
                    if($yf_bl>=$all_star_bl){
                        \Log::info("已返比例大于总比例");
                         break;
                    }

                    $bl=$this->comm_bl($teams[$i]);
                    
                    
                    //判断该比例是否返过
                    if(in_array($bl,$data_yfbl)){
                        \Log::info("该比例是返过");
                         continue;
                    }
					//如果已经返过0.2则不返0.1
					if(count($data_yfbl)>0){
						if(max($data_yfbl)>$bl){
                            \Log::info("已经返过0.2则不返0.1");
							continue;
						}
					}
					
					$total_yj=CommunityDividend::where('uid',$teams[$i])->value('last_month');
				
					if($total_yj<=0){
                        \Log::info("total_yj小于0");
						 continue;
					}	
                    
                    
					$jicha=bcsub($bl,end($data_yfbl),4);	//级差比例
				
				
					$sy_bl=bcsub($all_star_bl,$yf_bl,4);	//剩余比例
					
					
					//$my_bl=bcsub($sy_bl,$jicha,4);	//本次应给的比例

                    if($sy_bl<=$jicha){
                        $my_bl=$sy_bl;
                    }else{
                        $my_bl=$jicha;
                    }

                    //$community_zong_bl= config("senior_admin.community_zong_bl");
                    //$my_zong_fenhong=bcmul($total_yj, $community_zong_bl, 4);
                    	
                  	$my_jl=bcmul($total_yj, $my_bl, 4);
                    
                    $m=Account::addAmount($teams[$i],1,$my_jl);
					\Log::info("为".$teams[$i]."用户添加了".$my_jl);
                 	// 用户余额日志增加
                  	AccountLog::addLog($teams[$i], 1, $my_jl, 103, 1, Account::TYPE_LC, 'IUIC社群月度分红奖');
					
                    $n=CommunityDividend::where('uid',$teams[$i])->update(['last_month'=>0]);
                  	array_push($data_yfbl,$bl);
                  	$yf_bl = bcadd($yf_bl,$my_bl,4);
					//dd($yf_bl);

                    //$total_yj=CommunityDividend::where('uid',$teams[$i])->value('last_month');
                    //$nn.=$teams[$i].','.$my_bl.','.$my_jl.','.$jicha.','.$sy_bl.','.$total_yj.' ';
              }
              //$mm.=$nn."<br/>";
            }
          	//dd($mm);
          	
		
		\Log::info('结算每月分红业绩结束');
    }
	
	public function comm_bl($uid){
	  	$total=CommunityDividend::where('uid',$uid)->value('total');
	
	  	$lv1=config("senior_admin.community_lv1");
	  	$lv2=config("senior_admin.community_lv2");
	  	$lv3=config("senior_admin.community_lv3");
	  	$lv4=config("senior_admin.community_lv4");
	  
	  	if($total>=$lv4){
	    	return config("senior_admin.community_bl_lv4");
	    }elseif($total>=$lv3){
	    	return config("senior_admin.community_bl_lv3");
	    }elseif($total>=$lv2){
	    	return config("senior_admin.community_bl_lv2");
	    }elseif($total>=$lv1){
	    	return config("senior_admin.community_bl_lv1");
	    }else{
	    	return '0';
	    }
	}
}
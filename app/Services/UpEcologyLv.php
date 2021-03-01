<?php


namespace App\Services;




use App\Models\Account;
use App\Models\AccountLog;
use App\Models\KuangjiOrder;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use Illuminate\Support\Facades\Log;

class UpEcologyLv
{
    //模式购买   影响上级升级
    public function up_ecology_lv($uid,$type=1)
    {
        $user = User::where('id',$uid)->first();
        $p_user = User::where('id',$user->pid)->first();
        if(empty($p_user)){
            Log::info("找不到上级,停止");
            return;
        }
        $this->first_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->two_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->three_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->four_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->five_ecology($p_user->id,$p_user->ecology_lv,$type);
        return $this->up_ecology_lv($p_user->id,1);
    }

    //降级
    public function down_ecology_lv($uid,$type=2)
    {
        $user = User::where('id',$uid)->first();
        $p_user = User::where('id',$user->pid)->first();
        if(empty($p_user)){
            Log::info("找不到上级,停止");
            return;
        }
        $this->four_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->three_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->two_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->first_ecology($p_user->id,$p_user->ecology_lv,$type);
        $this->quali_ecology($p_user->id,$p_user->ecology_lv,$type);
        return $this->down_ecology_lv($p_user->id,2);
    }

    //一级生态

    /**
     * @param $uid    用户id
     * @param $level  level
     * @param $type  1升级2降级
     */
    public function first_ecology($uid,$level,$type)
    {
        if($type == 1){
            if($level >= 3){
                Log::info("当前uid".$uid."已是该等级1级或大于该等级,不用升");
                return;
            }
            //判断是否直推三人合格消费者
            $count = User::where('pid',$uid)
                ->where('ecology_lv','>=','2')
                ->count();
            Log::info("直推合格消费者人数".$count);
            if($count >= 3){
                User::where('id',$uid)->update(['ecology_lv'=>3,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."升一级生态");
                return;
            }else{
                Log::info("当前uid".$uid."未推够三个合格消费者,不升级");
                return;
            }
        }else if($type == 2){
            if($level <= 3){
                Log::info("当前已是该等级1级或小于该等级,不用降");
                return;
            }
            //判断所有部门是否有三个一级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','3')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否小于1
            rsort($count);
            if(count($count >= 3) && $count[2] < 1){
                User::where('id',$uid)->update(['ecology_lv'=>3,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."降一级生态");
                return;
            }else{
                Log::info("用户uid".$uid."推够三个一级生态,不降级");
                return;
            }
        }
    }

    //二级生态
    public function two_ecology($uid,$level,$type)
    {
        if($type == 1){
            if($level >= 4){
                Log::info("当前uid".$uid."已是该等级2级或大于该等级,不用升");
                return;
            }
            //判断所有部门是否有三个一级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','3')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否大于等于1
            rsort($count);
            if(count($count >= 3) && $count[2] >= 1){
                User::where('id',$uid)->update(['ecology_lv'=>4,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."升二级生态");
                return;
            }else{
                Log::info("用户uid".$uid."未推够三个一级生态,不升级");
                return;
            }
        }else if($type == 2){
            if($level <= 4){
                Log::info("当前已是该等级2级或小于该等级,不用降");
                return;
            }
            //判断所有部门是否有三个二级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','4')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否大于等于1
            rsort($count);
            if(count($count >= 3) && $count[2] < 1){
                User::where('id',$uid)->update(['ecology_lv'=>4,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."降二级生态");
                return;
            }else{
                Log::info("用户uid".$uid."推够三个二级生态,不降级");
                return;
            }
        }

    }

    //三级生态
    public function three_ecology($uid,$level,$type)
    {
        if($type == 1){
            if($level >= 5){
                Log::info("当前uid".$uid."当前已是该等级3级或大于该等级,不用升");
                return;
            }
            //判断所有部门是否有三个二级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','4')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否小于1
            rsort($count);
            if(count($count >= 3) && $count[2] >= 1){
                User::where('id',$uid)->update(['ecology_lv'=>5,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."升三级生态");
                return;
            }else{
                Log::info("当前uid".$uid."未推够三个二级生态,不升级");
                return;
            }
        }else if($type == 2){
            if($level <= 5){
                Log::info("当前uid".$uid."已是该等级3级或小于该等级,不用降");
                return;
            }
            //判断所有部门是否有三个三级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','5')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否小于1
            rsort($count);
            if(count($count >= 3) && $count[2] < 1){
                User::where('id',$uid)->update(['ecology_lv'=>5,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."降三级生态");
                return;
            }else{
                Log::info("当前uid".$uid."推够三个合格消费者,不降级");
                return;
            }
        }

    }

    //四级生态
    public function four_ecology($uid,$level,$type)
    {
        if($type == 1){
            if($level >= 6){
                Log::info("当前uid".$uid."已是该等级4级或大于该等级,不用升");
                return;
            }
            //判断所有部门是否有三个三级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','5')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否大于等于1
            rsort($count);
            if(count($count >= 3) && $count[2] >= 1){
                User::where('id',$uid)->update(['ecology_lv'=>6,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."升四级生态");
                return;
            }else{
                Log::info("当前uid".$uid."未推够三个三级生态,不升级");
                return;
            }
        }else if($type == 2){
            if($level <= 6){
                Log::info("当前uid".$uid."已是该等级4级或小于该等级,不用降");
                return;
            }
            //判断所有部门是否有三个四级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k] = User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','6')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否小于1
            rsort($count);
            if(count($count >= 3) && $count[2] < 1){
                User::where('id',$uid)->update(['ecology_lv'=>6,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."降四级生态");
                return;
            }else{
                Log::info("当前uid".$uid."推够三个四级消费者,不降级");
                return;
            }
        }

    }
    //五级生态
    public function five_ecology($uid,$level,$type)
    {
        if($type == 1){
            if($level >= 7){
                Log::info("当前uid".$uid."已是该等级5级或大于该等级,不用升");
                return;
            }
            //判断所有部门是否有三个五级生态(先找出所有直推,再找出所有直推的下级判断)
            $direct_couts = User::where('pid',$uid)->select('id')->get();
            $count = [];
            foreach ($direct_couts as $k => $v){
                $count[$k]= User::where('pid_path', 'like', '%,' . $v->id . ',%')
                    ->where('ecology_lv','>=','6')
                    ->count();
            }
            //将数组元素从大到小排列,判断第三个数是否小于1
            rsort($count);
            if(count($count >= 3) && $count[2] >= 1){
                User::where('id',$uid)->update(['ecology_lv'=>7,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."升五级生态");
                return;
            }else{
                Log::info("当前uid".$uid."未推够三个四级生态,不升级");
                return;
            }
        }

    }

    //合格消费者(一级降合格消费者)
    public function quali_ecology($uid,$level,$type)
    {
        if($type == 2){
            if($level <= 2){
                Log::info("当前uid".$uid."已是该等级合格消费者或小于该等级,不用降");
                return;
            }
            //需要直推三个合格消费者
            $count = User::where('pid',$uid)
                ->where('ecology_lv','>=','2')
                ->count();
            if($count < 3){
                User::where('id',$uid)->update(['ecology_lv'=>2,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
                Log::info("用户uid".$uid."降为合格消费者");
                return;
            }else{
                Log::info("当前uid".$uid."推够三个合格消费者,不降级");
                return;
            }
        }

    }

}
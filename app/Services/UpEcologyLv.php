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
    public function up_ecology_lv($uid)
    {
        $user = User::where('id',$uid)->first();
        $p_user = User::where('id',$user->pid)->first();
        if(empty($p_user)){
            Log::info("找不到上级,停止");
            return;
        }
        $this->first_ecology($p_user->id,$p_user->ecology_lv);
        $this->two_ecology($p_user->id,$p_user->ecology_lv);
        $this->three_ecology($p_user->id,$p_user->ecology_lv);
        $this->four_ecology($p_user->id,$p_user->ecology_lv);
        $this->five_ecology($p_user->id,$p_user->ecology_lv);
        return $this->up_ecology_lv($p_user->id);
    }

    //升级一级生态
    public function first_ecology($uid,$level)
    {
        if($level >= 3){
            Log::info("当前已是该等级或大于该等级,不用升");
            return;
        }
        //判断是否直推三人合格消费者
        $count = User::where('pid',$uid)
            ->where('ecology_lv','>=','2')
            ->count();
        if($count >= 3){
            User::where('id',$uid)->update(['ecology_lv'=>3,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            Log::info("用户uid".$uid."升一级生态");
            return;
        }else{
            Log::info("未推够三个合格消费者,不升级");
            return;
        }
    }

    //升级二级生态
    public function two_ecology($uid,$level)
    {
        if($level >= 4){
            Log::info("当前已是该等级或大于该等级,不用升");
            return;
        }
        //判断所有下级是否有三个一级生态
        $count = User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv','>=','3')
            ->count();
        if($count >= 3){
            User::where('id',$uid)->update(['ecology_lv'=>4,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            Log::info("用户uid".$uid."升二级生态");
            return;
        }else{
            Log::info("未推够三个一级生态,不升级");
            return;
        }
    }

    //升级三级生态
    public function three_ecology($uid,$level)
    {
        if($level >= 5){
            Log::info("当前已是该等级或大于该等级,不用升");
            return;
        }
        //判断所有下级是否有三个二级生态
        $count = User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv','>=','4')
            ->count();
        if($count >= 3){
            User::where('id',$uid)->update(['ecology_lv'=>5,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            Log::info("用户uid".$uid."升三级生态");
            return;
        }else{
            Log::info("未推够三个二级生态,不升级");
            return;
        }
    }

    //升级四级生态
    public function four_ecology($uid,$level)
    {
        if($level >= 6){
            Log::info("当前已是该等级或大于该等级,不用升");
            return;
        }
        //判断所有下级是否有三个三级生态
        $count = User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv','>=','5')
            ->count();
        if($count >= 3){
            User::where('id',$uid)->update(['ecology_lv'=>6,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            Log::info("用户uid".$uid."升四级生态");
            return;
        }else{
            Log::info("未推够三个三级生态,不升级");
            return;
        }
    }
    //升级五级生态
    public function five_ecology($uid,$level)
    {
        if($level >= 7){
            Log::info("当前已是该等级或大于该等级,不用升");
            return;
        }
        //判断所有下级是否有三个三级生态
        $count = User::where('pid_path', 'like', '%,' . $uid . ',%')
            ->where('ecology_lv','>=','6')
            ->count();
        if($count >= 3){
            User::where('id',$uid)->update(['ecology_lv'=>7,'ecology_lv_time'=>date('Y-m-d H:i:s')]);
            Log::info("用户uid".$uid."升五级生态");
            return;
        }else{
            Log::info("未推够三个四级生态,不升级");
            return;
        }
    }

}
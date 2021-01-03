<?php


namespace App\Services;




use App\Models\KuangjiOrder;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use Illuminate\Support\Facades\Log;

class JlkReleaseService
{

    protected $created_at;
    public function __construct()
    {
        $this->created_at = "2020-12-01 15:37:58";

    }
    //开发矿池中心加速释放(购买者质押矿(num) 老用户不得,无极差,扣除手续费,只得一次,之后不再得)
    // 新质押分享者得(num*5%)
    // 新一星加速(num*2%)
    // 新二星加速(num*3%)
    // 新三星加速(num*5%)
    //例:会员质押：12000 IUIC：
    //分享者：享有5%加速释放释放，自己的矿池600枚，扣除手续费20%，实际到账480枚可用。
    //一星获得团队加速自己的矿池2%，240枚X80%=192枚
    //二星获得团队加速自己的矿池3%，360枚X80%=288枚
    //三星获得团队加速自己的矿池5%，600枚X80%=480枚

    public function kuang_release($uid,$kuang_num)
    {
        //改用户第一次质押返奖,之后重复质押就不再返奖
        $kuangji_list = KuangjiOrder::where('uid',$uid)->where('created_at','>',$this->created_at)->count();
        if($kuangji_list >= 2){
            Log::info("已质押过一次,重复质押上级不得奖");
            return;
        }
        //直推获得加速奖励,
        $this->zhi_release($uid,$kuang_num);
        //1星2星3星加速奖励
        $star_level = [];
        $this->star_release($uid,$kuang_num,$star_level);
        return;
    }

    public function zhi_release($uid,$kuang_num)
    {
        $pid = User::where('id',$uid)->value('pid');
        $puser = User::where('id',$pid)->first();
        if(empty($puser)){
            Log::info("上级不存在终止");
            return;
        }
        if($puser->created_at < strtotime($this->created_at)){
            Log::info("老用户不享有加速释放奖励",['uid'=>$pid,'time'=>$puser->created_at,'times'=>strtotime($this->created_at)]);
            return;
        }
        //查找是否有质押记录
        $log = KuangjiOrder::where('uid',$pid)->first();
        if(empty($log)){
            Log::info("该用户没有质押记录不得加速释放奖励",['uid'=>$pid]);
            return;
        }
        //有记录给奖
        $get_num = $kuang_num * config('kuangji.zhitui_kuangji_release_rate') * 0.8;
        $ui = UserInfo::where('uid', $pid)->first();
        if(empty($ui)){
            Log::info("该用户没有矿池");
            return;
        }
        if($get_num >= bcsub($ui->buy_total, $ui->release_total, 4)){
            $true_num = bcsub($ui->buy_total, $ui->release_total, 4);
        }else{
            $true_num = $get_num;
        }
        UserInfo::where('uid',$pid)->increment('release_total',$true_num);
        UserWalletLog::addLog($pid,'user_info',$ui->id,'加速释放奖励','-',$true_num,2,1);
        Log::info('直推获得加速奖励',['uid'=>$pid,'num'=>$true_num]);
    }

    public function star_release($uid,$kuang_num,$star_level)
    {
        $pid = User::where('id',$uid)->value('pid');
        $puser = User::where('id',$pid)->first();
        if(empty($puser)){
            Log::info("上级不存在终止");
            return;
        }
        if($puser->created_at < strtotime($this->created_at)){
            Log::info("老用户不享有加速释放奖励",['uid'=>$pid,'time'=>$puser->created_at,'times'=>strtotime($this->created_at)]);
            return $this->star_release($pid,$kuang_num,$star_level);
        }
        //查找是否有质押记录
        $log = KuangjiOrder::where('uid',$pid)->first();
        if(empty($log)){
            Log::info("该用户没有质押记录不得加速释放奖励",['uid'=>$pid,'created'=>$this->created_at]);
            return $this->star_release($pid,$kuang_num,$star_level);
        }
        if(count($star_level) >= 3){
            Log::info("奖励已返完");
            return;
        }
        if($puser->star_community < 1) {
            Log::info("该用户星级社群等级过低",['uid'=>$pid,'star_level'=>$puser->star_community]);
            return $this->star_release($pid,$kuang_num,$star_level);
        }
        if(in_array($puser->star_community,$star_level)){
            Log::info("该用户星级等级已返过",['uid'=>$pid,'star_level'=>$puser->star_community]);
            return $this->star_release($pid,$kuang_num,$star_level);
        }
        //返奖
        if($puser->star_community == 1){
            $rate = config('kuangji.one_star_kuangji_release_rate');
        }else if($puser->star_community == 2){
            $rate = config('kuangji.two_star_kuangji_release_rate');
        }else{
            $rate = config('kuangji.three_star_kuangji_release_rate');
        }
        $get_num = $kuang_num * $rate * 0.8;
        $ui = UserInfo::where('uid', $pid)->first();
        if(empty($ui)){
            Log::info("该用户没有矿池");
            return $this->star_release($pid,$kuang_num,$star_level);
        }
        if($get_num >= bcsub($ui->buy_total, $ui->release_total, 4)){
            $true_num = bcsub($ui->buy_total, $ui->release_total, 4);
        }else{
            $true_num = $get_num;
        }
        UserInfo::where('uid',$pid)->increment('release_total',$true_num);
        Log::info('星级获得加速奖励',['uid'=>$pid,'num'=>$true_num,'star_level'=>$puser->star_community]);
        UserWalletLog::addLog($pid,'user_info',$ui->id,'加速释放奖励','-',$true_num,2,1);
        array_push($star_level,$puser->star_community);
        return $this->star_release($pid,$kuang_num,$star_level);
    }





}
<?php
namespace App\Services;

use App\Models\EcologyConfigPub;//生态公共配置表
use App\Models\EcologyConfig;//生态等级配置表
use App\Models\EcologyCreadit;
use App\Models\EcologyCreaditDay;
use App\Models\EcologyCreaditLog;
use App\Models\EcologyCreaditOrder;
use App\Models\EcologyCreaditsDayFushu;//日全网新增业绩结算附属表
use App\Models\User;//用户表

//手动结算 生态2团队长奖
class EcologySettlement
{
    protected $configPub;//配置
    protected $configLv;
    protected $ecdModel;
    protected $ecdfModel;
    protected $ecModel;
    protected $eclModel;
    protected $ecoModel;
    protected $uModel;

    protected $dayid;//结算日期id
    protected $num;//结算总数
    protected $set_time;//结算时间


    public function __construct()
    {
        $this->configPub = EcologyConfigPub::find(1)->toArray();//生态公共配置表
        $this->configLv = EcologyConfig::get();//生态等级配置表

        $this->ecdModel = new EcologyCreaditDay();//日全网新增业绩结算表
        $this->ecdfModel = new EcologyCreaditsDayFushu;//日全网新增业绩结算附属表
        $this->ecModel = new EcologyCreadit();//生态2积分余额表
        $this->eclModel = new EcologyCreaditLog();//生态2积分余额日志表
        $this->ecoModel = new EcologyCreaditOrder();//生态2积分订单表
        $this->uModel = new User;//用户表

    }

    /*
        $dayid 结算日期id
        $num   结算总数
        $dayRes   结算日期信息
        $set_time 结算时间
    */
    public function settlement($dayid,$num,$set_time)
    {
        // $res = $this->ecdModel->where('id',$dayid)->first();//结算日期信息
        // dd($res);
//        if(!$dayRes){
//            return ['code'=>0,'msg'=>'结算日期信息有误'];
//            // \Log::info('结算日期信息有误');
//            // return true;
//        }
//        if ($dayRes['set_status']['value'] != 0) {
//            return ['code'=>0,'msg'=>'请勿重复结算'];
//            // \Log::info('请勿重复结算');
//            // return true;
//        }

        $this->dayid = $dayid;
        $this->num = $num;
        $this->set_time = $set_time;

        \Log::info('====== 生态2团队长奖 开始 ======');

        //结算 生态2团队长奖
        $this->levelSettlementTotal();

        \Log::info('====== 生态2团队长奖 结束 ======');

        // return true;
        return ['code'=>1,'msg'=>'结算成功'];
    }

    /*生态2团队长奖 结算
    */
    public function levelSettlementTotal()
    {
        if (!$this->configLv) {
            return ['code'=>0,'msg'=>'等级配置信息有误'];
            // \Log::info('等级配置信息有误');
            // return true;
        }
        // dd($this->configLv);
        // 各个等级结算
        foreach ($this->configLv as $k => $v) {
            $this->levelSettlement($v['id'],$v['rate_bonus']);
        }
        return true;
    }

    /*各个等级结算
        $level 等级
        $rate 结算比例
    */
    public function levelSettlement($level,$rate)
    {
        //等级人数
        $peopleNum = $this->uModel
            ->where('ecology_lv',$level)
            ->where('ecology_lv_close',0)
            ->count();

        // 个人结算数 总数*比例/总人数
        $oneNum = 0;
        if ($peopleNum > 0) {
            // $oneNum = bcdiv(($this->num)*$rate,$peopleNum,6);
            $oneNum = $this->num*$rate/$peopleNum;
        }

        //记录结算信息
        $ecdfData = [
            'day_id' => $this->dayid,
            'type' => $level,
            'rate' => $rate,
            'people_num' => $peopleNum,
            'one_num' => $oneNum,
            'created_at' => $this->set_time
        ];
        $this->ecdfModel->insert($ecdfData);
        \Log::info('等级id-'.$level.'结算',$ecdfData);

        if ($rate <= 0) {
            //不用结算
            \Log::info('生态2团队长奖 等级结算比例<=0 不用结算',['level'=>$level,'rate'=>$rate]);
            return true;
        }
        if ($peopleNum <= 0) {
            //不用结算
            \Log::info('生态2团队长奖 等级人数<=0 不用结算',['level'=>$level,'peopleNum'=>$peopleNum]);
            return true;
        }
        //该等级 用户id集合
        $ids = $this->uModel
            ->where('ecology_lv',$level)
            ->where('ecology_lv_close',0)
            ->pluck('id')
            ->toArray();
        foreach ($ids as $k => $v) {
            $this->peopleOne($v,$oneNum);
        }
        return true;
    }

    /*用户结算
        $uid 用户id
        $oneNum 个人结算数
    */
    public function peopleOne($uid,$oneNum)
    {
        // 判断冻结是否充足
        $ecRes = $this->ecModel->where('uid',$uid)->first();
        if (!$ecRes) {
            \Log::info('生态2团队长奖 用户无积分资产 不结算',['uid'=>$uid]);
            return false;
        }
        if ($ecRes['amount_freeze'] <= 0) {
            \Log::info('生态2团队长奖 用户无冻结积分资产 不结算',['uid'=>$uid]);
            return false;
        }
        //冻结积分不足 全释放
        if ($oneNum >= $ecRes['amount_freeze']) {
            $oneNum = $ecRes['amount_freeze'];
            //插入释放完成时间
            EcologyCreadit::where('uid',$uid)->update(['release_end_time'=>date('Y-m-d H:i')]);
        }

        // -冻结积分
        $this->ecModel->where('uid',$uid)->decrement('amount_freeze',$oneNum);
        //记录日志
        $this->eclModel->addlog($uid,$oneNum,2,4,'生态2团队长奖',2);
        //顺延释放冻结订单
        $this->minusOrder($uid,$oneNum);

        // +可用积分
        $this->ecModel->where('uid',$uid)->increment('amount',$oneNum);
        //记录日志
        $this->eclModel->addlog($uid,$oneNum,1,4,'生态2团队长奖',1);
        return true;
    }

    /*订单顺延释放
        $num 释放数
    */
    public function minusOrder($uid,$num)
    {
        if ($num <= 0) {
            return true;
        }
        //订单信息 未释放完订单
        $res = $this->ecoModel
            ->where('uid',$uid)
            ->whereNull('end_time')
            ->orderBy('id','asc')
            ->first();
        if (!$res) {
            //所有订单已释放完/无订单
            return true;
        }
        //初始 总数 == 已释放数
        if ($res['creadit_amount'] <= $res['already_amount']) {
            //补漏释放时间
            $this->ecoModel->where('id',$res['id'])->update(['end_time'=>$this->set_time]);
            //递归
            return $this->minusOrder($uid,$num);
        }

        $after = $num + $res['already_amount'];//释放后 应总释放数
        if ($after < $res['creadit_amount']) {
            //总已释放数 < 总数
            $this->ecoModel->where('id',$res['id'])->increment('already_amount',$num);
            return true;
        }elseif ($after == $res['creadit_amount']) {
            //总已释放数 == 总数
            $this->ecoModel->where('id',$res['id'])->increment('already_amount',$num,['end_time'=>$this->set_time]);
            return true;
        }else{
            //总已释放数 > 总数 顺延
            $actNum = $res['creadit_amount'] - $res['already_amount'];//本订单此次 实释放数 = 总数-已释放数
            $this->ecoModel->where('id',$res['id'])->increment('already_amount',$actNum,['end_time'=>$this->set_time]);

            $num -= $actNum;
            //递归
            return $this->minusOrder($uid,$num);
        }


    }
}
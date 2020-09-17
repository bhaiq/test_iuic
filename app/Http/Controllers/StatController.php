<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BuyBack;
use App\Models\ExTip;
use App\Models\IuicInfo;
use App\Models\UserInfo;
use App\Models\UserWalletLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StatController extends Controller
{
    //矿池数据统计页面的数据
    public function infos(Request $request)
    {
        //IUIC总量
        $data['data']['all_iuic'] = $this->ce("0");
        //IUIC剩余总量(2.1亿 - IUIC剩余矿池 - 流通IUIC)
        $data['data']['sy_iuic'] = $this->ce("1");
        //流通IUIC数量
        $data['data']['lt_iuic'] = $this->ce("2");
        //IUIC矿池数量
        $data['data']['iuic_num'] = $this->ce("3");
        //IUIC矿池每天产出数量
        $data['data']['create_iuic'] = $this->ce("4");
        //交易买卖手续费分红总数
        $data['data']['bouns_num'] = $this->ce("6");
        //交易买卖手续费回购销毁IUIC总数
        $data['data']['buy_num'] = $this->ce("5");
        //交易买卖手续费累计手续费
        $data['data']['service_num'] = $this->ce("7");
        //币币手续费比例
        $tipBl = config('trade.tip_bl');
        $data['data']['marker'] = bcmul($tipBl,100,2)."%";
        $data['data']['taker'] = "0.00%";
        return $this->response($data);
    }

    public function logs(Request $request)
    {
        $logs = BuyBack::where('id','>',"0")->orderby('id','desc')->paginate(10);
        $list = [];
        foreach ($logs as $k => $v)
        {
            $list[$k]['num'] = $v['num'];
            $list[$k]['created_at'] = $v['created_at']->format('Y-m-d H:i:s');
            $list[$k]['exp'] = "回购销毁";
        }
        $data['list'] = $list;
        $data['current_page'] = $logs->currentPage();
        $data['all_page'] = $logs->lastPage();
        return $this->response(($data));

    }

    public function ce($id)
    {
        $id+=1;
        $values = IuicInfo::where('id',$id)->first();
        $iuic_kuagchi = UserInfo::sum(\DB::raw('(buy_total - release_total)'));
        $iuic_liutong = Account::where('coin_id',2)->sum(\DB::raw('(amount + amount_freeze)'));

        if($values['is_close'] == 0){
            return $values['value'];
        }else{
            if($id == 1){
                return number_format("210000000"); //IUIC总量
            }else if($id == 2){
                $shengy = 210000000 - $iuic_kuagchi - $iuic_liutong;
                return number_format($shengy,4); //IUIC剩余总量(2.1亿 - IUIC剩余矿池 - 流通IUIC)
            }else if($id == 3){
                return number_format($iuic_liutong,4); //流通IUIC数量
            }else if($id == 4){
                return number_format($iuic_kuagchi,4); //IUIC矿池数量
            }else if($id == 5){
                //IUIC矿池每天产出数量
                $today_release = UserWalletLog::where(function ($q){
                    $q->where('exp', '交易释放')->orwhere('exp', '灵活矿机释放')->orwhere('exp', '矿池静态释放');
                })->whereDate('created_at', now()->toDateString())->sum('num');
                $data['data'][4] = number_format($today_release,4);
            }else if($id == 6){
                //交易买卖手续费分红总数
                $fenhong_service = ExTip::where('type',0)->sum('bonus_num');
                return number_format($fenhong_service,4);
            }else if($id == 7) {
                //交易买卖手续费回购销毁IUIC总数
                $all_back = BuyBack::where('id','>',0)->sum('num');
                return number_format($all_back,4);
            }else if($id == 8) {
                //交易买卖手续费累计手续费
                $all_service = ExTip::where('type',0)->sum('num');
                return number_format($all_service,4);
            }
        }

    }
}

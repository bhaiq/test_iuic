<?php

namespace App\Http\Controllers;

use App\Models\EcologyConfigPub;
use App\Models\EcologyCreadit;
use App\Models\EcologyCreaditLog;
use App\Models\EcologyCreaditOrder;
use App\Models\KuangchiServiceCharge;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\UserWallet;
use App\Models\UserInfo;
use App\Models\KuangjiLinghuo;
use App\Models\EnergyOrder;
use App\Models\UsersImport;
use App\Models\SeniorAdmin;


//use Maatwebsite\Excel\Facades\Excel;

use Excel;

class ExcelController extends Controller
{
    // 清数据
    public function one(Request $request)
    {
        \Log::info('用户清资产开始');

        $data = $this->import("storage/exports/qing.xls");
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }
        return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $k=>$v){
                // dump((string)$v['A']);
                $new_account = (string)$v['A'];
                $user = User::with('user_info')->where('new_account', $new_account)->first();
                if(!$user){
                    // 无账号
                    $wu += 1;
                    array_push($wuArr, $new_account);
                    continue;
                }

                // 法币/币币 资产清空
                Account::where('uid', $user['id'])->update(['amount'=>'0','amount_freeze'=>'0']);
                // 能量资产清空
                UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_frozen_num'=>'0','consumer_num'=>'0','energy_lock_num'=>'0',]);
                // IUIC矿池 清空
                UserInfo::where('uid', $user['id'])->update(['buy_total'=>'0','release_total'=>'0']);
                // IUIC灵活矿机 清空
                KuangjiLinghuo::where('uid', $user['id'])->update(['num'=>'0']);

                $yes += 1;
                array_push($yesArr, $new_account);
            }
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];
        
        \Log::info('用户清资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }

    // 转换资产
    public function two(Request $request)
    {
        \Log::info('用户转换资产开始');

        $data = $this->import("storage/exports/dao.xls");
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }
        return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $k=>$v){
                $new_account = (string)$v['A'];
                $user = User::with('user_info')->where('id', $new_account)->first();
                if(!$user){
                    // 无账号
                    $wu += 1;
                    array_push($wuArr, $new_account);
                    continue;
                }

                $energy = UserWallet::where('uid', $user['id'])->first();//能量钱包
                $usdt = Account::where('uid', $user['id'])->where('coin_id', '1')->sum('amount');//法币/币币 可用USDT资产

                // 锁仓能量 1:1 转 IUIC矿池 bcmul($energy['energy_lock_num'], 1, 8);
                // 可用能量 1:1 转 IUIC矿池 bcmul($energy['energy_num'], 1, 8);
                // 冻结能量 1:1 转 IUIC矿池 bcmul($energy['energy_frozen_num'], 1, 8);
                // 可用USDT 1:7 转 IUIC矿池 bcmul($usdt, 7, 8);
                $num = bcmul(($energy['energy_lock_num'] * 1) + ($energy['energy_num'] * 1) + ($energy['energy_frozen_num'] * 1) + ($usdt * 7), 1, 8);

                // 锁仓能量 可用能量 冻结能量 清空
                UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_lock_num'=>'0','energy_frozen_num'=>'0']);
                // 法币/币币 可用USDT资产清空
                Account::where('uid', $user['id'])->where('coin_id', '1')->update(['amount'=>'0']);
                // IUIC矿池 增加
                if(UserInfo::where('uid', $user['id'])->exists()){
                    UserInfo::where('uid', $user['id'])->increment('buy_total', $num);
                }else{
                    $ulData = [
                        'uid' => $user->id,
                        'pid' => $user->pid,
                        'pid_path' => $user->pid_path,
                        'level' => 0,
                        'buy_total' => $num,
                        'buy_count' => 0,
                        'created_at' => now()->toDateTimeString(),
                    ];
                    UserInfo::create($ulData);
                }
                
                $yes += 1;
                array_push($yesArr, $new_account);
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户转换资产结束');
        return returnJson('1','处理成功',$returnArr);
    }

    // 整合资产1
    public function zheng1(Request $request)
    {
        \Log::info('整合资产开始');

        $data = $this->import("storage/exports/hebing1.xls");
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }

        return returnJson(0, '终止',$data);
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $ids = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $k=>$v){
                $new_account = (string)$v['A'];
                $user = User::with('user_info')->where('new_account', $new_account)->first();
                if(!$user){
                    // 无账号
                    $wu += 1;
                    array_push($wuArr, $new_account);
                    continue;
                }
                $yes += 1;
                array_push($yesArr, $new_account);
                array_push($ids, $user['id']);
            }

            $buy_total = UserInfo::whereIn('uid', $ids)->sum('buy_total');//矿池总数
            $release_total = UserInfo::whereIn('uid', $ids)->sum('release_total');//释放的总数
            $total = $buy_total - $release_total;//剩余数

            $heUid = User::with('user_info')->where('new_account', '13330039969')->value('id');

            // 增加整合用户IUIC矿池
            UserInfo::where('uid', $heUid)->increment('buy_total', $total);
            // 清空IUIC矿池
            UserInfo::whereIn('uid', $ids)->update(['buy_total'=>'0','release_total'=>'0']);
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'uid' => $heUid,
            'total' => $total,
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('整合资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }

    // 整合资产2
    public function zheng2(Request $request)
    {
        \Log::info('整合资产开始');

        $data = $this->import("storage/exports/hebing2.xls");
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }

        return returnJson(0, '终止',$data);
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $ids = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $k=>$v){
                $new_account = (string)$v['A'];
                $user = User::with('user_info')->where('new_account', $new_account)->first();
                if(!$user){
                    // 无账号
                    $wu += 1;
                    array_push($wuArr, $new_account);
                    continue;
                }
                $yes += 1;
                array_push($yesArr, $new_account);
                array_push($ids, $user['id']);
            }

            $buy_total = UserInfo::whereIn('uid', $ids)->sum('buy_total');//矿池总数
            $release_total = UserInfo::whereIn('uid', $ids)->sum('release_total');//释放的总数
            $total = $buy_total - $release_total;//剩余数

            $heUid = User::with('user_info')->where('new_account', '413514')->value('id');

            // 增加整合用户IUIC矿池
            UserInfo::where('uid', $heUid)->increment('buy_total', $total);
            // 清空IUIC矿池
            UserInfo::whereIn('uid', $ids)->update(['buy_total'=>'0','release_total'=>'0']);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'uid' => $heUid,
            'total' => $total,
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('整合资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }


    //Excel文件导入功能 By Laravel学院
    public function import($filePath=null){
        // $filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '清空资产表').'.xls';
        // $filePath = "storage/exports/qing.xls";
        // $filePath = "storage/exports/dao.xls";
        if(!$filePath){
            return [];
        }
        //载入文件
        $PHPExcel=Excel::load($filePath);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet=$PHPExcel->getSheet(0);
        //获取总列数
        $allColumn=$currentSheet->getHighestColumn();
        //获取总行数
        $allRow=$currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for($currentRow=1;$currentRow<=$allRow;$currentRow++){
            //从哪列开始，A表示第一列
            for($currentColumn='A';$currentColumn<=$allColumn;$currentColumn++){
                //数据坐标
                $address=$currentColumn.$currentRow;
                //读取到的数据，保存到数组$data中
                $data[$currentRow][$currentColumn]=$currentSheet->getCell($address)->getValue();
            }
        }
        // 去掉第exl表格中第一行(标题)
        unset($data[1]);
        // 清理空数组
        foreach($data as $k=>$v){
            if(empty($v)){
                unset($data[$k]);
            }
            foreach($v as $ktwo=>$vtwo){
                //第一列(ID)为空删除
                if($ktwo == 'A'){
                    if(empty($vtwo)){
                        unset($data[$k]);
                    }
                }
            }
        };
        // dump($data);

        return $data;
    }

     //Excel文件导出功能 By Laravel学院
    public function export(){
        $cellData = [
            ['学号','姓名','成绩'],
            ['10001','AAAAA','99'],
            ['10002','BBBBB','92'],
            ['10003','CCCCC','95'],
            ['10004','DDDDD','89'],
            ['10005','EEEEE','96'],
        ];
        Excel::create('学生成绩',function($excel) use ($cellData){
            $excel->sheet('score', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->export('xls');
    }
  
  	//整理负数方法
	public function zhenghe()
	{	
      	\Log::info('整理负数开始');
		$list=UserWallet::where('energy_frozen_num','<','0')->pluck('uid')->toArray();
		$num=count($list);
		// dd($num);
		\DB::beginTransaction();
		try {
			foreach($list as $v){
				$user=UserWallet::where('uid',$v)->first();
				$energy_num=$user->energy_num - abs($user->energy_frozen_num);
				if($energy_num < 0){
					$energy_num = 0;
				}
				$m=UserWallet::where('uid',$v)->update(['energy_num'=>$energy_num,'energy_frozen_num'=>0]);
				$n=EnergyOrder::where('uid',$v)->update(['status'=>"1"]);
			}
			
		  \DB::commit();
			
		} catch (\Exception $e) {
		    \DB::rollBack();
		    return returnJson(0, '操作异常');
		}
      	
		\Log::info('共整合了'.$num.'条数据', $list);
        \Log::info('整理负数结束');
        return returnJson("1", "共整合了".$num."条数据",$list);
	}


    // jl转换资产
    public function jlzhuan(Request $request)
    {
        \Log::info('用户转换资产开始');

        //$data = $this->import("storage/exports/jlzhuan.xlsx");

     $data = Excel::toArray(new UsersImport,storage_path('/exports/jlzhuan.xlsx'));
      //dd($data);
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }
        //return returnJson(0, '终止'.$data);
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
              foreach ($value as $k => $v){
               $new_account = $v[0];
                $user = User::with('user_info')->where('new_account',$new_account)->first();
                if(!$user){
                    // 无账号
                    $wu += 1;
                    array_push($wuArr, $new_account);
                    continue;
                }

                $energy = UserWallet::where('uid', $user['id'])->first();//能量钱包
                $usdt = Account::where('uid', $user['id'])->where('coin_id', '1')->sum('amount');//法币/币币 可用USDT资产

                // 锁仓能量 1:1 转 IUIC矿池 bcmul($energy['energy_lock_num'], 1, 8);
                // 可用能量 1:1 转 IUIC矿池 bcmul($energy['energy_num'], 1, 8);
                // 冻结能量 1:1 转 IUIC矿池 bcmul($energy['energy_frozen_num'], 1, 8);
                // 可用USDT 1:7 转 IUIC矿池 bcmul($usdt, 7, 8);
                $num = bcmul(($energy['energy_num'] * 1) + ($energy['energy_frozen_num'] * 1) + ($usdt * 7), 1, 8);

                // 锁仓能量 可用能量 冻结能量 清空
                UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_frozen_num'=>'0']);
                // 法币/币币 可用USDT资产清空
                Account::where('uid', $user['id'])->where('coin_id', '1')->update(['amount'=>'0']);
                // IUIC矿池 增加
                if(UserInfo::where('uid', $user['id'])->exists()){
                    UserInfo::where('uid', $user['id'])->increment('buy_total', $num);
                }else{
                    $ulData = [
                        'uid' => $user->id,
                        'pid' => $user->pid,
                        'pid_path' => $user->pid_path,
                        'level' => 0,
                        'buy_total' => $num,
                        'buy_count' => 0,
                        'created_at' => now()->toDateTimeString(),
                    ];
                    UserInfo::create($ulData);
                }
                
                $yes += 1;
                array_push($yesArr, $new_account);
            }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常'.$e);
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户转换资产结束');
        return returnJson('1','处理成功',$returnArr);
    }
  
  
    public function jlzhengs(Request $request)
    {
        \Log::info('整合资产开始');
		$jlname = $request->get('name');
        $data = Excel::toArray(new UsersImport,storage_path('/exports/'.$jlname.'.xlsx'));
        $count = count($data);
        if($count<1){
            return returnJson('0','未检测到有效数据');
        }

        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $ids = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
                foreach ($value as $k => $v) {
                    # code...
                
                    $new_account = $v[0];
                    $user = User::with('user_info')->where('new_account', $new_account)->first();
                    if(!$user){
                        // 无账号
                        $wu += 1;
                        array_push($wuArr, $new_account);
                        continue;
                    }
                    $yes += 1;
                    array_push($yesArr, $new_account);
                    array_push($ids, $user['id']);
                }
            }

            $buy_total = UserInfo::whereIn('uid', $ids)->sum('buy_total');//矿池总数
            $release_total = UserInfo::whereIn('uid', $ids)->sum('release_total');//释放的总数
            $total = $buy_total - $release_total;//剩余数

            $heUid = User::with('user_info')->where('new_account', $jlname)->value('id');

            // 增加整合用户IUIC矿池
            UserInfo::where('uid', $heUid)->increment('buy_total', $total);
            // 清空IUIC矿池
            UserInfo::whereIn('uid', $ids)->update(['buy_total'=>'0','release_total'=>'0']);
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'uid' => $heUid,
            'total' => $total,
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('整合资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }
//    public function jlqings(Request $request)
//    {
//        \Log::info('用户清资产开始');
//
//        $data = Excel::toArray(new UsersImport,storage_path('/exports/jlqing7_4.xlsx'));
//        $count = count($data);
//        if($count < 1){
//            return returnJson('0','未检测到有效数据');
//        }
//        // return returnJson(0, '终止');
//        \DB::beginTransaction();
//        try {
//            $yes = 0;//处理数量
//            $yesArr = [];
//            $wu = 0;//未处理数量
//            $wuArr = [];
//            foreach($data as $key=>$value){
//                foreach ($value as $k => $v) {
//                    # code...
//
//                    // dump((string)$v['A']);
//                    $new_account = $v[0];
//                    $user = User::with('user_info')->where('new_account', $new_account)->first();
//                    if(!$user){
//                        // 无账号
//                        $wu += 1;
//                        array_push($wuArr, $new_account);
//                        continue;
//                    }
//                    //可用能量和冻结能量清空
//                    UserWallet::where('uid', $user['id'])->update(['energy_frozen_num'=>0]);
//                    UserWallet::where('uid', $user['id'])->update(['energy_num'=>0]);
//
//                    //正在释放的能量报单改为释放完成
//                    EnergyOrder::where('uid', $user['id'])->update(['status' => 1]);
//
//                    // 能量矿池减少(500),可用能量增加(400)
////                    UserWallet::where('uid', $user['id'])->decrement('energy_frozen_num',500);
////                    UserWallet::where('uid', $user['id'])->increment('energy_num',400);
////                    if($user['new_account'] == "zwz001"){
////                        UserWallet::where('uid', $user['id'])->decrement('energy_frozen_num',500);
////                        UserWallet::where('uid', $user['id'])->increment('energy_num',400);
////                    }
//                    // IUIC矿池 清空
////                    UserInfo::where('uid', $user['id'])->update(['buy_total'=>'0','release_total'=>'0']);
//
//                    $yes += 1;
//                    array_push($yesArr, $new_account);
//                }
//            }
//
//            \DB::commit();
//        } catch (\Exception $e) {
//            \DB::rollBack();
//            return returnJson(0, '操作异常');
//        }
//
//        $returnArr = [
//            'yes' => [
//                'count' => $yes,
//                'yesArr' => $yesArr
//            ],
//            'wu' => [
//                'count' => $wu,
//                'wuArr' => $wuArr
//            ]
//        ];
//
//        \Log::info('用户清资产结束', $returnArr);
//        return returnJson('1','处理成功',$returnArr);
//    }
  
  
  	//将高级管理将的用户平移到社群奖
	public function pyHighComm()
	{
		
//      	\Log::info('将高级管理将的用户平移到社群奖 开始');
      	\Log::info('将高级管理将的用户平移到运营中心奖 开始');
      	$list=SeniorAdmin::where('status',1)->get();
      	//dd($list);
      
      	\DB::beginTransaction();
      	try {
        
        	$nn="";
			foreach($list as $v){
      		User::where('id',$v->uid)->update(['star_community'=>$v->type]);
          	SeniorAdmin::where('id',$v->id)->update(['type'=>0]);
          	
//          	$nn.='将用户id为'.$v->uid.'高级管理奖的用户平移为社群奖';
          	$nn.='将用户id为'.$v->uid.'高级管理奖的用户平移为运营中心奖';
      	}

        \DB::commit();
      } catch (\Exception $e) {
		
        \DB::rollBack();
        return returnJson(0, '操作异常');
      }
      
//      \Log::info('将高级管理将的用户平移到社群奖 结束');
      \Log::info('将高级管理将的用户平移到运营中心奖 结束');
      return returnJson('1','处理成功');
      		
	}

	//jl清资产 把这些账号里的所有能量矿池、USDT全部清零
    public function jlqing(Request $request)
    {
        \Log::info('用户清资产开始');

        $data = Excel::toArray(new UsersImport,storage_path('/exports/11_18qing.xls'));
        $count = count($data);
        if($count < 1){
            return returnJson('0','未检测到有效数据');
        }
        // return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
                foreach ($value as $k => $v) {
                    # code...

                    // dump((string)$v['A']);
                    $new_account = $v[0];
                    $user = User::with('user_info')->where('new_account', $new_account)->first();
                    if(!$user){
                        // 无账号
                        $wu += 1;
                        array_push($wuArr, $new_account);
                        continue;
                    }
                    // usdt法币/币币 资产清空
//                    Account::where('uid', $user['id'])->where('coin_id',1)->update(['amount'=>'0','amount_freeze'=>'0']);
                    // 能量资产清空
//                    UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_frozen_num'=>'0','consumer_num'=>'0','energy_lock_num'=>'0',]);
                    //能量订单处理
//                    EnergyOrder::where('uid',$user['id'])->update(['status'=>"1"]);
                    // IUIC矿池 清空
                    // UserInfo::where('uid', $user['id'])->update(['buy_total'=>'0','release_total'=>'0']);
                    // IUIC灵活矿机 清空
                     KuangjiLinghuo::where('uid', $user['id'])->update(['num'=>'0']);

                    $yes += 1;
                    array_push($yesArr, $new_account);
                }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常');
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户清资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }


    //账户能量资产与全部USDT资产全部清零，然后每个账户按表内数补上IUIC矿池
//    public function jlqing(Request $request)
//    {
//        \Log::info('用户处理资产开始');
//
//        $data = Excel::toArray(new UsersImport,storage_path('/exports/2020-9-20qing.xlsx'));
//        $count = count($data);
//        if($count < 1){
//            return returnJson('0','未检测到有效数据');
//        }
//        // return returnJson(0, '终止');
//        \DB::beginTransaction();
//        try {
//            $yes = 0;//处理数量
//            $yesArr = [];
//            $wu = 0;//未处理数量
//            $wuArr = [];
//            foreach($data as $key=>$value){
//                foreach ($value as $k => $v) {
//                    # code...
//
////                     dump((string)$v[0]);
////                     dump((string)$v[1]);
//                    $new_account = $v[0];
//                    $user = User::with('user_info')->where('new_account', $new_account)->first();
//                    if(!$user){
//                        // 无账号
//                        $wu += 1;
//                        array_push($wuArr, $new_account);
//                        continue;
//                    }
////                    // USDT法币/币币 资产清空
//                    Account::where('uid', $user['id'])->where('coin_id',1)->update(['amount'=>'0','amount_freeze'=>'0']);
//                    // 能量资产清空
//                    UserWallet::where('uid', $user['id'])->update(['energy_num'=>'0','energy_frozen_num'=>'0','consumer_num'=>'0','energy_lock_num'=>'0']);
//                    //能量订单处理
//                    EnergyOrder::where('uid',$user['id'])->update(['status'=>"1"]);
////                  // 补上IUIC矿池
////                    UserInfo::where('uid', $user['id'])->increment('buy_total',$v[1]);
////                    // IUIC灵活矿机 清空
////                    KuangjiLinghuo::where('uid', $user['id'])->update(['num'=>'0']);
//
//                    $yes += 1;
//                    array_push($yesArr, $new_account);
//                }
//            }
//
//            \DB::commit();
//        } catch (\Exception $e) {
//            \DB::rollBack();
//            return returnJson(0, '操作异常'.$e);
//        }
//
//        $returnArr = [
//            'yes' => [
//                'count' => $yes,
//                'yesArr' => $yesArr
//            ],
//            'wu' => [
//                'count' => $wu,
//                'wuArr' => $wuArr
//            ]
//        ];
//
//        \Log::info('用户清资产结束', $returnArr);
//        return returnJson('1','处理成功',$returnArr);
//    }


    public function kuang_service_charge(Request $request)
    {
        \Log::info('用户处理资产开始');

        $data = Excel::toArray(new UsersImport,storage_path('/exports/service.xls'));
        $count = count($data);
        if($count < 1){
            return returnJson('0','未检测到有效数据');
        }
        // return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
                foreach ($value as $k => $v) {
                    # code...

//                     dump((string)$v[0]);
//                     dump((string)$v[1]);
                    $new_account = $v[0];
                    $user = User::with('user_info')->where('new_account', $new_account)->first();
                    if(!$user){
                        // 无账号
                        $wu += 1;
                        array_push($wuArr, $new_account);
                        continue;
                    }
//
//                  // 扣除IUIC矿池(如果剩余的不够扣,就把剩余的扣完,并加记录)
                    $userinfo = UserInfo::where('uid',$user['id'])->first();
                    $user_buy_total = $userinfo->buy_total;
                    $user_release_tatal = $userinfo->release_total;
                    $shenyu = bcsub($user_buy_total,$user_release_tatal,8);
                    if($shenyu >= $v[1]){
                        $kou = $v[1];
                    }else{
                        $kou = $shenyu;
                    }
                    if($kou > 0){
                        UserInfo::where('uid', $user['id'])->increment('release_total',$kou);
                        $service_charge = new KuangchiServiceCharge();
                        $service_charge->uid = $user['id'];
                        $service_charge->all_num = $kou;
                        $service_charge->save();
                    }


                    $yes += 1;
                    array_push($yesArr, $new_account);
                }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常'.$e);
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户清资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }

    public function reback(Request $request)
    {
        \Log::info('用户处理资产开始');

        $data = Excel::toArray(new UsersImport,storage_path('/exports/user.xlsx'));
        $count = count($data);
        if($count < 1){
            return returnJson('0','未检测到有效数据');
        }
        // return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
                foreach ($value as $k => $v) {
                    # code...

//                     dump((string)$v[0]);
//                     dump((string)$v[1]);
                    $new_account = $v[0];
                    $user = User::where('new_account', $new_account)->first();
                    if(!$user){
                        // 无账号
                        $wu += 1;
                        array_push($wuArr, $new_account);
                        continue;
                    }
                    User::where('id',$user->id)->update(['pid'=>$v[1],'pid_path'=>$v[2]]);

                    $yes += 1;
                    array_push($yesArr, $new_account);
                }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常'.$e);
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户清资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }


    //加分享奖
    public function share_reward(Request $request)
    {
        \Log::info('用户处理资产开始');

        $data = Excel::toArray(new UsersImport,storage_path('/exports/iuic.xlsx'));
        $count = count($data);
        if($count < 1){
            return returnJson('0','未检测到有效数据');
        }
        // return returnJson(0, '终止');
        \DB::beginTransaction();
        try {
            $yes = 0;//处理数量
            $yesArr = [];
            $wu = 0;//未处理数量
            $wuArr = [];
            foreach($data as $key=>$value){
                foreach ($value as $k => $v) {
                    # code...

//                     dump((string)$v[0]);
//                     dump((string)$v[1]);
                    $new_account = $v[0];
                    $user = User::where('new_account', $new_account)->first();
                    if(!$user){
                        // 无账号
                        $wu += 1;
                        array_push($wuArr, $new_account);
                        continue;
                    }
//
////                  // 扣除IUIC矿池(如果剩余的不够扣,就把剩余的扣完,并加记录)
//                    $userinfo = UserInfo::where('uid',$user['id'])->first();
//                    $user_buy_total = $userinfo->buy_total;
//                    $user_release_tatal = $userinfo->release_total;
//                    $shenyu = bcsub($user_buy_total,$user_release_tatal,8);
//                    if($shenyu >= $v[1]){
//                        $kou = $v[1];
//                    }else{
//                        $kou = $shenyu;
//                    }
//                    if($kou > 0){
//                        UserInfo::where('uid', $user['id'])->increment('release_total',$kou);
//                        $service_charge = new KuangchiServiceCharge();
//                        $service_charge->uid = $user['id'];
//                        $service_charge->all_num = $kou;
//                        $service_charge->save();
//                    }
//                    $wallet = New EcologyCreadit();
                    $this->ecology_share_rewards($user['id'],$v[1]);


                    $yes += 1;
                    array_push($yesArr, $new_account);
                }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return returnJson(0, '操作异常'.$e);
        }

        $returnArr = [
            'yes' => [
                'count' => $yes,
                'yesArr' => $yesArr
            ],
            'wu' => [
                'count' => $wu,
                'wuArr' => $wuArr
            ]
        ];

        \Log::info('用户清资产结束', $returnArr);
        return returnJson('1','处理成功',$returnArr);
    }

    public function ecology_share_rewards($uid,$num)
    {
        \Log::info("-------------生态2分享奖开始------------");
        $pid = $uid;
        //判断上级是否有钱包
        $p_wallet = EcologyCreadit::where('uid',$pid)->first();
        if(empty($p_wallet) || $p_wallet->amount_freeze <= 0){
            \Log::info($pid."上级没有开通积分钱包或冻结积分为0");
            return;
        }
        //应释放数
        $reward = $num * EcologyConfigPub::where('id',1)->value('rate_direct');
        \DB::beginTransaction();
        try{
            if($p_wallet->amount_freeze > $reward){
                $true_num = $reward;
                EcologyCreadit::a_o_m($pid,$true_num,'2','3','生态2分享奖',2);
                EcologyCreadit::a_o_m($pid,$true_num,'1','3','生态2分享奖',1);
            }else{
                if($p_wallet->amount_freeze > 0){
                    $true_num = $p_wallet->amount_freeze;
                    EcologyCreadit::a_o_m($pid,$true_num,'2','3','生态2分享奖',2);
                    EcologyCreadit::a_o_m($pid,$true_num,'1','3','生态2分享奖',1);
                    //插入释放完成时间
                    User::where('id',$pid)->update(['ecology_lv_close'=>1]);
                    EcologyCreadit::where('uid',$pid)->update(['release_end_time'=>date('Y-m-d H:i')]);
                }

            }
            //从最早的订单开始释放
//            $lists = EcologyCreaditOrder::where('uid',$pid)
            $lists = EcologyCreaditOrder::where('uid',$pid)
                ->whereNull('end_time')
                ->orderby('id')
                ->get();
            $all = 0;
            foreach ($lists as $k => $v){
                $all+=$v->creadit_amount - $v->already_amount;
                //大于当前说明当前可以释放完,继续释放下一单
                if($true_num >= $all){
                    //订单状态更改,改为已释放完成,插入完成时间
                    EcologyCreaditOrder::where('id',$v->id)->update(['already_amount'=>$v->creadit_amount,
                        'end_time'=>date('Y-m-d H:i:s')]);
                }else{
                    //小于当前此单释放不完,改为释放部分,不插入完成时间,循环终止
                    $now_amount = $v->creadit_amount - $v->already_amount;
                    $sheng = $all - $now_amount;
                    $sheng = $true_num - $sheng;
                    EcologyCreaditOrder::where('id',$v->id)->increment('already_amount',$sheng);
                    break;
                }
            }
            \DB::commit();
        }catch (\Exception $exception){
            \DB::rollBack();
            \Log::info("错误".$exception->getMessage());
            return;
        }
        \Log::info("-------------生态2分享奖结束------------");
    }
}

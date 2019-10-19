<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 11:40
 */

namespace App\Http\Controllers;

use App\Models\MallGood;
use App\Models\MallIncomel;
use App\Models\MallOrder;
use App\Models\MallStore;
use App\Models\MxCity;
use App\Services\Service;
use Illuminate\Http\Request;

class MallStoreController extends Controller
{

    // 店铺首页
    public function index(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $ms = MallStore::where(['uid' => Service::auth()->getUser()->id, 'status' => 1])->first();
        if(!$ms){
            $this->responseError('数据有误');
        }

        $newAddress = '';
        $i = 0;

        $arr = explode(',', $ms->address);
        foreach ($arr as $v) {
            if ($i > 0) {
                $newAddress .= $v;
            }
            $i++;
        }

        $result = [
            'id' => $ms->id,
            'pic' => $ms->pic,
            'name' => $ms->name,
            'address' => $newAddress,
            'bg_img' => $ms->bg_img,
            'goods_count' => MallGood::where(['store_id' => $ms->id, 'status' => 1])->count(),
            'order_count' => MallOrder::where('store_id', $ms->id)->count(),
            'income_count' => bcmul(MallIncomel::where('store_id', $ms->id)->sum('sj_num'), 1, 4),
        ];

        return $this->response($result);

    }

    // 店铺申请
    public function apply(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'   => 'required',
            'mobile'  => 'required',
            'address_id' => 'required',
            'address_info' => 'required',
            'license_img' => 'required'
        ], [
            'name.required' => '名称不能为空',
            'mobile.required' => '手机不能为空',
            'address_id.required' => '地址信息不能为空',
            'address_info.required' => '地址详细不能为空',
            'license_img.required' =>'营业执照照片不能为空',
        ]);

        // 判断用户是否已经申请
        if(MallStore::where('uid', Service::auth()->getUser()->id)->where('status', '!=', 9)->exists()){
            $this->responseError('用户已申请或者已经是商家了');
        }

        // 获取城市信息
        $mc = MxCity::where(['id' => $request->get('address_id'), 'level_type' => 3])->first();
        if(!$mc){
            $this->responseError('店铺地址信息有误');
        }

        $msData = [
            'uid' => Service::auth()->getUser()->id,
            'name' => $request->get('name'),
            'mobile' => $request->get('mobile'),
            'address' => $mc->merger_name,
            'address_info' => $request->get('address_info'),
            'status' => 0,
            'license_img' => $request->get('license_img'),
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 先删除原来的数据
            MallStore::where('uid', Service::auth()->getUser()->id)->delete();

            // 商店表新增
            MallStore::create($msData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('店铺申请异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');


    }

    // 店铺通知
    public function notice(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id'   => 'required',
        ], [
            'store_id.required' => '店铺信息不能为空',
        ]);

        $res = MallOrder::with(['goods', 'user'])
            ->where('store_id', $request->get('store_id'))
            ->latest('id')
            ->paginate($request->get('per_page', 10))
            ->toArray();

        foreach ($res['data'] as $k => $v){

            $arr = [];

            $arr['nickname'] = $v['user']['nickname'];
            $arr['goods_id'] = $v['goods_id'];
            $arr['goods_img'] = $v['goods']['goods_img'];
            $arr['goods_info'] = $v['goods']['goods_info'];
            $arr['created_at'] = date('Y/m/d', strtotime($v['created_at']));

            $res['data'][$k] = $arr;

        }

        return $this->response($res);

    }

    // 店铺设置
    public function edit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id'   => 'required',
        ], [
            'store_id.required' => '店铺信息不能为空',
        ]);

        // 验证店铺信息是否正确
        $ms = MallStore::where('status', 1)->find($request->get('store_id'));
        if(!$ms){
            $this->responseError('店铺信息有误');
        }

        if($request->has('pic') && !empty($request->get('pic'))){
            $ms->pic = $request->get('pic');
        }

        if($request->has('name') && !empty($request->get('name'))){
            $ms->name = $request->get('name');
        }

        if($request->has('bg_img') && !empty($request->get('bg_img'))){
            $ms->bg_img = $request->get('bg_img');
        }

        $ms->save();

        $this->responseSuccess('操作成功');

    }

    // 店铺商品信息
    public function goods(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id'   => 'required',
            'type' => 'required|in:1,2,3',
        ], [
            'store_id.required' => '店铺信息不能为空',
            'type.required' => '类型不能为空',
            'type.in' => '类型不正确',
        ]);

        $store = MallStore::find($request->get('store_id'));
        if(!$store){
            $this->responseError('数据有误');
        }

        $result['store'] = [
            'pic' => $store->pic,
            'bg_img' => $store->bg_img,
            'name' => $store->name,
            'address' => $store->address,
            'address_info' => $store->address_info,
        ];

        if($request->get('type') == 2){
            $res = MallGood::where('store_id', $store->id)->where('status', 1)->latest('top')->latest('id')->paginate($request->get('per_page', 10));
        }else if($request->get('type') == 3){
            $res = MallGood::where('store_id', $store->id)->where('status', 1)->latest('top')->latest('sale_num')->paginate($request->get('per_page', 10));
        }else{
            $res = MallGood::where('store_id', $store->id)->where('status', 1)->latest('top')->paginate($request->get('per_page', 10));
        }

        return $this->response(array_merge($res->toArray(), $result));

    }

}
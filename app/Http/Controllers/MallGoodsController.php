<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 15:39
 */

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Coin;
use App\Models\ExTeam;
use App\Models\MallAddress;
use App\Models\MallCategory;
use App\Models\MallGood;
use App\Models\MallOrder;
use App\Models\MallStore;
use App\Services\Service;
use Illuminate\Http\Request;

class MallGoodsController extends Controller
{

    // 发布商品
    public function add(Request $request)
    {

        \Log::info('接收参数', $request->all());

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_img' => 'required',
            'goods_info' => 'required|max:200',
            'goods_name' => 'required',
            'goods_price' => 'required|numeric|regex:/^[0-9]+(.[0-9]{1,2})?$/',
            'goods_cost' => 'required|numeric|regex:/^[0-9]+(.[0-9]{1,2})?$/',
            'category_id' => 'required',
            'store_id' => 'required',
        ], [
            'goods_img.required' => '商品图片不能为空',
            'goods_info.required' => '商品说明不能为空',
            'goods_info.max' => '商品说明不能超过200个字符',
            'goods_name.required' => '商品名称不能为空',
            'goods_price.required' => '商品价格不能为空',
            'goods_price.numeric' => '商品价格必须是数字',
            'goods_price.regex' => '商品价格只能保留两位小数',
            'goods_cost.required' => '商品成本不能为空',
            'goods_cost.numeric' => '商品成本必须是数字',
            'goods_cost.regex' => '商品成本只能保留两位小数',
            'category_id.required' => '商品类别不能为空',
            'store_id.required' => '店铺信息不能为空',
        ]);

        // 验证图片数量是否超标
        if (is_array($request->get('goods_img'))) {
            if (count($request->get('goods_img')) > 4) {
                $this->responseError('图片数量不能超过4张');
            }
            $imgArr = $request->get('goods_img');
        } else {
            $imgArr = explode(',', $request->get('goods_img'));
            if (count($imgArr) > 4) {
                $this->responseError('图片数量不能超过4张');
            }
        }

        if($request->get('goods_cost') > $request->get('goods_price')){
            $this->responseError('成本价不能大于售价');
        }

        // 验证商品类别是否有问题
        if (!MallCategory::where('id', $request->get('category_id'))->exists()) {
            $this->responseError('商品类别有误');
        }

        // 验证店铺信息是否正确
        if (!MallStore::where('id', $request->get('store_id'))->exists()) {
            $this->responseError('店铺信息有误');
        }

        // 计算返利
        $orePool = MallGood::getRebate($request->get('goods_price'));

        $mgData = [
            'uid' => Service::auth()->getUser()->id,
            'store_id' => $request->get('store_id'),
            'goods_name' => $request->get('goods_name'),
            'goods_price' => $request->get('goods_price'),
            'goods_cost' => $request->get('goods_cost'),
            'goods_img' => implode(',', $imgArr),
            'goods_info' => $request->get('goods_info'),
            'category_id' => $request->get('category_id'),
            'ore_pool' => $orePool,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            MallGood::create($mgData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('发布商品异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 获取商品类别
    public function category()
    {

        Service::auth()->isLoginOrFail();

        $res = MallCategory::get()->toArray();

        return $this->response($res);

    }

    // 获取商品返利
    public function rebate(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'num' => 'required',
        ], [
            'num.required' => '数量不能为空',
        ]);

        $num = MallGood::getRebate($request->get('num'));

        return $this->response(['num' => $num]);
    }

    // 获取商品列表
    public function index(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'store_id' => 'required',
            'status' => 'required|in:1,2,3'
        ], [
            'store_id.required' => '店铺信息不能为空',
            'status.required' => '状态不能为空',
            'status.in' => '状态信息不正确',
        ]);

        $status = 1;
        if ($request->get('status') == 2) {
            $status = 0;
        }

        if($request->get('status') != 3){

            // 获取该店铺的在售中商品
            $res = MallGood::select('id', 'goods_name', 'goods_price', 'goods_cost', 'goods_img', 'sale_num', 'ore_pool', 'goods_info', 'category_id')
                ->where(['store_id' => $request->get('store_id'), 'status' => $status, 'is_affirm' => 1])
                ->latest('top')
                ->paginate($request->get('per_page', 10));

            $result = $res->toArray();

        }else{

            // 获取该店铺申请中的商品
            $res = MallGood::select('id', 'goods_name', 'goods_price', 'goods_cost', 'goods_img', 'sale_num', 'ore_pool', 'goods_info', 'category_id')
                ->where(['store_id' => $request->get('store_id'), 'is_affirm' => 0])
                ->latest('top')
                ->paginate($request->get('per_page', 10));

            $result = $res->toArray();

        }

        foreach ($result['data'] as $k => $v){
            $result['data'][$k]['category_name'] = MallCategory::find($v['category_id'])->name ?? '';
        }

        return $this->response($result);

    }

    // 编辑商品
    public function edit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required',
            'goods_img' => 'required',
            'goods_info' => 'required',
            'goods_name' => 'required',
            'category_id' => 'required',
        ], [
            'goods_id.required' => '商品信息不能为空',
            'goods_img.required' => '商品图片不能为空',
            'goods_info.required' => '商品说明不能为空',
            'goods_name.required' => '商品名称不能为空',
            'category_id.required' => '商品类别不能为空',
        ]);

        // 验证商品是否有问题
        if (!MallGood::where('id', $request->get('goods_id'))->exists()) {
            $this->responseError('商品信息有误');
        }

        // 验证图片数量是否超标
        if (is_array($request->get('goods_img'))) {
            if(count($request->get('goods_img')) > 4){
                $this->responseError('图片数量不能超过4张');
            }
            $imgArr = $request->get('goods_img');
        } else {
            $imgArr = explode(',', $request->get('goods_img'));
            if (count($imgArr) > 4) {
                $this->responseError('图片数量不能超过4张');
            }
        }

        // 验证商品类别是否有问题
        if (!MallCategory::where('id', $request->get('category_id'))->exists()) {
            $this->responseError('商品类别有误');
        }

        // 计算返利
        $orePool = MallGood::getRebate($request->get('goods_price'));

        $mgData = [
            'goods_name' => $request->get('goods_name'),
            'goods_img' => implode(',', $imgArr),
            'goods_info' => $request->get('goods_info'),
            'ore_pool' => $orePool,
            'category_id' => $request->get('category_id'),
        ];

        // 修改商品
        \DB::beginTransaction();
        try {

            MallGood::where('id', $request->get('goods_id'))->update($mgData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('编辑商品异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 上下架或删除商品
    public function operate(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required',
            'type' => 'required|in:1,2,3',
        ], [
            'goods_id.required' => '商品信息不能为空',
            'type.required' => '类型不能为空',
            'type.in' => '类型不正确',
        ]);

        // 获取商品信息是否正确
        $goods = MallGood::where('status', '!=', 9)->find($request->get('goods_id'));
        if (!$goods || !MallStore::where(['uid' => Service::auth()->getUser()->id, 'id' => $goods->store_id])->exists()) {
            $this->responseError('数据有误');
        }

        $status = $goods->status;

        if ($request->get('type') == 1) {
            $status = 1;
        } else if ($request->get('type') == 2) {
            $status = 0;
        } else if ($request->get('type') == 3) {
            $status = 9;
        } else {
            $this->responseError('数据有误');
        }

        // 更新订单状态
        \DB::beginTransaction();
        try {

            $goods->status = $status;
            $goods->save();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('操作商品异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 查看商品详情
    public function info(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required',
        ], [
            'goods_id.required' => '商品信息不能为空',
        ]);

        $goods = MallGood::with('store')->where(['status' => 1, 'is_affirm' => 1])->find($request->get('goods_id'));
        if (!$goods) {
            $this->responseError('商品已下架或删除');
        }

        $data = $goods->toArray();

        $data['store_mobile'] = $goods->store->mobile ?? 0;

        return $this->response($data);

    }

    // 生成商品订单
    public function order(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required',
        ], [
            'goods_id.required' => '商品信息不能为空',
        ]);

        // 获取商品信息
        $goods = MallGood::with('store')->where('status', 1)->find($request->get('goods_id'));
        if (!$goods) {
            $this->responseError('数据有误');
        }

        $res = [
            'address' => null,
            'store' => null,
            'goods' => null,
        ];

        // 获取用户地址信息
        $address = MallAddress::where('uid', Service::auth()->getUser()->id)->latest('is_default')->first();
        if ($address) {

            $newAddress = '';
            $i = 0;

            $arr = explode(',', $address->address);
            foreach ($arr as $v) {
                if ($i > 0) {
                    $newAddress .= $v;
                }
                $i++;
            }

            $res['address'] = [
                'address_id' => $address->id,
                'name' => $address->name,
                'mobile' => $address->mobile,
                'address' => $newAddress,
                'address_info' => $address->address_info,
            ];

        }

        // 收集商店信息
        if (!empty($goods->store)) {

            $res['store'] = [
                'store_id' => $goods->store->id,
                'store_name' => $goods->store->name,
            ];

        }

        // 获取IUIC的价格
        $exTeam = ExTeam::getCurPrice(1);
        $goodsPriceIuic = bcdiv(bcmul($exTeam['rate'], $goods->goods_price, 8), $exTeam['price_cny'], 4);

        $goodsPriceArr = [
            [
                'id' => 1,
                'name' => 'USDT',
                'num' => $goods->goods_price,
            ],
            [
                'id' => 2,
                'name' => 'IUIC',
                'num' => $goodsPriceIuic
            ],
        ];

        $res['goods'] = [
            'goods_id' => $goods->id,
            'goods_name' => $goods->goods_name,
            'goods_img' => $goods->goods_img,
            'goods_info' => $goods->goods_info,
            'goods_price' => $goods->goods_price,
            'goods_price_arr' => $goodsPriceArr,
            'goods_cost' => $goods->goods_cost,
            'ore_pool' => $goods->ore_pool,
        ];

        return $this->response($res);

    }

    // 商品购买提交
    public function buy(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'goods_id' => 'required',
            'address_id' => 'required',
            'num' => 'required|integer',
            'paypass' => 'required',
            'coin_id' => 'required',
        ], [
            'goods_id.required' => '商品信息不能为空',
            'address_id.required' => '地址信息不能为空',
            'num.required' => '数量不能为空',
            'num.integer' => '数量必须是整数',
            'coin_id.required' => '币种信息不能不为空',
        ]);

        // 验证商品信息是否正确
        $goods = MallGood::where('status', 1)->find($request->get('goods_id'));
        if (!$goods) {
            $this->responseError('商品信息有误');
        }

        // 验证地址信息是否有误
        $address = MallAddress::where('uid', Service::auth()->getUser()->id)->find($request->get('address_id'));
        if (!$address) {
            $this->responseError('地址信息有误');
        }

        // 验证币种信息是否整
        $coin = Coin::find($request->get('coin_id'));
        if(!$coin){
            $this->responseError('地址信息有误');
        }

        // 验证二级密码
        Service::auth()->isTransactionPasswordYesOrFail($request->get('paypass'));

        // 获取那个USDT的币种ID
        $coin = Coin::getCoinByName($coin->name);
        $coinAccount = Service::auth()->account($coin->id, Account::TYPE_LC);

        if($coin->name == 'USDT'){
            $totalPrice = bcmul($goods->goods_price, $request->get('num'), 8);
        }else{

            // 获取IUIC的价格
            $exTeam = ExTeam::getCurPrice(1);

            // 获取IUIC的数量
            $totalPrice = bcdiv(bcmul($exTeam['rate'], bcmul($goods->goods_price, $request->get('num'), 8), 8), $exTeam['price_cny'], 8);
        }

        // 判断用户余额是否充足
        if ($coinAccount->amount < $totalPrice) {
            $this->responseError('用户余额不足');
        }

        $moData = [
            'uid' => Service::auth()->getUser()->id,
            'store_id' => $goods->store_id,
            'order_sn' => MallOrder::getOrderSn(),
            'goods_id' => $goods->id,
            'num' => $request->get('num'),
            'goods_name' => $goods->goods_name,
            'goods_price' => $goods->goods_price,
            'goods_cost' => $goods->goods_cost,
            'goods_img' => implode(',', $goods->goods_img),
            'goods_info' => $goods->goods_info,
            'ore_pool' => $goods->ore_pool,
            'to_name' => $address->name,
            'to_mobile' => $address->mobile,
            'to_address' => $address->address,
            'to_address_info' => $address->address_info,
            'pay_coin_id' => $coin->id,
            'pay_num' => $totalPrice,
            'created_at' => now()->toDateTimeString(),
        ];

        \DB::beginTransaction();
        try {

            MallOrder::create($moData);

            // 商品销量加
            MallGood::where('id', $goods->id)->increment('sale_num');

            // 用户余额减少
            Account::reduceAmount(Service::auth()->getUser()->id, $coin->id, $totalPrice);

            // 余额日志增加
            AccountLog::addLog(Service::auth()->getUser()->id, $coin->id, $totalPrice, 19, 0, 1, '购买商品');

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('多店铺商城订单生成异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 获取支付时可选择的币种信息
    public function coin()
    {
        return Coin::get(['id', 'name'])->toArray();
    }

}
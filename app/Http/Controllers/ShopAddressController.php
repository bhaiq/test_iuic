<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 10:03
 */

namespace App\Http\Controllers;

use App\Models\ShopAddress;
use App\Services\Service;
use Illuminate\Http\Request;

class ShopAddressController extends Controller
{

    // 地址列表
    public function index()
    {

        Service::auth()->isLoginOrFail();

        $sa = ShopAddress::where('uid', Service::auth()->getUser()->id)->latest('is_default')->get(['id', 'to_name', 'to_mobile', 'to_address', 'is_default', 'created_at']);
        return $this->response($sa->toArray());

    }

    // 新增地址
    public function store(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'   => 'required',
            'mobile'  => 'required',
            'address' => 'required'
        ], [
            'name.required' => '名称不能为空',
            'mobile.required' => '手机不能为空',
            'address.required' => '地址不能为空',
        ]);

        $saData = [
            'uid' => Service::auth()->getUser()->id,
            'to_name' => $request->get('name'),
            'to_mobile' => $request->get('mobile'),
            'to_address' => $request->get('address'),
            'created_at' => now()->toDateTimeString(),
        ];

        if($request->get('is_default', 0) == 1){

            ShopAddress::where('uid', Service::auth()->getUser()->id)->update(['is_default' => 0]);

            $saData['is_default'] = $request->get('is_default');
        }

        \DB::beginTransaction();
        try {

            ShopAddress::create($saData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址新增异常');

            $this->responseError('新增异常');

        }

        return $this->responseSuccess('操作成功');

    }

    // 修改地址
    public function update(Request $request, $id)
    {
        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'     => 'required',
            'mobile'     => 'required',
            'address' => 'required'
        ], [
            'name.required' => '名称不能为空',
            'mobile.required' => '手机不能为空',
            'address.required' => '地址不能为空',
        ]);

        // 验证数据是否正确
        if(!ShopAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->exists()){
            $this->responseError('数据有误');
        }

        $saData = [
            'to_name' => $request->get('name'),
            'to_mobile' => $request->get('mobile'),
            'to_address' => $request->get('address'),
        ];

        if($request->get('is_default', 0) == 1){

            ShopAddress::where('uid', Service::auth()->getUser()->id)->update(['is_default' => 0]);

            $saData['is_default'] = 1;
        }

        \DB::beginTransaction();
        try {

            ShopAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->update($saData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址修改异常');

            $this->responseError('修改异常');

        }

        $this->responseSuccess('操作成功');

    }

    // 删除地址
    public function destroy($id)
    {

        Service::auth()->isLoginOrFail();

        // 验证数据是否正确
        if(!ShopAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->exists()){
            $this->responseError('数据有误');
        }


        \DB::beginTransaction();
        try {

            ShopAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->delete();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址删除异常');

            $this->responseError('删除异常');

        }

        $this->responseSuccess('操作成功');

    }


}
<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/4
 * Time: 10:03
 */

namespace App\Http\Controllers;

use App\Models\MallAddress;
use App\Models\MxCity;
use App\Services\Service;
use Illuminate\Http\Request;

class ShopAddressController extends Controller
{

    // 地址列表
    public function index()
    {

        Service::auth()->isLoginOrFail();

        $sa = MallAddress::where('uid', Service::auth()->getUser()->id)->latest('is_default')->get(['id', 'name', 'mobile', 'address', 'address_info', 'is_default', 'created_at'])->toArray();

        $result = [];
        foreach ($sa as $k => $v){

            $newAddress = '';
            $i = 0;

            $arr = explode(',', $v['address']);
            foreach ($arr as $val) {
                if ($i > 0) {
                    $newAddress .= $val;
                }
                $i++;
            }

            $result[$k]['id'] = $v['id'];
            $result[$k]['to_name'] = $v['name'];
            $result[$k]['to_mobile'] = $v['mobile'];
            $result[$k]['to_address'] = $newAddress . $v['address_info'];
            $result[$k]['is_default'] = $v['is_default'];
            $result[$k]['created_at'] = $v['created_at'];


        }

        return $this->response($result);

    }

    // 新增地址
    public function store(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'   => 'required',
            'mobile'  => 'required',
            'address_id' => 'required',
            'address_info' => 'required',
        ], [
            'name.required' => trans('api.name_cannot_be_empty'),
            'mobile.required' => trans('api.phone_cannot_empty'),
            'address_id.required' => trans('api.address_cannot_empty'),
            'address_info.required' => trans('api.address_details_cannot_empty'),
        ]);

        // 获取城市信息
        $mc = MxCity::where(['id' => $request->get('address_id'), 'level_type' => 3])->first();
        if(!$mc){
            $this->responseError(trans('api.incorrect_area_address_information'));
        }

        $saData = [
            'uid' => Service::auth()->getUser()->id,
            'name' => $request->get('name'),
            'mobile' => $request->get('mobile'),
            'address' => $mc->merger_name,
            'address_info' => $request->get('address_info'),
            'created_at' => now()->toDateTimeString(),
        ];

        if($request->get('is_default', 0) == 1){

            MallAddress::where('uid', Service::auth()->getUser()->id)->update(['is_default' => 0]);

            $saData['is_default'] = $request->get('is_default');
        }

        \DB::beginTransaction();
        try {

            MallAddress::create($saData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址新增异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        return $this->responseSuccess(trans('api.operate_successfully'));

    }

    // 修改地址
    public function update(Request $request, $id)
    {
        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'     => 'required',
            'mobile'     => 'required',
            'address_id' => 'required',
            'address_info' => 'required',
        ], [
            'name.required' => trans('api.name_cannot_be_empty'),
            'mobile.required' => trans('api.phone_cannot_empty'),
            'address_id.required' => trans('api.address_cannot_empty'),
            'address_info.required' => trans('api.address_details_cannot_empty'),
        ]);

        // 验证数据是否正确
        if(!MallAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->exists()){
            $this->responseError(trans('api.parameter_is_wrong'));
        }

        // 获取城市信息
        $mc = MxCity::where(['id' => $request->get('address_id'), 'level_type' => 3])->first();
        if(!$mc){
            $this->responseError(trans('api.incorrect_area_address_information'));
        }

        $saData = [
            'name' => $request->get('name'),
            'mobile' => $request->get('mobile'),
            'address' => $mc->merger_name,
            'address_info' => $request->get('address_info'),
        ];

        if($request->get('is_default', 0) == 1){

            MallAddress::where('uid', Service::auth()->getUser()->id)->update(['is_default' => 0]);

            $saData['is_default'] = 1;
        }

        \DB::beginTransaction();
        try {

            MallAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->update($saData);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址修改异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfull'));

    }

    // 删除地址
    public function destroy($id)
    {

        Service::auth()->isLoginOrFail();

        // 验证数据是否正确
        if(!MallAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->exists()){
            $this->responseError(trans('api.wrong_operation'));
        }

        \DB::beginTransaction();
        try {

            MallAddress::where(['uid' => Service::auth()->getUser()->id, 'id' => $id])->delete();

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('收货地址删除异常');

            $this->responseError(trans('api.wrong_operation'));

        }

        $this->responseSuccess(trans('api.operate_successfully'));

    }


}
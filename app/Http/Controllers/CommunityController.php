<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/20
 * Time: 15:32
 */

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\MxCity;
use App\Models\User;
use App\Models\UserInfo;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommunityController extends Controller
{

    // 获取城市信息
    public function getGity(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $id = $request->get('id', 100000);

        $mc = MxCity::where('parent_id', $id)->get(['id', 'name']);
        if($mc->isEmpty()){
            $this->responseError('数据有误');
        }

        // 当用户状态为拒绝的时候改成0
        if(Service::auth()->getUser()->is_community == 9){
            User::where('id', Service::auth()->getUser()->id)->update(['is_community' => 0]);
        }

        return $this->response($mc->toArray());

    }

    // 申请社区提交
    public function submit(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $this->validate($request->all(), [
            'name'     => 'required',
            'mobile'   => 'required',
            'id'       => 'required',
            'oneself_img' => 'required|image',
            'field_img'  => 'required|image',
        ], [
            'name.required' => '社区名称不能为空',
            'mobile.required' => '手机号不能为空',
            'id.required' => '所在地址不能为空',
            'oneself_img.required' => '本人照片不能为空',
            'field_img.required' => '产地照片不能为空',
        ]);

        // 判断当前用户是否已经申请了社区
        if(Community::where('uid', Service::auth()->getUser()->id)->exists()){
            $this->responseError('已经申请了社区,不能再次申请');
        }

        // 判断用户是否是节点用户
        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if(!$ui || $ui->is_bonus != 1){
            $this->responseError('不是节点用户');
        }

        // 获取城市信息
        $mc = MxCity::where(['id' => $request->get('id'), 'level_type' => 3])->first();
        if(!$mc){
            $this->responseError('所在地址信息有误');
        }

        // 判断当前社区是否被申请
        if(Community::where('address', $mc->merger_name)->exists()){
            $this->responseError('所在地社区已被申请');
        }

        $path = $request->file('oneself_img')->store('us');
        Storage::setVisibility($path, 'public');
        $oneself_img = Storage::url($path);

        $path = $request->file('field_img')->store('us');
        Storage::setVisibility($path, 'public');
        $field_img = Storage::url($path);

        $cData = [
            'uid' => Service::auth()->getUser()->id,
            'name' => $request->get('name'),
            'mobile' => $request->get('mobile'),
            'address' => $mc->merger_name,
            'oneself_img' => $oneself_img,
            'field_img' => $field_img,
            'created_at' => now()->toDateTimeString(),
        ];

        // 生成订单
        \DB::beginTransaction();
        try {

            // 合伙人表新增
            Community::create($cData);

            // 用户表状态改变
            User::where('id', Service::auth()->getUser()->id)->update(['is_community' => 2]);

            \DB::commit();

        } catch (\Exception $exception) {

            \DB::rollBack();

            \Log::info('社区申请异常');

            $this->responseError('操作异常');

        }

        $this->responseSuccess('操作成功');


    }

}
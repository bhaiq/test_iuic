<?php

namespace App\Http\Controllers;

use App\Models\ModePay;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModePayController extends Controller
{
    public function _list()
    {
        Service::auth()->isLoginOrFail();

        return $this->response(Service::auth()->getUser()->modePay->toArray());
    }

    public function create(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $user   = Service::auth()->getUser();
        $types  = $user->modePay->pluck('type');
        $number = $request->input('type') == ModePay::TYPE_BANK ? 'digits_between:16,19|integer' : 'string|between:6,30';
        $this->validate($request->all(), [
            'type'         => ['required', 'between:0,2', Rule::notIn($types)],
            'qr_code'      => 'required_if:type,' . ModePay::TYPE_WECHAT . ',' . ModePay::TYPE_ALI,
            'number'       => 'required|' . $number,
            'name'         => 'required|between:1,22',
            'bank'         => 'required_if:type,' . ModePay::TYPE_BANK,
            'bank.name'    => 'string|max:30|required_if:type,' . ModePay::TYPE_BANK,
            'bank.address' => 'string|max:100'
        ], [
            'type.not_in' => trans('payment.type.exist'),
        ], [
            'qr_code'      => trans('payment.create.qr_code'),
            'bank.name'    => trans('payment.create.bank.name'),
            'bank.address' => trans('payment.create.bank.address')
        ]);

        $data = array_filter($request->only('number', 'type', 'name', 'bank'));

        if ($request->has('qr_code')) {
            $path = $request->file('qr_code')->store('us');
            \Storage::setVisibility($path, 'public');

            $data['qr_code'] = \Storage::url($path);
        }

        $pay = $user->modePay()->create($data);

        $pay->refresh();

        return $this->response($pay->toArray());
    }

    public function update()
    {

    }

    public function del($id)
    {
        Service::auth()->isLoginOrFail();
        $pay = ModePay::findOrFail($id);
        if ($pay->uid != Service::auth()->getUser()->id) return $this->responseError('system.illegal');
        $pay->delete();

        return $this->responseSuccess();
    }

    public function info($id)
    {
        Service::auth()->isLoginOrFail();
        $pay = ModePay::whereUid($id)->get();

        return $this->response($pay->toArray());
    }
}

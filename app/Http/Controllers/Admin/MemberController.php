<?php

namespace App\Http\Controllers\Admin;

use App\Libs\StringLib;
use App\Models\Member;
use App\Services\Service;
use Illuminate\Http\Request;

class MemberController extends Controller
{

    public function _list()
    {
        return $this->response(Member::orderBy('id', 'desc')->get()->toArray());
    }

    public function create(Request $request)
    {
        $rules = [
            'email'    => 'string|required|max:50|email|unique:member,email',
            'password' => 'string|required|between:6,18',
            'name'     => 'required|string|between:2,5',
        ];

        $this->validate($request->all(), $rules);

        $user = Member::create($request->only('email', 'name', 'password'));
        $user->refresh();

        return $this->response($user->toArray());
    }

    public function login(Request $request)
    {
        $this->validate($request->all(), [
            'email'    => 'string|required',
            'password' => 'string|required|between:6,18'
        ]);

        $user = Member::whereEmail($request->input('email'))->first();
        if ($user && $user->password == StringLib::password($request->input('password'))) {
            $data          = $user->toArray();
            $data['token'] = Service::admin()->createToken($user->id);
            return response()->json($data);
        }

        $this->responseError('用户名或密码不存在');
    }

    public function update($id, Request $request)
    {
        $this->validate($request->all(), [
            'email'   => 'string|required|max:50|email|unique:member,email',
            'name'    => 'required|string|between:2,5',
            'role_id' => 'required|integer',
        ]);

        $member          = Member::findOrFail($id);
        $member->email   = $request->input('email');
        $member->name    = $request->input('name');
        $member->role_id = $request->input('role_id');
        $member->save();
        return $this->responseSuccess();
    }

    public function setPassword($id, Request $request)
    {
        $this->validate($request->all(), [
            'password' => 'string|required|between:6,18'
        ]);

        $user           = Member::findOrFail($id);
        $user->password = StringLib::password($request->input('password'));
        $user->save();

        return $this->responseSuccess();
    }
}

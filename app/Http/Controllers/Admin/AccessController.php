<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\RBAC\Access;
use App\Models\Admin\RBAC\RoleAccess;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AccessController extends Controller
{
    public function _list()
    {
        return $this->response(Access::all());
    }

    public function create(Request $request)
    {
        $this->validate($request->all(), [
            'title' => 'required|string|between:4,30',
            'uri'   => 'required|string|between:3,50|unique:access'
        ]);

        $access = Access::create($request->only('title', 'uri'));
        $access->refresh();

        return $this->response($access->toArray());
    }

    public function del($id)
    {
        Access::whereId($id)->delete();
        RoleAccess::whereAccessId($id)->delete();
        return $this->responseSuccess();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\RBAC\Role;
use App\Models\Admin\RBAC\RoleAccess;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function _list()
    {
        return Role::all();
    }

    public function info($id)
    {
        $role = Role::findOrFail($id);
        $role->load('access');
        return $this->response($role->toArray());
    }

    public function create(Request $request)
    {
        $this->validate($request->all(), [
            'name' => 'required|string|between:4,30',
        ]);

        $access = Role::create($request->only('name'));
        $access->refresh();

        return $this->response($access->toArray());
    }

    public function access($id, Request $request)
    {
        $this->validate($request->all(), [
            'type'      => 'required|integer|between:0,1',
            'access_id' => 'required',
        ]);

        $role = Role::findOrFail($id);
        $role->access()->attach($request->input('access_id'));
    }

    public function del($id)
    {
        Role::whereId($id)->delete();
        RoleAccess::whereRoleId($id)->delete();
        return $this->responseSuccess();
    }
}

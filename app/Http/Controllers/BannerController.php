<?php

namespace App\Http\Controllers;

use App\Constants\HeaderConstant;
use App\Models\Banner;
use App\Services\Service;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function _list(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = Banner::oldest('top')->get()->toArray();

        foreach ($result as $k => $v){
            $result[$k]['jump_url'] = $v['jump_url'] . '?x-token=' . $request->header(HeaderConstant::AUTH_TOKEN);
        }

        return $this->response($result);

    }
}

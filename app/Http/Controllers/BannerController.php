<?php

namespace App\Http\Controllers;

use App\Models\Banner;

class BannerController extends Controller
{
    public function _list()
    {

        $result = Banner::oldest('top')->get()->toArray();

        return $this->response($result);

    }
}

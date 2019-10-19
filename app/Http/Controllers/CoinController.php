<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\ExOrder;
use App\Models\ExTeam;
use App\Models\Redis;
use Illuminate\Http\Request;

class CoinController extends Controller
{
    public function _list(Request $request)
    {
        if ($request->get('is_legal', 1)) {
            return $this->response(Coin::whereIsLegal($request->get('is_legal', 1))->get(['name', 'id'])->toArray());
        }
        return $this->response(Coin::all('name', 'id')->toArray());
    }

    public function getTeam($id)
    {
        return $this->response(ExOrder::market($id, 60));
    }
}

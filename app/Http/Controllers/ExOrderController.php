<?php

namespace App\Http\Controllers;

use App\Models\ExOrderTogetger;
use Illuminate\Http\Request;

class ExOrderController extends Controller
{
    public function _list($team_id)
    {
        $order = ExOrderTogetger::whereTeamId($team_id)->orderBy('id', 'desc')->take(20)->get()->toArray();
        return $this->response($order);
    }

    public function price($team_id)
    {
        return $this->response(ExOrderTogetger::historyPrice($team_id));
    }
}

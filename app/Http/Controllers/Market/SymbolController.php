<?php

namespace App\Http\Controllers\Market;

use App\Models\ExTeam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SymbolController extends Controller
{
    public function info(Request $request)
    {

        $model = [
            'name'                  => 'IUIC/USDT',
            'exchange-traded'       => 'IUIC',
            'exchange-listed'       => 'IUIC',
            'timezone'              => 'Asia/Shanghai',
            'minmov'                => 1,
            'minmov2'               => 0,
            'pointvalue'            => 1,
            'session'               => '24x7',
            'has_intraday'          => true,
            'has_no_volume'         => false,
            'description'           => 'IUIC',
            'type'                  => 'bitcoin',
            "supported_resolutions" => [
                "1",
                "15",
                "60",
                "240",
                "D",
                "W"
            ],
            "intraday_multipliers"  => [
                "1",
                "15",
                "60",
                "240",
                "D",
                "W"
            ],
            'pricescale'            => 10000,
            'ticker'                => 'IUIC'
        ];

        $array     = explode(':', $request->get('symbol'));
        $team_name = $array[count($array) - 1];

        \Log::info('K线图数据有误', ['team_name' => $team_name]);
        dd($team_name);
        $team      = ExTeam::whereName($team_name)->first()->toSql();
        dd($team);


        $model['description'] = $team->name;
        $model['name']        = $team->name;
        $model['ticker']      = $team->name;

        return $model;
    }
}

<?php

namespace App\Http\Controllers\Market;

use App\Models\ExTeam;
use App\Models\SymbolHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $team   = ExTeam::whereName($request->get('symbol'))->first();
        $type   = SymbolHistory::getType($request->get('resolution', 1));
        $orders = SymbolHistory::whereType($type)->whereTeamId($team->id)->whereBetween('created_at', [Carbon::createFromTimestamp($request->get('from'))->toDateTimeString(), Carbon::createFromTimestamp($request->get('to'))->toDateTimeString()])->get();

        $data['t'] = $orders->pluck('created_at');
        $data['o'] = $orders->pluck('o');
        $data['h'] = $orders->pluck('h');
        $data['l'] = $orders->pluck('l');
        $data['c'] = $orders->pluck('c');
        $data['v'] = $orders->pluck('v');
        $data['s'] = $orders->isEmpty() ? 'no_data' : 'ok';

        if ($type != 0 && !$orders->isEmpty()) {

            $cur = SymbolHistory::whereType($type)->whereTeamId($team->id)->orderBy('id', 'desc')->first();

            $dur       = $this->getNextTime($type);
            $cur_order = SymbolHistory::whereType($type - 1)->whereTeamId($team->id)->whereBetween('created_at', $dur)->get();

            if ($type == SymbolHistory::TYPE_1D){
                $data['t'][] = $dur['st'];
            }else{
                $data['t'][] = $dur['et'];
            }
            if ($cur_order->isEmpty()) {
                if ($cur) {
                    $data['o'][] = $cur->c;
                    $data['h'][] = $cur->c;
                    $data['l'][] = $cur->c;
                    $data['c'][] = $cur->c;
                    $data['v'][] = 0;
                } else {
                    $data['o'][] = 0;
                    $data['h'][] = 0;
                    $data['l'][] = 0;
                    $data['c'][] = 0;
                    $data['v'][] = 0;
                }
            } else {
                if ($cur) {
                    $data['o'][] = $cur->c;
                    $data['h'][] = $cur_order->max('h');
                    $data['l'][] = $cur_order->min('l');
                    $data['c'][] = $cur_order->last()->c;
                    $data['v'][] = $cur_order->sum('v');
                } else {
                    $data['o'][] = $cur->c;
                    $data['h'][] = $cur_order->max('h');
                    $data['l'][] = $cur_order->min('l');
                    $data['c'][] = $cur_order->last()->c;
                    $data['v'][] = $cur_order->sum('v');
                }
            }

        }

        return $data;
    }

    public function getNextTime($type)
    {
        //$type 0 一分钟线; 1 15分钟线; 2 1小时; 3 4小时线; 4 1天线; 5 周线

        switch ($type) {
            case SymbolHistory::TYPE_15:
                $min  = bcmul(bcdiv(date('i'), 15, 0), 15, 0);
                $next = date('Y-m-d H:') . $min;
                Carbon::createFromDate($next)->timestamp;
                $next = [
                    'st' => Carbon::createFromDate($next)->timestamp,
                    'et' => Carbon::createFromDate($next)->timestamp + 900
                ];
                break;
            case SymbolHistory::TYPE_1H:
                $next = [
                    'st' => Carbon::now()->startOfHour()->timestamp,
                    'et' => Carbon::now()->startOfHour()->timestamp + 3600
                ];
                break;
            case SymbolHistory::TYPE_4H:
                $H    = bcmul(bcdiv(date('H'), 4, 0), 4, 0);
                $next = date('Y-m-d ') . $H .':00:00';
                $next = [
                    'st' => Carbon::createFromDate($next)->timestamp,
                    'et' => Carbon::createFromDate($next)->timestamp + 14400,
                ];
                break;
            case SymbolHistory::TYPE_1D:
                $next = [
                    'st' => Carbon::now()->startOfDay()->timestamp,
                    'et' => Carbon::now()->startOfDay()->timestamp + 3600 * 24,
                ];
                break;
            case SymbolHistory::TYPE_W:
                $next = [
                    'st' => Carbon::now()->startOfWeek()->timestamp,
                    'et' => Carbon::now()->startOfWeek()->timestamp + 3600 * 24 * 7,
                ];
                break;
        }
        return $next;

    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/18
 * Time: 15:00
 */

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Services\Service;
use Illuminate\Http\Request;

class BonusController extends Controller
{

    public function log(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = AccountLog::select('coin_id', 'amount as num', 'type', 'remark as exp', 'created_at')
            ->whereIn('scene', [14, 15])
            ->where('uid', Service::auth()->getUser()->id)
          	->latest('id')
            ->paginate($request->get('per_page', 10))
            ->toArray();

        foreach ($result['data'] as $k => $v){
            $result['data'][$k]['sign'] = $v['type'] ? '+' : '-';
            $result['data'][$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);

            if($v['coin_id'] == 2){
                $result['data'][$k]['unit'] = 'IUIC';
            }else{
                $result['data'][$k]['unit'] = 'USDT';
            }

            unset($result['data'][$k]['type']);
        }


        $res = [
            'admin_bonus_total' => AccountLog::where(['uid' => Service::auth()->getUser()->id, 'scene' => 15])->sum('amount'),
            'bonus_total' => AccountLog::where(['uid' => Service::auth()->getUser()->id, 'scene' => 14])->sum('amount'),
        ];

        return $this->response(array_merge($result, $res));

    }

}
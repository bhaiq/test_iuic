<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/12
 * Time: 10:54
 */

namespace App\Http\Controllers;

use App\Models\UserInfo;
use App\Models\UserWalletLog;
use App\Services\Service;
use Illuminate\Http\Request;

class PoolController extends Controller
{

    public function log(Request $request)
    {

        Service::auth()->isLoginOrFail();

        $result = UserWalletLog::where('uid', Service::auth()->getUser()->id)
            ->where('wallet_type', 2)
            ->where('log_type', 1)
            ->select('exp', 'sign', 'num', 'created_at')
            ->latest()->paginate($request->get('per_page', 10));

        $ui = UserInfo::where('uid', Service::auth()->getUser()->id)->first();
        if(!$ui){
            $res['ore_pool_total'] = 0;
            $res['release_total'] = 0;
            $res['residue_total'] = 0;
        }else{
            $res['ore_pool_total'] = $ui->buy_total;
            $res['release_total'] = $ui->release_total;
            $res['residue_total'] = bcsub($ui->buy_total, $ui->release_total, 8);
        }

        foreach ($result as $k => $v){
            $result[$k]['unit'] = 'IUIC';
        }

        return $this->response(array_merge($result->toArray(), ['data_total' => $res]));

    }

}
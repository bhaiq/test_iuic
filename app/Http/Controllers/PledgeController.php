<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/24
 * Time: 15:02
 */

namespace App\Http\Controllers;

use App\Models\PledgeLevel;
use App\Services\Service;
use Illuminate\Http\Request;

class PledgeController extends Controller
{

    // 质押页面信息
    public function start()
    {

        Service::auth()->isLoginOrFail();

        // 获取质押列表信息
        $pl = PledgeLevel::latest('num')->get();

        $result = [];
        $isDefault = true;
        foreach ($pl as $k => $v){

            if($isDefault && Service::auth()->getUser()->pledge_num >= $v->num){

                $result[] = [
                    'num' => $v->num,
                    'sort' => $k+1,
                    'bl' => bcmul($v->pledge_bl, 100) . '%',
                    'is_default' => 1,
                ];

                $isDefault = false;

            }else{

                $result[] = [
                    'num' => $v->num,
                    'sort' => $k+1,
                    'bl' => bcmul($v->pledge_bl, 100) . '%',
                    'is_default' => 0,
                ];

            }

        }

        return $this->response($result);

    }

}
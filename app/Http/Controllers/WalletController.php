<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\Coin;
use App\Models\Wallet;
use App\Services\AddressCreateServer;
use App\Services\Service;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function _list(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $id = Service::auth()->getUser()->id;
        AccessToken::whereUid($id)->update(['ip' => $request->getClientIp()]);
        return $this->response(Wallet::whereUid($id)->get()->toArray());
    }

    public function detail($coin_id)
    {
        $this->responseError(trans('api.function_not_open_yet'));
      
        Service::auth()->isLoginOrFail();
        $coin    = Coin::find($coin_id)->toArray();
        $wallets = Wallet::whereUid(Service::auth()->getUser()->id)->get()->keyBy('type')->toArray();

        foreach ($coin['coin_types'] as $k => $coin_type) {
            if (!isset($wallets['coin_type'])) {
                $coin['coin_types'][$k]['address'] = (new AddressCreateServer())->addressSave(Service::auth()->getUser()->id, $coin_type['coin_type']);
            } else {
                $coin['coin_types'][$k]['address'] = $wallets[$coin_type['coin_type']]['address'];
            }
        }

        return $this->response($coin);
    }
}

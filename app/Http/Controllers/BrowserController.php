<?php

namespace App\Http\Controllers;

use App\Libs\StringLib;
use App\Models\Account;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class BrowserController extends Controller
{
    public function market()
    {
        $cli      = new Client();
        $response = $cli->get('https://data.gateio.co/api2/1/marketlist');
        $arr      = json_decode($response->getBody()->getContents(), true);
        $res      = array_filter($arr['data'], function ($item) {
            return in_array($item['pair'],
                ['eos_usdt', 'btc_usdt', 'eth_usdt', 'ltc_usdt',
                    'bch_usdt', 'xrp_usdt', 'trx_usdt'
                ]);
        });

        foreach ($res as &$re) {
            $re['rate_cny'] = StringLib::sprintN($re['rate'] * Account::getRate());
        }

        return $this->response(array_values($res));
    }

}

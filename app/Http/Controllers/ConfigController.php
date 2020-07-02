<?php

namespace App\Http\Controllers;

use App\Models\ExTeam;
use App\Models\Version;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ConfigController extends Controller
{
    public function init()
    {
        return $this->response([
            'exchange_rate' => $this->getExchangeRate(),
        ]);

    }

    public function getExchangeRate()
    {
        $key = 'ExchangeRate:usd-cn';

        $val = Redis::get($key);
        if ($val) {
            return $val;
        }

        $client   = new Client();
        $response = $client->request('GET', 'http://web.juhe.cn:8080/finance/exchange/rmbquot?type=1&bank=3&key=a2013292237a26379da1bc88d2309d5d');
        $data     = json_decode($response->getBody()->getContents(), true);
        $res      = array_shift($data['result'])['美元']['bankConversionPri'] / 100;

        Redis::setex($key, 60 * 60 * 3, $res);

        return $res;
    }

    public function version(Request $request)
    {
       $this->validate($request->all(), [
            'type'    => 'required|between:0,1',
            'version' => 'required'
        ]);

        $version        = Version::whereType($request->get('type'))->orderBy('id', 'desc')->first();
        $data           = $version->toArray();
        $data['is_new'] = $request->get('version') != $version->current_version;

        return $this->response($data);


    }

    public function test(Request $request)
    {
        ExTeam::pushCurPrice(1, 10);
        ExTeam::pushList(1);
        return $request->toArray();
    }
}

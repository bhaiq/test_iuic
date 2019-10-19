<?php

namespace App\Services;

use Pusher\Pusher;
use GuzzleHttp\Client;

class PusherServer
{

    protected $client;

    const EVENT_EX_CUR_PRICE = 'ex_cur_price_team_';

    public function __construct()
    {
//        $this->client = new Pusher('147d75f05e982aac5371', '4d37a24aaf1bb0453193', '841112', [
//            'cluster' => 'ap1',
//            'useTLS' => true
//        ]);
        $this->client = new Pusher('e1ba85a2709e9d44ec57', '30447a7d762468623f02', '845112', [
            'cluster' => 'ap1',
            'useTLS' => true
        ]);
    }

    /**
     * @param string $event
     * @param array  $data
     * @throws \Pusher\PusherException
     */
    public function allPush(string $event, array $data)
    {
        $this->iosPush($event, $data);
        $this->androidPush($event, $data);
    }

    /**
     * @param string $event
     * @param array  $data
     * @throws \Pusher\PusherException
     */
    public function iosPush(string $event, array $data)
    {
        $this->client->trigger(env('PUSHER_IOS_SUBSCRIBE'), $event, $data);
    }

    /**
     * @param string $event
     * @param array  $data
     * @throws \Pusher\PusherException
     */
    public function androidPush(string $event, array $data)
    {
        $this->client->trigger(env('PUSHER_ANDROID_SUBSCRIBE'), $event, $data);
    }

}

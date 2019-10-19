<?php


namespace App\Services;


use JPush\Client;

class JGPushServer
{

    protected $app_key;
    protected $master_secret;


    protected $client;

    public function __construct()
    {
        $this->app_key = env('JUPUSH_APP_KEY');
        $this->master_secret = env('JUPUSH_MASTER_SECRET');
        $this->client = new Client($this->app_key, $this->master_secret);
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

    public function iosPush(string $event, array $data) {
        if(env('PUSHER_ARR_ENV')) {
            $this->client->push()
                ->setPlatform('ios')
                ->addTag('testIosSubscribe')
                ->message('', [
                    'title' => $event,
                    'content_type' => '',
                    'extras' => $data
                ])
                ->send();
        }else{
            $this->client->push()
                ->setPlatform('ios')
                ->addTag('iosSubscribe')
                ->message('', [
                    'title' => $event,
                    'content_type' => '',
                    'extras' => $data
                ])
                ->send();
        }
    }

    public function androidPush(string $event, array $data) {
        if(env('PUSHER_ARR_ENV')) {
            $this->client->push()
                ->setPlatform('android')
                ->addTag('testAndroidSubscribe')
                ->message('', [
                    'title' => $event,
                    'content_type' => '',
                    'extras' => $data
                ])
                ->send();
        }else{
            $this->client->push()
                ->setPlatform('android')
                ->addTag('androidSubscribe')
                ->message('', [
                    'title' => $event,
                    'content_type' => '',
                    'extras' => $data
                ])
                ->send();
        }
    }



}

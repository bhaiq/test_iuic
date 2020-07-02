<?php

namespace App\Services;

use App\Models\Wallet;
use GuzzleHttp\Client;

class EthService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param int $uid
     * @return Wallet|\Illuminate\Database\Eloquent\Model
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createEthAddress(int $uid)
    {
        $address = $this->post('http://127.0.0.1:8080/eth/getUserAddress/' . $uid);
        return $address;
    }

    /**
     * @param $url
     * @param $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $url, array $data = [])
    {
        $response = $this->client->request('POST', $url, $data);
        return $response->getBody()->getContents();
    }

    public function get(string $url, array $pram)
    {
        return $this->client->get($url, $pram);
    }
}

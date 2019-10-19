<?php


namespace App\Services;

use Graze\GuzzleHttp\JsonRpc\Client;

class AitcCoinServer
{

    protected $url = 'http://47.90.65.240:6665';

    /**
     * 获取新的用户地址
     * @return mixed
     */
    function getNewAddress() {
        $result = $this->rpcRequestSend('getnewaddress');
        $res = json_decode($result, true);
        return $res['result'];
    }

    function getAddressList() {
        $result = $this->rpcRequestSend('getaddress');
        $res = json_decode($result, true);
        return $res['result'];
    }

    /**
     * 获取钱包余额或某地址余额
     * @param string $address
     * @return mixed
     */
    function getBalance(string $address = '') {
        $result = $this->rpcRequestSend('getbalance', [$address]);
        $res = json_decode($result, true);
        return $res['result'];
    }

    /**
     * 转移数量
     * @param string $address
     * @param        $amount
     * @return mixed
     */
    function sendToAddress(string $address, $amount) {
        $result = $this->rpcRequestSend('sendtoaddress', [$address, $amount]);
        $res = json_decode($result, true);
        return $res['result'];
    }

    /**
     * 获取历史交易记录
     * @param int $number
     * @return mixed
     */
    function getTransactionList(int $number = 100) {
        $result = $this->rpcRequestSend('listtransactions', [$number]);
        $res = json_decode($result, true);
        return $res['result'];
    }


    /**
     * 发送请求
     * @param string $method
     * @param array  $params
     * @return string
     */
    function rpcRequestSend(string $method, array $params = [0]) {
        $client = Client::factory($this->url);
        $requestMessage = $client->request('1', $method, $params);
        $request = $client->send($requestMessage);
        return $request->getBody()->getContents();
    }
}

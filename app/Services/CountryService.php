<?php

namespace App\Services;

use GuzzleHttp\Client;

class CountryService
{
    protected $client;
    protected $_country;

    public function __construct(string $ip)
    {
        $this->client = new Client();
        $this->setCountry($ip);
    }

    public function __get($name)
    {
        if (isset($this->_country[$name]))
            return $this->_country[$name];
        return null;
    }

    public function toArray()
    {
        return $this->_country;
    }

    public function setCountry(string $ip)
    {
        $this->_country = json_decode($this->get('http://ip-api.com/json/' . $ip . '?fields=8122367&lang=zh-CN'), true);
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

    public function get(string $url, array $pram = [])
    {
        $response = $this->client->request('GET', $url, $pram);
        return $response->getBody()->getContents();
    }
}

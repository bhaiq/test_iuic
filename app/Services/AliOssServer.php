<?php


namespace App\Services;
use Illuminate\Http\Request;
use OSS\OSSClient;


class AliOssServer
{

    protected $accessKeyId;
    protected $accessKeySecret;
    protected $endpoint;
    protected $bucket;

    protected $client;


    public function __construct()
    {
        $this->accessKeyId = env('ALI_OOS_ACCESS_KEY_ID', null);
        $this->accessKeySecret= env('ALI_OOS_ACCESS_KEY_SECRET', null);
        $this->endpoint = env('ALI_OOS_ENDPOINT', null);
        $this->bucket = env('ALI_OOS_BUCKET');
        $this->client = new OSSClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
    }


    public function imageUpload(string $key, Request $request) {
        $path = $request->file($key)->store('us');
        var_dump($path);
        $res = $this->client->uploadFile($this->bucket, $key, $path);
        return $res['info']['url'];
    }

}

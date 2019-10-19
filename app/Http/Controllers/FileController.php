<?php

namespace App\Http\Controllers;

use App\Services\AliOssServer;
use App\Services\Service;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function create(Request $request)
    {
//        $path = (new AliOssServer)->imageUpload('name', $request);
        $path = Service::s3()->create('name', $request);
        return $this->response([
            'url' => $path
        ]);
    }
}

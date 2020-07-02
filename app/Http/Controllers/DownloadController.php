<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/5
 * Time: 15:57
 */

namespace App\Http\Controllers;


use App\Models\Version;

class DownloadController extends Controller
{

    public function index()
    {

        $iosUrl = './';
        $andUrl = './';

        // 获取最新版本的IOS地址
        $ver1 = Version::latest('id')->where('type', 1)->first();
        if($ver1){
            $iosUrl = $ver1->url;
        }

        // 获取最新版本的安卓地址
        $ver2 = Version::latest('id')->where('type', 0)->first();
        if($ver2){
            $andUrl = $ver2->url;
        }


        $data = [
            'ios_url' => $iosUrl,
            'and_url' => $andUrl,
        ];
      
        return view('download', $data);
    }

}
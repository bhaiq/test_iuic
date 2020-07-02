<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 15:27
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommonController extends Controller
{

    // 上传图片
    public function uploadImage(Request $request)
    {

        $this->validate($request->all(), [
            'file' => 'required|image'
        ], [
            'file.required' =>'图片不能为空',
            'file.image' => '文件必须是只图片',
        ]);


        if (is_array($request->file('file'))) {

            $files = $request->file('file');
            $url = [];

            foreach ($files as $key => $file) {
                $path = $file->store('us');
                Storage::setVisibility($path, 'public');
                $url[] = Storage::url($path);

            }

        }else{

            $path = $request->file('file')->store('us');
            Storage::setVisibility($path, 'public');
            $url = Storage::url($path);
        }

        return $this->response(['url' => $url]);

    }

}
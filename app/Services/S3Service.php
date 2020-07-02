<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    /**
     * @param string  $key
     * @param Request $request
     * @return string
     */
    public function create(string $key, Request $request): string
    {
        $path = $request->file($key)->store('us');
        Storage::setVisibility($path, 'public');
        return Storage::url($path);
    }

    /**
     * @param         $old_key
     * @param string  $key
     * @param Request $request
     * @return string
     */
    public function replace($old_key, string $key, Request $request): string
    {
        if ($old_key) {
            $old_key = strstr($old_key, "us/");
            Storage::delete($old_key);
        }
        $path = $request->file($key)->store('us');
        Storage::setVisibility($path, 'public');
        return Storage::url($path);
    }

}

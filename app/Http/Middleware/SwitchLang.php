<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SwitchLang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//        return $next($request);
        $language = $request->header("lang");
        if(empty($language)){
            App::setLocale('zh-CN');
        }else{
            App::setLocale($language);
        }
        return $next($request);
    }
}

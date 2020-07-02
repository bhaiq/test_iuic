<?php

namespace App\Http\Middleware;

use App\Services\Service;
use App\Services\ServiceProvider;
use Closure;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = $request->header('locale', 'zh-CN');
        \App::setLocale($locale);
        Service::auth()->init($request);
        return $next($request);
    }
}

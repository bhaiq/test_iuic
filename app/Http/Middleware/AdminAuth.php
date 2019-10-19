<?php

namespace App\Http\Middleware;

use App\Services\Service;
use Closure;

class AdminAuth
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
        Service::admin()->init($request);
        return $next($request);
    }
}

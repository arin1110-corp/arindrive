<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_login')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
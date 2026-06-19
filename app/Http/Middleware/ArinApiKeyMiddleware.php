<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ArinApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-ARINDRIVE-KEY');

        if (!$apiKey || $apiKey !== env('ARINDRIVE_API_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized API Key',
            ], 401);
        }

        return $next($request);
    }
}
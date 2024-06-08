<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [ // Replace with your allowed origins
            'http://localhost:3000',
        ];

        $origin = $request->server('HTTP_ORIGIN');

        if (in_array($origin, $allowedOrigins)) {
            $response = $next($request);
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Authorization, Origin');

            if ($request->method() === 'OPTIONS') {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }

            return $response;
        }

        return response('Unauthorized', 401);
    }
}

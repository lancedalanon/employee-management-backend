<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => EnsureUserHasRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Exception $exception) {
            // Get the current request instance
            $request = Request::instance();

            // Generate a unique error ID
            $errorId = Str::uuid();
    
            // Collect detailed context
            $context = [
                'error_id' => $errorId,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'request_url' => $request->fullUrl(),
                'user_agent' => $request->header('User-Agent'),
                'client_ip' => $request->ip(),
            ];
    
            // Log error with context
            Log::error('Error occurred', $context);
            
            // Default response for any exception
            return response()->json([
                'message' => 'Internal Server Error.',
                'error_id' => $errorId, // Provide the error ID for further investigation
            ], 500);
        })->stop();
    })->create();

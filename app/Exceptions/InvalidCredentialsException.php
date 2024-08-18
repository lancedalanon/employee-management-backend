<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidCredentialsException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Optionally, log the exception or send it to an external service.
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }
}

<?php

namespace App\Services\Logging;

use Illuminate\Http\Request;

class LogLevelResolver
{
    /**
     * Determine log level based on request path and method
     */
    public function getRequestLogLevel(Request $request): string
    {
        // Health checks and monitoring endpoints - debug level
        if (str_contains($request->path(), 'health') || str_contains($request->path(), 'status')) {
            return 'debug';
        }

        // Provider endpoints - info level
        if (str_contains($request->path(), 'provider')) {
            return 'info';
        }

        // Default to info
        return 'info';
    }

    /**
     * Determine log level based on response status code
     */
    public function getResponseLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',        // Server errors
            $statusCode >= 400 => 'warning',      // Client errors
            $statusCode >= 300 => 'info',         // Redirects
            $statusCode >= 200 => 'info',         // Success
            default => 'debug',
        };
    }
}

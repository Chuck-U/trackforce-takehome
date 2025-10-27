<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logRequest($request);
        
        // Process the request
        $response = $next($request);
        
        // Calculate execution time
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log response
        $this->logResponse($request, $response, $executionTime);
        
        return $response;
    }

    /**
     * Log the incoming request
     */
    private function logRequest(Request $request): void
    {
        $logLevel = $this->getLogLevel($request);
        
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'provider' => $this->getProviderFromRequest($request),
        ];

        // Only log body for non-GET requests, and sanitize sensitive data
        if ($request->method() !== 'GET') {
            $context['body'] = $this->sanitizeRequestData($request->all());
        }

        Log::log($logLevel, 'API Request Received', $context);
    }

    /**
     * Log the response
     */
    private function logResponse(Request $request, Response $response, float $executionTime): void
    {
        $statusCode = $response->getStatusCode();
        $logLevel = $this->getResponseLogLevel($statusCode);
        
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $statusCode,
            'execution_time_ms' => $executionTime,
            'provider' => $this->getProviderFromRequest($request),
        ];

        // Log response body for errors
        if ($statusCode >= 400) {
            $content = $response->getContent();
            if ($content && $this->isJson($content)) {
                $context['response'] = json_decode($content, true);
            }
        }

        $message = $this->getResponseMessage($statusCode);
        Log::log($logLevel, $message, $context);
    }

    /**
     * Determine log level based on request path and method
     */
    private function getLogLevel(Request $request): string
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
    private function getResponseLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',        // Server errors
            $statusCode >= 400 => 'warning',      // Client errors
            $statusCode >= 300 => 'info',         // Redirects
            $statusCode >= 200 => 'info',         // Success
            default => 'debug',
        };
    }

    /**
     * Get response message based on status code
     */
    private function getResponseMessage(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'API Request Failed - Server Error',
            $statusCode >= 400 => 'API Request Failed - Client Error',
            $statusCode >= 300 => 'API Request Redirected',
            $statusCode >= 200 => 'API Request Successful',
            default => 'API Request Completed',
        };
    }

    /**
     * Extract provider name from request
     */
    private function getProviderFromRequest(Request $request): ?string
    {
        $path = $request->path();
        
        if (str_contains($path, 'provider1')) {
            return 'provider1';
        }
        
        if (str_contains($path, 'provider2')) {
            return 'provider2';
        }
        
        return null;
    }

    /**
     * Sanitize sensitive data from request
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'authorization',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Check if content is JSON
     */
    private function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}


<?php

namespace App\Services\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogger
{
    public function __construct(
        private LogLevelResolver $logLevelResolver,
        private SensitiveDataSanitizer $dataSanitizer
    ) {}

    /**
     * Log the incoming request
     */
    public function logRequest(Request $request): void
    {
        $logLevel = $this->logLevelResolver->getRequestLogLevel($request);
        
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'provider' => $this->getProviderFromRequest($request),
        ];

        // Only log body for non-GET requests, and sanitize sensitive data
        if ($request->method() !== 'GET') {
            $context['body'] = $this->dataSanitizer->sanitize($request->all());
        }

        Log::log($logLevel, 'API Request Received', $context);
    }

    /**
     * Log the response
     */
    public function logResponse(Request $request, $response, float $executionTime): void
    {
        $statusCode = $response->getStatusCode();
        $logLevel = $this->logLevelResolver->getResponseLogLevel($statusCode);
        
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
     * Check if content is JSON
     */
    private function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

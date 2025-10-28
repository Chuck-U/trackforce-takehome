<?php

namespace App\Http\Middleware;

use App\Services\Logging\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function __construct(
        private RequestLogger $requestLogger
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->requestLogger->logRequest($request);
        
        // Process the request
        $response = $next($request);
        
        // Calculate execution time
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log response
        $this->requestLogger->logResponse($request, $response, $executionTime);
        
        return $response;
    }
}


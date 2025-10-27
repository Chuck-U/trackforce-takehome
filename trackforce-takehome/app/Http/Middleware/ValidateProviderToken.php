<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateProviderToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Missing or invalid authorization header',
                ],
            ], 401);
        }

        // Extract token
        $token = substr($authHeader, 7);

        // Validate token format (basic validation)
        if (empty($token) || strlen($token) < 10) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid token format',
                ],
            ], 401);
        }

        // In a production environment, you would validate the token against
        // a database, cache, or by verifying a JWT signature
        // For this implementation, we accept any properly formatted Bearer token

        return $next($request);
    }
}


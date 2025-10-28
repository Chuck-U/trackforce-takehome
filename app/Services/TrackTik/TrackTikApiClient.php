<?php

namespace App\Services\TrackTik;

use App\Domain\DataTransferObjects\TrackTikResponse;
use App\Services\Auth\OAuth2TokenManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackTikApiClient
{
    public function __construct(
        private string $baseUrl,
        private OAuth2TokenManager $tokenManager
    ) {}

    /**
     * Authenticate with TrackTik OAuth2 service
     * 
     * @return string Access token
     * @throws \Exception
     */
    private function authenticate(): string
    {
        Log::info('Authenticating with TrackTik OAuth2 service');
        
        try {
            $token = $this->tokenManager->getAccessToken();
            
            Log::info('Successfully authenticated with TrackTik OAuth2');
            
            return $token;
        } catch (\Exception $e) {
            Log::error('Failed to authenticate with TrackTik OAuth2', [
                'message' => $e->getMessage(),
            ]);
            
            throw new \Exception('OAuth2 authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Make authenticated POST request to TrackTik API
     *
     * @param string $endpoint
     * @param array $data
     * @return TrackTikResponse
     */
    public function post(string $endpoint, array $data): TrackTikResponse
    {
        try {
            // Explicitly authenticate with OAuth2 before making the request
            $token = $this->authenticate();
            
            Log::info('Making authenticated POST request to TrackTik', [
                'endpoint' => $endpoint,
            ]);
            
            $response = Http::withToken($token)->post("{$this->baseUrl}{$endpoint}", $data);

            return $this->handleResponse($response, 'POST', $endpoint, $data);
        } catch (\Exception $e) {
            Log::error('Exception making POST request to TrackTik', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
                'data' => $data,
            ]);

            return TrackTikResponse::error($e->getMessage());
        }
    }

    /**
     * Make authenticated PUT request to TrackTik API
     *
     * @param string $endpoint
     * @param array $data
     * @return TrackTikResponse
     */
    public function put(string $endpoint, array $data): TrackTikResponse
    {
        try {
            // Explicitly authenticate with OAuth2 before making the request
            $token = $this->authenticate();
            
            Log::info('Making authenticated PUT request to TrackTik', [
                'endpoint' => $endpoint,
            ]);
            
            $response = Http::withToken($token)->put("{$this->baseUrl}{$endpoint}", $data);

            return $this->handleResponse($response, 'PUT', $endpoint, $data);
        } catch (\Exception $e) {
            Log::error('Exception making PUT request to TrackTik', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
                'data' => $data,
            ]);

            return TrackTikResponse::error($e->getMessage());
        }
    }

    /**
     * Make authenticated GET request to TrackTik API
     *
     * @param string $endpoint
     * @return TrackTikResponse
     */
    public function get(string $endpoint): TrackTikResponse
    {
        try {
            // Explicitly authenticate with OAuth2 before making the request
            $token = $this->authenticate();
            
            Log::info('Making authenticated GET request to TrackTik', [
                'endpoint' => $endpoint,
            ]);
            
            $response = Http::withToken($token)->get("{$this->baseUrl}{$endpoint}");

            return $this->handleResponse($response, 'GET', $endpoint);
        } catch (\Exception $e) {
            Log::error('Exception making GET request to TrackTik', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return TrackTikResponse::error($e->getMessage());
        }
    }

    /**
     * Handle HTTP response and convert to TrackTikResponse
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @return TrackTikResponse
     */
    private function handleResponse($response, string $method, string $endpoint, ?array $data = null): TrackTikResponse
    {
        if ($response->successful()) {
            return TrackTikResponse::success($response->json('data') ?? []);
        }

        $errorMessage = $response->json('error.message') ?? $this->getDefaultErrorMessage($method);
        
        Log::error("Failed to {$method} {$endpoint} in TrackTik", [
            'status' => $response->status(),
            'body' => $response->body(),
            'endpoint' => $endpoint,
            'data' => $data,
        ]);

        return TrackTikResponse::error($errorMessage);
    }

    /**
     * Get default error message based on HTTP method
     *
     * @param string $method
     * @return string
     */
    private function getDefaultErrorMessage(string $method): string
    {
        return match ($method) {
            'POST' => 'Failed to create employee',
            'PUT' => 'Failed to update employee',
            'GET' => 'Employee not found',
            default => 'API request failed',
        };
    }
}

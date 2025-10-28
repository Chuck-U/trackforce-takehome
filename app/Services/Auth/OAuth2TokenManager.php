<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuth2TokenManager
{
    private const TTL_DEFAULT_TIME = 3600;
    private const CACHE_KEY = 'tracktik_access_token';

    public function __construct(
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private string $scope
    ) {}

    /**
     * Get OAuth2 access token (cached)
     *
     * @return string
     * @throws \Exception
     */
    public function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_DEFAULT_TIME, function () {
            Log::info('Requesting new OAuth2 access token from TrackTik', [
                'token_url' => $this->tokenUrl,
                'client_id' => $this->clientId,
                'scope' => $this->scope,
            ]);

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => $this->scope,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to obtain TrackTik access token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token_url' => $this->tokenUrl,
                ]);
                throw new \Exception('Failed to authenticate with TrackTik API');
            }

            $data = $response->json();
            
            Log::info('Successfully obtained OAuth2 access token', [
                'expires_in' => $data['expires_in'] ?? 'unknown',
            ]);
            
            return $data['access_token'];
        });
    }

    /**
     * Clear cached token
     *
     * @return void
     */
    public function clearToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

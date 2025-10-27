<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TrackTikService
{
    private string $baseUrl;
    private string $tokenUrl;
    private string $clientId;
    private string $clientSecret;
    private string $scope;

    public function __construct()
    {
        $credentials = config('services.tracktik');
        
        $this->baseUrl = $credentials['base_url'];
        $this->tokenUrl = $credentials['token_url'];
        $this->clientId = $credentials['client_id'];
        $this->clientSecret = $credentials['client_secret'];
        $this->scope = $credentials['scope'];
    }

    /**
     * Get OAuth2 access token (cached)
     */
    private function getAccessToken(): string
    {
        return Cache::remember('tracktik_access_token', 3500, function () {
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
                ]);
                throw new \Exception('Failed to authenticate with TrackTik API');
            }

            $data = $response->json();
            return $data['access_token'];
        });
    }

    /**
     * Create employee in TrackTik
     *
     * @param array<string, mixed> $employeeData
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function createEmployee(array $employeeData): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/employees", $employeeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            Log::error('Failed to create employee in TrackTik', [
                'status' => $response->status(),
                'body' => $response->body(),
                'employee_data' => $employeeData,
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Failed to create employee',
            ];
        } catch (\Exception $e) {
            Log::error('Exception creating employee in TrackTik', [
                'message' => $e->getMessage(),
                'employee_data' => $employeeData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update employee in TrackTik
     *
     * @param string $employeeId
     * @param array<string, mixed> $employeeData
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function updateEmployee(string $employeeId, array $employeeData): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->put("{$this->baseUrl}/employees/{$employeeId}", $employeeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            Log::error('Failed to update employee in TrackTik', [
                'status' => $response->status(),
                'body' => $response->body(),
                'employee_id' => $employeeId,
                'employee_data' => $employeeData,
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Failed to update employee',
            ];
        } catch (\Exception $e) {
            Log::error('Exception updating employee in TrackTik', [
                'message' => $e->getMessage(),
                'employee_id' => $employeeId,
                'employee_data' => $employeeData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get employee from TrackTik
     *
     * @param string $employeeId
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function getEmployee(string $employeeId): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/employees/{$employeeId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Employee not found',
            ];
        } catch (\Exception $e) {
            Log::error('Exception getting employee from TrackTik', [
                'message' => $e->getMessage(),
                'employee_id' => $employeeId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}


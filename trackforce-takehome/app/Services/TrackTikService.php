<?php

namespace App\Services;

use App\Contracts\TrackTikServiceInterface;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Domain\DataTransferObjects\TrackTikResponse;
use App\Services\Auth\OAuth2TokenManager;
use App\Services\TrackTik\TrackTikApiClient;

class TrackTikService implements TrackTikServiceInterface
{
    public function __construct(
        private TrackTikApiClient $apiClient
    ) {}

    /**
     * Create employee in TrackTik
     *
     * @param TrackTikEmployeeData $employeeData
     * @return TrackTikResponse
     */
    public function createEmployee(TrackTikEmployeeData $employeeData): TrackTikResponse
    {
        return $this->apiClient->post('/employees', $employeeData->toArray());
    }

    /**
     * Update employee in TrackTik
     *
     * @param string $employeeId
     * @param TrackTikEmployeeData $employeeData
     * @return TrackTikResponse
     */
    public function updateEmployee(string $employeeId, TrackTikEmployeeData $employeeData): TrackTikResponse
    {
        return $this->apiClient->put("/employees/{$employeeId}", $employeeData->toArray());
    }

    /**
     * Get employee from TrackTik
     *
     * @param string $employeeId
     * @return TrackTikResponse
     */
    public function getEmployee(string $employeeId): TrackTikResponse
    {
        return $this->apiClient->get("/employees/{$employeeId}");
    }
}


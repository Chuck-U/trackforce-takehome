<?php

namespace App\Services\Employee;

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\TrackTikServiceInterface;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeProcessingService
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private TrackTikServiceInterface $trackTikService
    ) {}

    /**
     * Process employee data (create or update)
     *
     * @param string $provider
     * @param string $employeeId
     * @param TrackTikEmployeeData $trackTikData
     * @param array $employeeData
     * @return array{success: bool, data?: array, error?: array}
     */
    public function processEmployee(
        string $provider,
        string $employeeId,
        TrackTikEmployeeData $trackTikData,
        array $employeeData
    ): array {
        return DB::transaction(function () use ($provider, $employeeId, $trackTikData, $employeeData) {
            // Check if employee already exists
            $employee = $this->employeeRepository->findByProviderAndId($provider, $employeeId);
            $isUpdate = $employee !== null;

            // Forward to TrackTik API
            if ($isUpdate && $employee->tracktik_id) {
                $trackTikResponse = $this->trackTikService->updateEmployee(
                    $employee->tracktik_id,
                    $trackTikData
                );
            } else {
                $trackTikResponse = $this->trackTikService->createEmployee($trackTikData);
            }

            if (!$trackTikResponse->success) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'TRACKTIK_ERROR',
                        'message' => $trackTikResponse->error,
                    ],
                ];
            }

            // Update employee data with TrackTik response
            $employeeData['tracktik_id'] = $trackTikResponse->data['id'] ?? $trackTikResponse->data['employeeId'] ?? null;

            // Store/update in local database
            if ($isUpdate) {
                $employee = $this->employeeRepository->update($employee, $employeeData);
            } else {
                $employee = $this->employeeRepository->create($employeeData);
            }

            Log::info("{$provider} employee processed", [
                'employee_id' => $employee->employee_id,
                'action' => $isUpdate ? 'updated' : 'created',
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $employee->id,
                    'employeeId' => $employee->employee_id,
                    'provider' => $employee->provider,
                    'tracktikId' => $employee->tracktik_id,
                    'message' => $isUpdate ? 'Employee updated successfully' : 'Employee created successfully',
                ],
                'isUpdate' => $isUpdate,
            ];
        });
    }
}

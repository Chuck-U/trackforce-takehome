<?php

namespace App\Services\Employee;

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\TrackTikServiceInterface;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Domain\DataTransferObjects\TrackTikResponse;
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
     * @return array{response: TrackTikResponse, isUpdate: bool}
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
                    'response' => TrackTikResponse::error($trackTikResponse->error ?? 'TrackTik API error'),
                    'isUpdate' => $isUpdate,
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
                'response' => TrackTikResponse::success([
                    'id' => $employee->id,
                    'employeeId' => $employee->employee_id,
                    'provider' => $employee->provider,
                    'tracktikId' => $employee->tracktik_id,
                    'message' => $isUpdate ? 'Employee updated successfully' : 'Employee created successfully',
                ]),
                'isUpdate' => $isUpdate,
            ];
        });
    }

    /**
     * Retrieve employee data (local and optionally TrackTik)
     *
     * @param string $provider
     * @param string $employeeId
     * @return TrackTikResponse
     */
    public function getEmployee(string $provider, string $employeeId): TrackTikResponse
    {
        try {
            $employee = $this->employeeRepository->findByProviderAndId($provider, $employeeId);
            if (!$employee) {
                return TrackTikResponse::error('Employee not found');
            }

            $tracktikData = null;
            if (!empty($employee->tracktik_id)) {
                $trackTikResponse = $this->trackTikService->getEmployee($employee->tracktik_id);
                if ($trackTikResponse->success) {
                    $tracktikData = $trackTikResponse->data;
                }
            }

            return TrackTikResponse::success([
                'id' => $employee->id,
                'employeeId' => $employee->employee_id,
                'provider' => $employee->provider,
                'tracktikId' => $employee->tracktik_id,
                'tracktik' => $tracktikData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee', [
                'provider' => $provider,
                'employeeId' => $employeeId,
                'message' => $e->getMessage(),
            ]);
            return TrackTikResponse::error('Unable to retrieve employee');
        }
    }
}

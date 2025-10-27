<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider2EmployeeRequest;
use App\Models\Employee;
use App\Services\Provider2EmployeeMapper;
use App\Services\TrackTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Provider2EmployeeController extends Controller
{
    public function __construct(
        private Provider2EmployeeMapper $mapper,
        private TrackTikService $trackTikService
    ) {}

    /**
     * Create or update employee from Provider 2
     */
    public function store(Provider2EmployeeRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $validatedData = $request->validated();
                
                // Map to TrackTik schema
                $trackTikData = $this->mapper->mapToTrackTik($validatedData);

                // Check if employee already exists
                $employee = Employee::where('employee_id', $validatedData['employee_number'])
                    ->where('provider', 'provider2')
                    ->first();

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

                if (!$trackTikResponse['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'TRACKTIK_ERROR',
                            'message' => $trackTikResponse['error'],
                        ],
                    ], 500);
                }

                $personalInfo = $validatedData['personal_info'];
                $workInfo = $validatedData['work_info'];

                // Store/update in local database
                $employeeData = [
                    'employee_id' => $validatedData['employee_number'],
                    'provider' => 'provider2',
                    'first_name' => $personalInfo['given_name'],
                    'last_name' => $personalInfo['family_name'],
                    'email' => $personalInfo['email'],
                    'phone_number' => $personalInfo['mobile'] ?? null,
                    'position' => $workInfo['role'] ?? null,
                    'department' => $workInfo['division'] ?? null,
                    'start_date' => $workInfo['start_date'] ?? null,
                    'status' => $this->mapStatus($workInfo['current_status'] ?? 'employed'),
                    'tracktik_id' => $trackTikResponse['data']['id'] ?? $trackTikResponse['data']['employeeId'] ?? null,
                    'provider_data' => $validatedData,
                ];

                if ($isUpdate) {
                    $employee->update($employeeData);
                } else {
                    $employee = Employee::create($employeeData);
                }

                Log::info('Provider 2 employee processed', [
                    'employee_id' => $employee->employee_id,
                    'action' => $isUpdate ? 'updated' : 'created',
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $employee->id,
                        'employeeId' => $employee->employee_id,
                        'provider' => $employee->provider,
                        'tracktikId' => $employee->tracktik_id,
                        'message' => $isUpdate ? 'Employee updated successfully' : 'Employee created successfully',
                    ],
                ], $isUpdate ? 200 : 201);
            });
        } catch (\Exception $e) {
            Log::error('Error processing Provider 2 employee', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An error occurred while processing the employee data',
                ],
            ], 500);
        }
    }

    /**
     * Map provider status to database status
     */
    private function mapStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'employed' => 'active',
            'terminated' => 'terminated',
            'on_leave' => 'inactive',
            default => 'active',
        };
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider1EmployeeRequest;
use App\Models\Employee;
use App\Services\Provider1EmployeeMapper;
use App\Services\TrackTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Provider1EmployeeController extends Controller
{
    public function __construct(
        private Provider1EmployeeMapper $mapper,
        private TrackTikService $trackTikService
    ) {}

    /**
     * Create or update employee from Provider 1
     */
    public function store(Provider1EmployeeRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $validatedData = $request->validated();
                
                // Map to TrackTik schema
                $trackTikData = $this->mapper->mapToTrackTik($validatedData);

                // Check if employee already exists
                $employee = Employee::where('employee_id', $validatedData['emp_id'])
                    ->where('provider', 'provider1')
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

                // Store/update in local database
                $employeeData = [
                    'employee_id' => $validatedData['emp_id'],
                    'provider' => 'provider1',
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'email' => $validatedData['email_address'],
                    'phone_number' => $validatedData['phone'] ?? null,
                    'position' => $validatedData['job_title'] ?? null,
                    'department' => $validatedData['dept'] ?? null,
                    'start_date' => $validatedData['hire_date'] ?? null,
                    'status' => $validatedData['employment_status'] ?? 'active',
                    'tracktik_id' => $trackTikResponse['data']['id'] ?? $trackTikResponse['data']['employeeId'] ?? null,
                    'provider_data' => $validatedData,
                ];

                if ($isUpdate) {
                    $employee->update($employeeData);
                } else {
                    $employee = Employee::create($employeeData);
                }

                Log::info('Provider 1 employee processed', [
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
            Log::error('Error processing Provider 1 employee', [
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
}


<?php

namespace App\Http\Controllers\Api;

use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider1EmployeeRequest;
use App\Models\Employee;
use App\Services\Provider1EmployeeMapper;
use App\Services\TrackTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;


/**
 * Controller for handling employee data from Provider 1
 */
#[OA\Tag(
    name: "Provider 1",
    description: "Employee data endpoints for Provider 1"
)]
class Provider1EmployeeController extends Controller
{
    /**
     * Constructor for the Provider1EmployeeController
     *
     * @param Provider1EmployeeMapper $mapper Mapper for mapping employee data to TrackTik schema
     * @param TrackTikService $trackTikService Service for interacting with TrackTik API
     */
    public function __construct(
        private Provider1EmployeeMapper $mapper,
        private TrackTikService $trackTikService
    ) {}

    /**
     * Create or update employee from Provider 1
     */
    #[OA\Post(
        path: "/provider1/employees",
        summary: "Create or update an employee from Provider 1",
        tags: ["Provider 1"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["emp_id", "first_name", "last_name", "email_address"],
                properties: [
                    new OA\Property(property: "emp_id", type: "string", example: "EMP001"),
                    new OA\Property(property: "first_name", type: "string", example: "John"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                    new OA\Property(property: "email_address", type: "string", format: "email", example: "john.doe@example.com"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "555-1234"),
                    new OA\Property(property: "job_title", type: "string", nullable: true, example: "Security Officer"),
                    new OA\Property(property: "dept", type: "string", nullable: true, example: "Security"),
                    new OA\Property(property: "hire_date", type: "string", format: "date", nullable: true, example: "2024-01-15"),
                    new OA\Property(property: "employment_status", type: "string", enum: ["active", "inactive", "terminated"], nullable: true, example: "active")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Employee created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "employeeId", type: "string", example: "EMP001"),
                                new OA\Property(property: "provider", type: "string", example: "provider1"),
                                new OA\Property(property: "tracktikId", type: "string", example: "tt-12345"),
                                new OA\Property(property: "message", type: "string", example: "Employee created successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: "Employee updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "employeeId", type: "string", example: "EMP001"),
                                new OA\Property(property: "provider", type: "string", example: "provider1"),
                                new OA\Property(property: "tracktikId", type: "string", example: "tt-12345"),
                                new OA\Property(property: "message", type: "string", example: "Employee updated successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(
                            property: "error",
                            properties: [
                                new OA\Property(property: "code", type: "string", example: "VALIDATION_ERROR"),
                                new OA\Property(property: "message", type: "string", example: "Invalid employee data"),
                                new OA\Property(
                                    property: "details",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "field", type: "string"),
                                            new OA\Property(property: "message", type: "string")
                                        ],
                                        type: "object"
                                    )
                                )
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(
                            property: "error",
                            properties: [
                                new OA\Property(property: "code", type: "string", example: "INTERNAL_ERROR"),
                                new OA\Property(property: "message", type: "string", example: "An error occurred while processing the employee data")
                            ],
                            type: "object"
                        )
                    ]
                )
            )
        ]
    )]
    public function store(Provider1EmployeeRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $validatedData = $request->validated();
                
                // Create DTO from validated data
                $providerData = Provider1EmployeeData::fromArray($validatedData);
                
                // Map to TrackTik schema
                $trackTikData = $this->mapper->mapToTrackTik($providerData);

                // Check if employee already exists
                $employee = Employee::where('employee_id', $validatedData['emp_id'])
                    ->where('provider', 'provider1')
                    ->first();

                $isUpdate = $employee !== null;

                // Forward to TrackTik API
                if ($isUpdate && $employee->tracktik_id) {
                    $trackTikResponse = $this->trackTikService->updateEmployee(
                        $employee->tracktik_id,
                        $trackTikData->toArray()
                    );
                } else {
                    $trackTikResponse = $this->trackTikService->createEmployee($trackTikData->toArray());
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


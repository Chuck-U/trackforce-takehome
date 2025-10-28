<?php

namespace App\Http\Controllers\Api;

use App\Domain\DataTransferObjects\Provider2EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider2EmployeeRequest;
use App\Models\Employee;
use App\Services\Provider2EmployeeMapper;
use App\Services\TrackTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Provider 2",
    description: "Employee data endpoints for Provider 2"
)]
class Provider2EmployeeController extends Controller
{
    public function __construct(
        private Provider2EmployeeMapper $mapper,
        private TrackTikService $trackTikService
    ) {}

    /**
     * Create or update employee from Provider 2
     */
    #[OA\Post(
        path: "/provider2/employees",
        summary: "Create or update an employee from Provider 2",
        tags: ["Provider 2"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["employee_number", "personal_info", "work_info"],
                properties: [
                    new OA\Property(property: "employee_number", type: "string", example: "EMP2001"),
                    new OA\Property(
                        property: "personal_info",
                        properties: [
                            new OA\Property(property: "given_name", type: "string", example: "Jane"),
                            new OA\Property(property: "family_name", type: "string", example: "Smith"),
                            new OA\Property(property: "email", type: "string", format: "email", example: "jane.smith@example.com"),
                            new OA\Property(property: "mobile", type: "string", nullable: true, example: "555-5678")
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "work_info",
                        properties: [
                            new OA\Property(property: "role", type: "string", nullable: true, example: "Security Guard"),
                            new OA\Property(property: "division", type: "string", nullable: true, example: "Operations"),
                            new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, example: "2024-02-01"),
                            new OA\Property(property: "current_status", type: "string", enum: ["employed", "terminated", "on_leave"], nullable: true, example: "employed")
                        ],
                        type: "object"
                    )
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
                                new OA\Property(property: "id", type: "integer", example: 2),
                                new OA\Property(property: "employeeId", type: "string", example: "EMP2001"),
                                new OA\Property(property: "provider", type: "string", example: "provider2"),
                                new OA\Property(property: "tracktikId", type: "string", example: "tt-67890"),
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
                                new OA\Property(property: "id", type: "integer", example: 2),
                                new OA\Property(property: "employeeId", type: "string", example: "EMP2001"),
                                new OA\Property(property: "provider", type: "string", example: "provider2"),
                                new OA\Property(property: "tracktikId", type: "string", example: "tt-67890"),
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
    public function store(Provider2EmployeeRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $validatedData = $request->validated();
                
                // Create DTO from validated data
                $providerData = Provider2EmployeeData::fromArray($validatedData);
                
                // Map to TrackTik schema
                $trackTikData = $this->mapper->mapToTrackTik($providerData);

                // Check if employee already exists
                $employee = Employee::where('employee_id', $validatedData['employee_number'])
                    ->where('provider', 'provider2')
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


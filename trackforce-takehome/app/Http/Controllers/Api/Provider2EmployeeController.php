<?php

namespace App\Http\Controllers\Api;

use App\Domain\DataTransferObjects\Provider2EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider2EmployeeRequest;
use App\Http\Responses\ApiResponseFactory;
use App\Services\Employee\EmployeeProcessingService;
use App\Services\Provider2EmployeeMapper;
use App\Services\Mapping\StatusMapperInterface;
use Illuminate\Http\JsonResponse;
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
        private EmployeeProcessingService $processingService,
        private StatusMapperInterface $statusMapper
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
            $validatedData = $request->validated();
            
            // Create DTO from validated data
            $providerData = Provider2EmployeeData::fromArray($validatedData);
            
            // Map to TrackTik schema
            $trackTikData = $this->mapper->mapToTrackTik($providerData);

            $personalInfo = $validatedData['personal_info'];
            $workInfo = $validatedData['work_info'];

            // Prepare employee data for database
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
                'status' => $this->statusMapper->mapToDatabase($workInfo['current_status'] ?? 'employed'),
                'provider_data' => $validatedData,
            ];

            // Process employee
            $result = $this->processingService->processEmployee(
                'provider2',
                $validatedData['employee_number'],
                $trackTikData,
                $employeeData
            );

            if (!$result['success']) {
                return ApiResponseFactory::trackTikError($result['error']['message']);
            }

            return ApiResponseFactory::success($result['data'], $result['isUpdate'] ? 200 : 201);
        } catch (\Exception $e) {
            Log::error('Error processing Provider 2 employee', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponseFactory::internalError();
        }
    }
}


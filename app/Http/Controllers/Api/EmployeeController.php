<?php

namespace App\Http\Controllers\Api;

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\TrackTikServiceInterface;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Exceptions\InvalidProviderException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponseFactory;
use App\Services\Employee\EmployeeProcessingService;
use App\Services\Provider\ProviderResolver;
use App\Services\Provider\ProviderValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Employees",
    description: "Route for CRUD operations on employees"
)]
class EmployeeController extends Controller
{
    public function __construct(
        private ProviderResolver $providerResolver,
        private EmployeeProcessingService $processingService,
        private EmployeeRepositoryInterface $employeeRepository,
        private TrackTikServiceInterface $trackTikService
    ) {
    }

    #[OA\Get(
        path: "/{provider}/employees/{employee_id}",
        summary: "Get employee by provider and employee ID",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "provider",
                in: "path",
                required: true,
                description: "Provider identifier (e.g., provider1, provider2)",
                schema: new OA\Schema(type: "string", example: "provider1")
            ),
            new OA\Parameter(
                name: "employee_id",
                in: "path",
                required: true,
                description: "Provider-specific employee ID",
                schema: new OA\Schema(type: "string", example: "EMP001")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employee retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "employeeId", type: "string", example: "EMP001"),
                                new OA\Property(property: "provider", type: "string", example: "provider1"),
                                new OA\Property(property: "tracktikId", type: "string", nullable: true, example: "tt-12345"),
                                new OA\Property(property: "tracktik", type: "object", nullable: true)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Employee not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(
                            property: "error",
                            properties: [
                                new OA\Property(property: "code", type: "string", example: "NOT_FOUND"),
                                new OA\Property(property: "message", type: "string", example: "Employee not found")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid provider",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(
                            property: "error",
                            properties: [
                                new OA\Property(property: "code", type: "string", example: "INVALID_PROVIDER"),
                                new OA\Property(property: "message", type: "string")
                            ],
                            type: "object"
                        )
                    ]
                )
            )
        ]
    )]
    public function showEmployeeById(Request $request, string $provider, string $employee_id): JsonResponse
    {
        try {
            $this->providerResolver->validateProvider($provider);

            $response = $this->processingService->getEmployee(
                $this->providerResolver->normalizeProvider($provider),
                $employee_id
            );

            if (!$response->success) {
                return ApiResponseFactory::error([
                    'code' => 'NOT_FOUND',
                    'message' => $response->error,
                ], 404);
            }

            return ApiResponseFactory::fromTrackTikResponse($response);
        } catch (InvalidProviderException $e) {
            return ApiResponseFactory::error([
                'code' => 'INVALID_PROVIDER',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponseFactory::internalError();
        }
    }

    #[OA\Post(
        path: "/{provider}/employees",
        summary: "Create an employee for given provider",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "provider",
                in: "path",
                required: true,
                description: "Provider identifier (e.g., provider1, provider2)",
                schema: new OA\Schema(type: "string", example: "provider1")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Employee data - schema varies by provider. Provider 1 uses flat structure, Provider 2 uses nested structure.",
            content: [
                "application/json" => new OA\MediaType(
                    mediaType: "application/json",
                    examples: [
                        "provider1" => new OA\Examples(
                            example: "provider1",
                            summary: "Provider 1 Schema (Flat Structure)",
                            value: [
                                "emp_id" => "EMP001",
                                "first_name" => "John",
                                "last_name" => "Doe",
                                "email_address" => "john.doe@example.com",
                                "phone" => "+1-555-0101",
                                "job_title" => "Security Officer",
                                "dept" => "Security Operations",
                                "hire_date" => "2024-01-15",
                                "employment_status" => "active"
                            ]
                        ),
                        "provider2" => new OA\Examples(
                            example: "provider2",
                            summary: "Provider 2 Schema (Nested Structure)",
                            value: [
                                "employee_number" => "EMP2001",
                                "personal_info" => [
                                    "given_name" => "Jane",
                                    "family_name" => "Smith",
                                    "email" => "jane.smith@example.com",
                                    "mobile" => "+1-555-0201"
                                ],
                                "work_info" => [
                                    "role" => "Security Guard",
                                    "division" => "Operations",
                                    "start_date" => "2024-02-01",
                                    "current_status" => "employed"
                                ]
                            ]
                        )
                    ],
                    schema: new OA\Schema(
                        type: "object",
                        oneOf: [
                            new OA\Schema(
                                title: "Provider1Schema",
                                required: ["emp_id", "first_name", "last_name", "email_address"],
                                properties: [
                                    new OA\Property(property: "emp_id", type: "string", description: "Employee ID"),
                                    new OA\Property(property: "first_name", type: "string", description: "First name"),
                                    new OA\Property(property: "last_name", type: "string", description: "Last name"),
                                    new OA\Property(property: "email_address", type: "string", format: "email", description: "Email address"),
                                    new OA\Property(property: "phone", type: "string", nullable: true, description: "Phone number"),
                                    new OA\Property(property: "job_title", type: "string", nullable: true, description: "Job title"),
                                    new OA\Property(property: "dept", type: "string", nullable: true, description: "Department"),
                                    new OA\Property(property: "hire_date", type: "string", format: "date", nullable: true, description: "Hire date"),
                                    new OA\Property(property: "employment_status", type: "string", enum: ["active", "inactive", "terminated"], nullable: true, description: "Employment status")
                                ]
                            ),
                            new OA\Schema(
                                title: "Provider2Schema",
                                required: ["employee_number", "personal_info", "work_info"],
                                properties: [
                                    new OA\Property(property: "employee_number", type: "string", description: "Employee number"),
                                    new OA\Property(
                                        property: "personal_info",
                                        type: "object",
                                        required: ["given_name", "family_name", "email"],
                                        properties: [
                                            new OA\Property(property: "given_name", type: "string", description: "Given name"),
                                            new OA\Property(property: "family_name", type: "string", description: "Family name"),
                                            new OA\Property(property: "email", type: "string", format: "email", description: "Email"),
                                            new OA\Property(property: "mobile", type: "string", nullable: true, description: "Mobile number")
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "work_info",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "role", type: "string", nullable: true, description: "Job role"),
                                            new OA\Property(property: "division", type: "string", nullable: true, description: "Division"),
                                            new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, description: "Start date"),
                                            new OA\Property(property: "current_status", type: "string", enum: ["employed", "terminated", "on_leave"], nullable: true, description: "Current employment status")
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                )
            ]
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
                                new OA\Property(property: "message", type: "string", example: "Employee updated successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error or invalid provider",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "error", type: "object")
                    ]
                )
            )
        ]
    )]
    public function addEmployeeById(Request $request, string $provider): JsonResponse
    {
        try {
            $this->providerResolver->validateProvider($provider);
            $rules = ProviderValidationRules::rulesFor($provider);
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $details = collect($validator->errors()->messages())
                    ->map(fn($messages, $field) => [
                        'field' => $field,
                        'message' => $messages[0],
                    ])
                    ->values()
                    ->all();
                return ApiResponseFactory::validationError($details);
            }
            $validated = $validator->validated();

            $providerDto = $this->providerResolver->createDtoFromArray($provider, $validated);
            $mapper = $this->providerResolver->resolveMapper($provider);
            /** @var TrackTikEmployeeData $trackTikData */
            $trackTikData = $mapper->mapToTrackTik($providerDto);

            $employeeData = $this->providerResolver->prepareEmployeeData($provider, $validated);
            $employeeId = $this->providerResolver->extractEmployeeId($provider, $validated);

            $result = $this->processingService->processEmployee(
                $this->providerResolver->normalizeProvider($provider),
                $employeeId,
                $trackTikData,
                $employeeData
            );

            return ApiResponseFactory::fromTrackTikResponse(
                $result['response'],
                $result['isUpdate'] ? 200 : 201
            );
        } catch (InvalidProviderException $e) {
            return ApiResponseFactory::error([
                'code' => 'INVALID_PROVIDER',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating employee', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponseFactory::internalError();
        }
    }

    #[OA\Put(
        path: "/{provider}/employees/{employee_id}",
        summary: "Update an employee for given provider",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "provider",
                in: "path",
                required: true,
                description: "Provider identifier (e.g., provider1, provider2)",
                schema: new OA\Schema(type: "string", example: "provider1")
            ),
            new OA\Parameter(
                name: "employee_id",
                in: "path",
                required: true,
                description: "Provider-specific employee ID",
                schema: new OA\Schema(type: "string", example: "EMP001")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Updated employee data - schema varies by provider. Must match the employee_id in the URL path.",
            content: [
                "application/json" => new OA\MediaType(
                    mediaType: "application/json",
                    examples: [
                        "provider1" => new OA\Examples(
                            example: "provider1",
                            summary: "Provider 1 Update Schema",
                            value: [
                                "emp_id" => "EMP001",
                                "first_name" => "John",
                                "last_name" => "Doe",
                                "email_address" => "john.doe.updated@example.com",
                                "phone" => "+1-555-0199",
                                "job_title" => "Senior Security Officer",
                                "dept" => "Security Operations",
                                "hire_date" => "2024-01-15",
                                "employment_status" => "active"
                            ]
                        ),
                        "provider2" => new OA\Examples(
                            example: "provider2",
                            summary: "Provider 2 Update Schema",
                            value: [
                                "employee_number" => "EMP2001",
                                "personal_info" => [
                                    "given_name" => "Jane",
                                    "family_name" => "Smith",
                                    "email" => "jane.smith.updated@example.com",
                                    "mobile" => "+1-555-0299"
                                ],
                                "work_info" => [
                                    "role" => "Lead Security Guard",
                                    "division" => "Operations",
                                    "start_date" => "2024-02-01",
                                    "current_status" => "employed"
                                ]
                            ]
                        )
                    ],
                    schema: new OA\Schema(
                        type: "object",
                        oneOf: [
                            new OA\Schema(
                                title: "Provider1UpdateSchema",
                                required: ["emp_id", "first_name", "last_name", "email_address"],
                                properties: [
                                    new OA\Property(property: "emp_id", type: "string", description: "Employee ID (must match URL path)"),
                                    new OA\Property(property: "first_name", type: "string", description: "First name"),
                                    new OA\Property(property: "last_name", type: "string", description: "Last name"),
                                    new OA\Property(property: "email_address", type: "string", format: "email", description: "Email address"),
                                    new OA\Property(property: "phone", type: "string", nullable: true, description: "Phone number"),
                                    new OA\Property(property: "job_title", type: "string", nullable: true, description: "Job title"),
                                    new OA\Property(property: "dept", type: "string", nullable: true, description: "Department"),
                                    new OA\Property(property: "hire_date", type: "string", format: "date", nullable: true, description: "Hire date"),
                                    new OA\Property(property: "employment_status", type: "string", enum: ["active", "inactive", "terminated"], nullable: true, description: "Employment status")
                                ]
                            ),
                            new OA\Schema(
                                title: "Provider2UpdateSchema",
                                required: ["employee_number", "personal_info", "work_info"],
                                properties: [
                                    new OA\Property(property: "employee_number", type: "string", description: "Employee number (must match URL path)"),
                                    new OA\Property(
                                        property: "personal_info",
                                        type: "object",
                                        required: ["given_name", "family_name", "email"],
                                        properties: [
                                            new OA\Property(property: "given_name", type: "string", description: "Given name"),
                                            new OA\Property(property: "family_name", type: "string", description: "Family name"),
                                            new OA\Property(property: "email", type: "string", format: "email", description: "Email"),
                                            new OA\Property(property: "mobile", type: "string", nullable: true, description: "Mobile number")
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "work_info",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "role", type: "string", nullable: true, description: "Job role"),
                                            new OA\Property(property: "division", type: "string", nullable: true, description: "Division"),
                                            new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, description: "Start date"),
                                            new OA\Property(property: "current_status", type: "string", enum: ["employed", "terminated", "on_leave"], nullable: true, description: "Current employment status")
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                )
            ]
        ),
        responses: [
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
                response: 404,
                description: "Employee not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(
                            property: "error",
                            properties: [
                                new OA\Property(property: "code", type: "string", example: "NOT_FOUND"),
                                new OA\Property(property: "message", type: "string", example: "Employee not found")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error, invalid provider, or mismatched employee ID",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "error", type: "object")
                    ]
                )
            )
        ]
    )]
    public function updateEmployeeById(Request $request, string $provider, string $employee_id): JsonResponse
    {
        try {
            $this->providerResolver->validateProvider($provider);
            $rules = ProviderValidationRules::rulesFor($provider);
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $details = collect($validator->errors()->messages())
                    ->map(fn($messages, $field) => [
                        'field' => $field,
                        'message' => $messages[0],
                    ])
                    ->values()
                    ->all();
                return ApiResponseFactory::validationError($details);
            }
            $validated = $validator->validated();

            $extractedId = $this->providerResolver->extractEmployeeId($provider, $validated);
            if ($extractedId !== $employee_id) {
                return ApiResponseFactory::error([
                    'code' => 'MISMATCHED_EMPLOYEE_ID',
                    'message' => 'Path employee_id does not match payload identifier',
                ], 400);
            }

            $existing = $this->employeeRepository->findByProviderAndId(
                $this->providerResolver->normalizeProvider($provider),
                $employee_id
            );
            if (!$existing) {
                return ApiResponseFactory::error([
                    'code' => 'NOT_FOUND',
                    'message' => 'Employee not found',
                ], 404);
            }

            $providerDto = $this->providerResolver->createDtoFromArray($provider, $validated);
            $mapper = $this->providerResolver->resolveMapper($provider);
            /** @var TrackTikEmployeeData $trackTikData */
            $trackTikData = $mapper->mapToTrackTik($providerDto);

            $employeeData = $this->providerResolver->prepareEmployeeData($provider, $validated);

            $result = $this->processingService->processEmployee(
                $this->providerResolver->normalizeProvider($provider),
                $employee_id,
                $trackTikData,
                $employeeData
            );

            return ApiResponseFactory::fromTrackTikResponse($result['response'], 200);
        } catch (InvalidProviderException $e) {
            return ApiResponseFactory::error([
                'code' => 'INVALID_PROVIDER',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error updating employee', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponseFactory::internalError();
        }
    }
}



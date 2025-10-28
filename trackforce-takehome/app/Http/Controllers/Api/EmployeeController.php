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
            description: "Employee information in the TrackTik employee schema",
            content: new OA\JsonContent(
                required: ["emp_id", "first_name", "last_name", "email_address", "phone", "job_title", "dept", "hire_date", "employment_status"],
                properties: [
                    new OA\Property(property: "emp_id", type: "string", example: "EMP001"),
                    new OA\Property(property: "first_name", type: "string", example: "Alice"),
                    new OA\Property(property: "last_name", type: "string", example: "Smith"),
                    new OA\Property(property: "email_address", type: "string", format: "email", example: "alice.smith@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "+14255550101"),
                    new OA\Property(property: "job_title", type: "string", example: "Security Officer"),
                    new OA\Property(property: "dept", type: "string", example: "Operations"),
                    new OA\Property(property: "hire_date", type: "string", format: "date", example: "2025-01-01"),
                    new OA\Property(property: "employment_status", type: "string", enum: ["active", "inactive", "terminated"], example: "active")
                ],
                type: "object"
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
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Employee information in the TrackTik employee schema",
            content: new OA\JsonContent(
                required: ["emp_id", "first_name", "last_name", "email_address", "phone", "job_title", "dept", "hire_date", "employment_status"],
                properties: [
                    new OA\Property(property: "emp_id", type: "string", example: "EMP001"),
                    new OA\Property(property: "first_name", type: "string", example: "Alice"),
                    new OA\Property(property: "last_name", type: "string", example: "Smith"),
                    new OA\Property(property: "email_address", type: "string", format: "email", example: "alice.smith@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "+14255550101"),
                    new OA\Property(property: "job_title", type: "string", example: "Security Officer"),
                    new OA\Property(property: "dept", type: "string", example: "Operations"),
                    new OA\Property(property: "hire_date", type: "string", format: "date", example: "2025-01-01"),
                    new OA\Property(property: "employment_status", type: "string", enum: ["active", "inactive", "terminated"], example: "active")
                ],
                type: "object"
            )
        )
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



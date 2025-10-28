<?php

use App\Models\Employee;
use App\Services\TrackTikService;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

// Helper function to get auth headers
function authHeaders2(): array {
    return ['Authorization' => 'Bearer test-provider-token-12345'];
}

beforeEach(function () {
    // Mock TrackTik API responses with OAuth authentication flow
    Http::fake(function ($request) {
        $uri = $request->url();
        
        // OAuth2 token endpoint - must be called before any TrackTik API call
        if (str_contains($uri, '/oauth/token')) {
            return Http::response([
                'access_token' => 'fake-access-token-12345',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'employees:read employees:write',
            ], 200);
        }
        
        // Employee endpoints - require Bearer token authentication
        if (str_contains($uri, '/employees')) {
            // Verify that the request has a valid Bearer token
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader[0], 'Bearer fake-access-token')) {
                return Http::response([
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Invalid or missing authentication token',
                    ],
                ], 401);
            }

            $body = json_decode($request->body(), true);
            $employeeId = $body['employeeId'] ?? uniqid();
            
            $isUpdate = preg_match('/\/employees\/[^\/]+$/', $uri);
            
            return Http::response([
                'success' => true,
                'data' => [
                    'id' => 'tracktik-uuid-' . md5($employeeId),
                    'employeeId' => $employeeId,
                    'firstName' => $body['firstName'] ?? 'Test',
                    'lastName' => $body['lastName'] ?? 'User',
                    'email' => $body['email'] ?? 'test@provider2.com',
                ],
            ], $isUpdate ? 200 : 201);
        }
        
        return Http::response([], 404);
    });
});

test('can create employee with valid Provider 2 data', function () {
    $employeeData = [
        'employee_number' => 'P2_001',
        'personal_info' => [
            'given_name' => 'Carol',
            'family_name' => 'Davis',
            'email' => 'carol.davis@provider2.com',
            'mobile' => '+1-555-0201',
        ],
        'work_info' => [
            'role' => 'Security Guard',
            'division' => 'Night Shift Security',
            'start_date' => '2024-02-01',
            'current_status' => 'employed',
        ],
    ];

    $response = postJson('/api/provider2/employees', $employeeData, authHeaders2());

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'employeeId' => 'P2_001',
                'provider' => 'provider2',
            ],
        ]);

    expect(Employee::where('employee_id', 'P2_001')->exists())->toBeTrue();
});

test('can update existing Provider 2 employee', function () {
    // Create initial employee
    Employee::create([
        'employee_id' => 'P2_002',
        'provider' => 'provider2',
        'first_name' => 'David',
        'last_name' => 'Wilson',
        'email' => 'david.wilson@provider2.com',
        'status' => 'active',
        'tracktik_id' => 'tracktik-uuid-999',
    ]);

    $employeeData = [
        'employee_number' => 'P2_002',
        'personal_info' => [
            'given_name' => 'Dave',
            'family_name' => 'Wilson',
            'email' => 'dave.wilson@provider2.com',
        ],
        'work_info' => [
            'role' => 'Team Lead',
            'division' => 'Day Shift Security',
            'current_status' => 'employed',
        ],
    ];

    $response = postJson('/api/provider2/employees', $employeeData, authHeaders2());

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $employee = Employee::where('employee_id', 'P2_002')->first();
    expect($employee->first_name)->toBe('Dave');
});

test('validates required fields for Provider 2', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'P2_003',
        // Missing required fields
    ], authHeaders2());

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid employee data',
            ],
        ]);
});

test('validates email format for Provider 2', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'P2_004',
        'personal_info' => [
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'invalid-email',
        ],
        'work_info' => [
            'current_status' => 'employed',
        ],
    ], authHeaders2());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('validates current status enum for Provider 2', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'P2_005',
        'personal_info' => [
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'test@example.com',
        ],
        'work_info' => [
            'current_status' => 'invalid_status',
        ],
    ], authHeaders2());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('correctly maps Provider 2 status to system status', function () {
    $testCases = [
        ['employed', 'active'],
        ['terminated', 'terminated'],
        ['on_leave', 'inactive'],
    ];

    foreach ($testCases as $index => [$providerStatus, $expectedStatus]) {
        $employeeData = [
            'employee_number' => "P2_STATUS_{$index}",
            'personal_info' => [
                'given_name' => 'Test',
                'family_name' => 'User',
                'email' => "test_status_{$index}@example.com",
                'mobile' => '+1-555-0100',
            ],
            'work_info' => [
                'role' => 'Security Guard',
                'division' => 'Test Division',
                'start_date' => '2024-01-01',
                'current_status' => $providerStatus,
            ],
        ];

        $response = postJson('/api/provider2/employees', $employeeData, authHeaders2());
        $response->assertSuccessful();

        $employee = Employee::where('employee_id', "P2_STATUS_{$index}")->first();
        expect($employee)->not->toBeNull()
            ->and($employee->status)->toBe($expectedStatus);
    }
});

test('stores provider data in employee record for Provider 2', function () {
    $employeeData = [
        'employee_number' => 'P2_006',
        'personal_info' => [
            'given_name' => 'Emily',
            'family_name' => 'Brown',
            'email' => 'emily@provider2.com',
        ],
        'work_info' => [
            'role' => 'Officer',
        ],
    ];

    postJson('/api/provider2/employees', $employeeData, authHeaders2());

    $employee = Employee::where('employee_id', 'P2_006')->first();
    expect($employee->provider_data)->toBeArray()
        ->and($employee->provider_data['employee_number'])->toBe('P2_006');
});

test('authenticates with OAuth before calling TrackTik API', function () {
    $employeeData = [
        'employee_number' => 'P2_007',
        'personal_info' => [
            'given_name' => 'Frank',
            'family_name' => 'Garcia',
            'email' => 'frank.garcia@provider2.com',
            'mobile' => '+1-555-0207',
        ],
        'work_info' => [
            'role' => 'Supervisor',
            'division' => 'Security Operations',
            'current_status' => 'employed',
        ],
    ];

    $response = postJson('/api/provider2/employees', $employeeData, authHeaders2());

    $response->assertStatus(201);

    // Verify OAuth token endpoint was called
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/oauth/token') &&
               $request['grant_type'] === 'client_credentials';
    });

    // Verify employee endpoint was called with Bearer token
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/employees') &&
               str_contains($request->header('Authorization')[0] ?? '', 'Bearer fake-access-token');
    });
});

test('rejects Provider 1 schema on Provider 2 endpoint', function () {
    // Attempting to use Provider 1 schema structure on Provider 2 endpoint
    $provider1Data = [
        'emp_id' => 'P1_WRONG',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john.doe@example.com',
        'phone' => '+1-555-9999',
        'job_title' => 'Security Officer',
        'dept' => 'Security',
        'hire_date' => '2024-01-15',
        'employment_status' => 'active',
    ];

    $response = postJson('/api/provider2/employees', $provider1Data, authHeaders2());

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid employee data',
            ],
        ]);

    // Verify the error details mention missing required Provider 2 fields
    $responseData = $response->json();
    expect($responseData['error']['details'])->toBeArray();
    
    $fieldNames = collect($responseData['error']['details'])->pluck('field')->toArray();
    expect($fieldNames)->toContain('employee_number')
        ->and($fieldNames)->toContain('personal_info')
        ->and($fieldNames)->toContain('work_info');

    // Employee should not be created
    expect(Employee::where('employee_id', 'P1_WRONG')->exists())->toBeFalse();
});

test('rejects partial Provider 1 schema with some Provider 2 fields', function () {
    // Mixed schema - has Provider 2 employee_number but Provider 1 flat structure
    $mixedData = [
        'employee_number' => 'P2_MIXED',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
    ];

    $response = postJson('/api/provider2/employees', $mixedData, authHeaders2());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    // Employee should not be created
    expect(Employee::where('employee_id', 'P2_MIXED')->exists())->toBeFalse();
});

test('rejects Provider 1 fields in nested Provider 2 structure', function () {
    // Provider 2 structure but with Provider 1 field names inside
    $wrongFieldsData = [
        'employee_number' => 'P2_FIELDS',
        'personal_info' => [
            'first_name' => 'John',  // Wrong: should be 'given_name'
            'last_name' => 'Doe',    // Wrong: should be 'family_name'
            'email_address' => 'john@example.com',  // Wrong: should be 'email'
        ],
        'work_info' => [
            'job_title' => 'Guard',  // Wrong: should be 'role'
            'employment_status' => 'active',  // Wrong: should be 'current_status'
        ],
    ];

    $response = postJson('/api/provider2/employees', $wrongFieldsData, authHeaders2());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    // Employee should not be created
    expect(Employee::where('employee_id', 'P2_FIELDS')->exists())->toBeFalse();
});

<?php

use App\Models\Employee;
use App\Services\TrackTikService;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

// Helper function to get auth headers
function authHeaders(): array {
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
                    'email' => $body['email'] ?? 'test@provider1.com',
                ],
            ], $isUpdate ? 200 : 201);
        }

        return Http::response([], 404);
    });
});

test('can create employee with valid Provider 1 data', function () {
    $employeeData = [
        'emp_id' => 'P1_001',
        'first_name' => 'Alice',
        'last_name' => 'Johnson',
        'email_address' => 'alice.johnson@provider1.com',
        'phone' => '+1-555-0101',
        'job_title' => 'Security Officer',
        'dept' => 'Security Operations',
        'hire_date' => '2024-01-15',
        'employment_status' => 'active',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, authHeaders());

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'employeeId' => 'P1_001',
                'provider' => 'provider1',
            ],
        ]);

    expect(Employee::where('employee_id', 'P1_001')->exists())->toBeTrue();
});

test('can update existing Provider 1 employee', function () {
    // Create initial employee
    Employee::create([
        'employee_id' => 'P1_002',
        'provider' => 'provider1',
        'first_name' => 'Bob',
        'last_name' => 'Smith',
        'email' => 'bob.smith@provider1.com',
        'status' => 'active',
        'tracktik_id' => 'tracktik-uuid-456',
    ]);

    $employeeData = [
        'emp_id' => 'P1_002',
        'first_name' => 'Robert',
        'last_name' => 'Smith',
        'email_address' => 'robert.smith@provider1.com',
        'employment_status' => 'active',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, authHeaders());

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $employee = Employee::where('employee_id', 'P1_002')->first();
    expect($employee->first_name)->toBe('Robert');
});

test('validates required fields for Provider 1', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_003',
        // Missing required fields
    ], authHeaders());

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid employee data',
            ],
        ]);
});

test('validates email format for Provider 1', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_004',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email_address' => 'invalid-email',
    ], authHeaders());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('validates employment status enum for Provider 1', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_005',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email_address' => 'test@example.com',
        'employment_status' => 'invalid_status',
    ], authHeaders());

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('stores provider data in employee record for Provider 1', function () {
    $employeeData = [
        'emp_id' => 'P1_006',
        'first_name' => 'Charlie',
        'last_name' => 'Brown',
        'email_address' => 'charlie@provider1.com',
        'phone' => '+1-555-0103',
    ];

    postJson('/api/provider1/employees', $employeeData, authHeaders());

    $employee = Employee::where('employee_id', 'P1_006')->first();
    expect($employee->provider_data)->toBeArray()
        ->and($employee->provider_data['emp_id'])->toBe('P1_006');
});

test('rejects invalid employment status', function () {
    $employeeData = [
        'emp_id' => 'P1_007',
        'first_name' => 'David',
        'last_name' => 'Lee',
        'email_address' => 'david@provider1.com',
        'employment_status' => 'invalid_status',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, authHeaders());
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
            ],
        ]);
    
    // Employee should not be created
    expect(Employee::where('employee_id', 'P1_007')->exists())->toBeFalse();
});

test('authenticates with OAuth before calling TrackTik API', function () {
    $employeeData = [
        'emp_id' => 'P1_008',
        'first_name' => 'Eva',
        'last_name' => 'Martinez',
        'email_address' => 'eva.martinez@provider1.com',
        'phone' => '+1-555-0108',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, authHeaders());

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

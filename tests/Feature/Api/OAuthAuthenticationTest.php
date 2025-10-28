<?php

use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

// Helper function to get auth headers
function providerAuthHeaders(): array {
    return ['Authorization' => 'Bearer test-provider-token-12345'];
}

test('handles OAuth authentication failure when creating employee', function () {
    // Mock OAuth endpoint to return an error
    Http::fake([
        '*/oauth/token' => Http::response([
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
        ], 401),
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [],
        ], 201),
    ]);

    $employeeData = [
        'emp_id' => 'P1_OAUTH_FAIL',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email_address' => 'test@example.com',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, providerAuthHeaders());

    // Should return internal error due to OAuth failure
    $response->assertStatus(500);

    // Employee should not be created
    expect(Employee::where('employee_id', 'P1_OAUTH_FAIL')->exists())->toBeFalse();
});

test('handles OAuth authentication failure when updating employee', function () {
    // Create an existing employee
    Employee::create([
        'employee_id' => 'P1_OAUTH_UPDATE',
        'provider' => 'provider1',
        'first_name' => 'Original',
        'last_name' => 'Name',
        'email' => 'original@example.com',
        'status' => 'active',
        'tracktik_id' => 'existing-tracktik-id',
    ]);

    // Mock OAuth endpoint to return an error
    Http::fake([
        '*/oauth/token' => Http::response([
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
        ], 401),
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [],
        ], 200),
    ]);

    $employeeData = [
        'emp_id' => 'P1_OAUTH_UPDATE',
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'email_address' => 'updated@example.com',
    ];

    $response = putJson('/api/provider1/employees/P1_OAUTH_UPDATE', $employeeData, providerAuthHeaders());

    // Should return internal error due to OAuth failure
    $response->assertStatus(500);

    // Employee should not be updated
    $employee = Employee::where('employee_id', 'P1_OAUTH_UPDATE')->first();
    expect($employee->first_name)->toBe('Original');
});

test('handles OAuth authentication failure when getting employee', function () {
    // Create an existing employee
    Employee::create([
        'employee_id' => 'P1_OAUTH_GET',
        'provider' => 'provider1',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'status' => 'active',
        'tracktik_id' => 'tracktik-id-123',
    ]);

    // Mock OAuth endpoint to return an error
    Http::fake([
        '*/oauth/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Invalid credentials',
        ], 401),
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [],
        ], 200),
    ]);

    $response = getJson('/api/provider1/employees/P1_OAUTH_GET', providerAuthHeaders());

    // Should still return employee from local database, but without TrackTik data
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'employeeId' => 'P1_OAUTH_GET',
                'provider' => 'provider1',
            ],
        ]);
});

test('OAuth token is reused across multiple requests', function () {
    // Mock successful OAuth and employee endpoints with dynamic responses
    Http::fake(function ($request) {
        $uri = $request->url();

        if (str_contains($uri, '/oauth/token')) {
            return Http::response([
                'access_token' => 'reusable-token-12345',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'employees:read employees:write',
            ], 200);
        }

        if (str_contains($uri, '/employees')) {
            $body = json_decode($request->body(), true);
            $employeeId = $body['employeeId'] ?? 'P1_REUSE';

            return Http::response([
                'success' => true,
                'data' => [
                    'id' => 'tracktik-uuid-' . md5($employeeId),
                    'employeeId' => $employeeId,
                    'firstName' => $body['firstName'] ?? 'Test',
                    'lastName' => $body['lastName'] ?? 'User',
                    'email' => $body['email'] ?? 'test@example.com',
                ],
            ], 201);
        }

        return Http::response([], 404);
    });

    // First request
    postJson('/api/provider1/employees', [
        'emp_id' => 'P1_REUSE_1',
        'first_name' => 'First',
        'last_name' => 'Request',
        'email_address' => 'first@example.com',
    ], providerAuthHeaders())->assertStatus(201);

    // Second request
    postJson('/api/provider1/employees', [
        'emp_id' => 'P1_REUSE_2',
        'first_name' => 'Second',
        'last_name' => 'Request',
        'email_address' => 'second@example.com',
    ], providerAuthHeaders())->assertStatus(201);

    // Verify OAuth token endpoint was called
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/oauth/token');
    });

    // Verify employee endpoint was called for both requests
    $employeeRequests = collect(Http::recorded())->filter(fn($record) => 
        str_contains($record[0]->url(), '/employees')
    );
    expect($employeeRequests)->toHaveCount(2);
});

test('handles OAuth server timeout', function () {
    // Mock OAuth endpoint to timeout
    Http::fake([
        '*/oauth/token' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        },
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [],
        ], 201),
    ]);

    $employeeData = [
        'emp_id' => 'P1_TIMEOUT',
        'first_name' => 'Test',
        'last_name' => 'Timeout',
        'email_address' => 'timeout@example.com',
    ];

    $response = postJson('/api/provider1/employees', $employeeData, providerAuthHeaders());

    // Should return internal error
    $response->assertStatus(500);

    // Employee should not be created
    expect(Employee::where('employee_id', 'P1_TIMEOUT')->exists())->toBeFalse();
});

test('provider2 handles OAuth authentication failure', function () {
    // Mock OAuth endpoint to return an error
    Http::fake([
        '*/oauth/token' => Http::response([
            'error' => 'invalid_scope',
            'error_description' => 'Invalid scope requested',
        ], 400),
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [],
        ], 201),
    ]);

    $employeeData = [
        'employee_number' => 'P2_OAUTH_FAIL',
        'personal_info' => [
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'test@provider2.com',
        ],
        'work_info' => [
            'current_status' => 'employed',
        ],
    ];

    $response = postJson('/api/provider2/employees', $employeeData, providerAuthHeaders());

    // Should return internal error due to OAuth failure
    $response->assertStatus(500);

    // Employee should not be created
    expect(Employee::where('employee_id', 'P2_OAUTH_FAIL')->exists())->toBeFalse();
});

test('verifies OAuth request contains correct credentials', function () {
    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
        '*/employees*' => Http::response([
            'success' => true,
            'data' => [
                'id' => 'test-id',
                'employeeId' => 'P1_CREDS',
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com',
            ],
        ], 201),
    ]);

    postJson('/api/provider1/employees', [
        'emp_id' => 'P1_CREDS',
        'first_name' => 'Test',
        'last_name' => 'Credentials',
        'email_address' => 'creds@example.com',
    ], providerAuthHeaders())->assertStatus(201);

    // Verify OAuth request was sent with correct parameters
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/oauth/token') &&
               $request['grant_type'] === 'client_credentials' &&
               isset($request['client_id']) &&
               isset($request['client_secret']) &&
               isset($request['scope']);
    });
});

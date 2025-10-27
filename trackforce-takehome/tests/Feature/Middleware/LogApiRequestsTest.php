<?php

use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Mock TrackTik API responses
    Http::fake(function ($request) {
        $uri = $request->url();
        
        if (str_contains($uri, '/oauth/token')) {
            return Http::response([
                'access_token' => 'fake-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200);
        }
        
        if (str_contains($uri, '/employees')) {
            $body = json_decode($request->body(), true);
            $employeeId = $body['employeeId'] ?? uniqid();
            
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
});

test('middleware allows successful API requests to pass through', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_LOG_TEST',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email_address' => 'test@example.com',
    ]);

    $response->assertStatus(201);
    expect(Employee::where('employee_id', 'P1_LOG_TEST')->exists())->toBeTrue();
});

test('middleware logs client errors', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_ERROR_TEST',
        // Missing required fields
    ]);

    $response->assertStatus(400);
});

test('middleware works with provider2 requests', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'P2_LOG_TEST',
        'personal_info' => [
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'test@example.com',
        ],
        'work_info' => [
            'current_status' => 'employed',
        ],
    ]);

    $response->assertStatus(201);
    expect(Employee::where('employee_id', 'P2_LOG_TEST')->exists())->toBeTrue();
});

test('middleware does not break normal flow', function () {
    // Test that we can create and update an employee
    $response1 = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_FLOW_TEST',
        'first_name' => 'Flow',
        'last_name' => 'Test',
        'email_address' => 'flow@example.com',
    ]);

    $response1->assertStatus(201);

    // Update the same employee
    $response2 = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_FLOW_TEST',
        'first_name' => 'Flow',
        'last_name' => 'Updated',
        'email_address' => 'flow.updated@example.com',
    ]);

    $response2->assertStatus(200);
    
    $employee = Employee::where('employee_id', 'P1_FLOW_TEST')->first();
    expect($employee->last_name)->toBe('Updated');
});


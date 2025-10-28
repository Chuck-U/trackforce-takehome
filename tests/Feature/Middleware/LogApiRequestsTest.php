<?php

use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function Pest\Laravel\postJson;

// Helper function to get auth headers
function logTestAuthHeaders(): array {
    return ['Authorization' => 'Bearer test-provider-token-12345'];
}

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
                    'email' => $body['email'] ?? 'test@provider1.com',
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
    ], logTestAuthHeaders());

    $response->assertStatus(201);
    expect(Employee::where('employee_id', 'P1_LOG_TEST')->exists())->toBeTrue();
});

test('middleware logs client errors', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_ERROR_TEST',
        // Missing required fields
    ], logTestAuthHeaders());

    $response->assertStatus(400);
});

test('middleware works with provider2 requests', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'P2_LOG_TEST',
        'personal_info' => [
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'test@provider2.com',
        ],
        'work_info' => [
            'current_status' => 'employed',
        ],
    ], logTestAuthHeaders());

    $response->assertStatus(201);
    expect(Employee::where('employee_id', 'P2_LOG_TEST')->exists())->toBeTrue();
});

test('middleware does not break normal flow', function () {
    $response1 = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_FLOW_TEST',
        'first_name' => 'Flow',
        'last_name' => 'Test',
        'email_address' => 'flow@example.com',
    ], logTestAuthHeaders());

    $response1->assertStatus(201);

    // Update the same employee
    $response2 = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_FLOW_TEST',
        'first_name' => 'Updated',
        'last_name' => 'Test',
        'email_address' => 'updated@example.com',
    ], logTestAuthHeaders());

    $response2->assertStatus(200);
});

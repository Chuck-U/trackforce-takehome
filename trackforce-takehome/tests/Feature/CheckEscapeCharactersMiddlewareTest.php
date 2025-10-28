<?php

use Illuminate\Support\Facades\Http;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Mock TrackTik API responses for tests
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
                ],
            ], 201);
        }

        return Http::response([], 404);
    });
});

test('valid input passes middleware on provider1', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'EMP123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john.doe@example.com',
        'status' => 'active'
    ]);

    // Should not get a 400 error from escape character check
    expect($response->status())->not->toBe(400);
});

test('backslash escape character is detected in provider1', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'EMP123',
        'first_name' => 'John\\test',
        'last_name' => 'Doe',
        'email_address' => 'john.doe@example.com',
        'status' => 'active'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
    
    expect($response->json('message'))->toContain('Escape characters detected in field');
});

test('literal newline escape sequence is detected', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'EMP123',
        'first_name' => 'John',
        'last_name' => 'Doe\\n',
        'email_address' => 'john.doe@example.com',
        'status' => 'active'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
});

test('literal tab escape sequence is detected', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'EMP123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john\\t@example.com',
        'status' => 'active'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
});

test('escape characters in nested objects are detected in provider2', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'EMP456',
        'personal_info' => [
            'given_name' => 'Jane\\x1b',
            'family_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ],
        'work_info' => [
            'role' => 'Engineer',
            'start_date' => '2024-01-01',
            'current_status' => 'employed'
        ]
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
});

test('control characters are detected', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'EMP123',
        'first_name' => "John\x00test", // Null byte
        'last_name' => 'Doe',
        'email_address' => 'john.doe@example.com',
        'status' => 'active'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
});

test('middleware works on provider2 routes', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'EMP456\\',
        'personal_info' => [
            'given_name' => 'Jane',
            'family_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ],
        'work_info' => [
            'role' => 'Engineer',
            'start_date' => '2024-01-01',
            'current_status' => 'employed'
        ]
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid Input',
            'status' => 400,
        ]);
});

test('valid provider2 input passes middleware', function () {
    $response = postJson('/api/provider2/employees', [
        'employee_number' => 'EMP456',
        'personal_info' => [
            'given_name' => 'Jane',
            'family_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ],
        'work_info' => [
            'role' => 'Engineer',
            'start_date' => '2024-01-01',
            'current_status' => 'employed'
        ]
    ]);

    // Should not get a 400 error from escape character check
    expect($response->status())->not->toBe(400);
});

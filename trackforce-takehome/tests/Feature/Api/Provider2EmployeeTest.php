<?php

use App\Models\Employee;
use App\Services\TrackTikService;
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

    $response = postJson('/api/provider2/employees', $employeeData);

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

    $response = postJson('/api/provider2/employees', $employeeData);

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
    ]);

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
    ]);

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
    ]);

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

        $response = postJson('/api/provider2/employees', $employeeData);
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

    postJson('/api/provider2/employees', $employeeData);

    $employee = Employee::where('employee_id', 'P2_006')->first();
    expect($employee->provider_data)->toBeArray()
        ->and($employee->provider_data['employee_number'])->toBe('P2_006');
});


<?php

use App\Services\Provider2EmployeeMapper;

test('maps Provider 2 employee data to TrackTik schema', function () {
    $mapper = new Provider2EmployeeMapper();

    $providerData = [
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

    $result = $mapper->mapToTrackTik($providerData);

    expect($result)->toBeArray()
        ->and($result['employeeId'])->toBe('P2_001')
        ->and($result['firstName'])->toBe('Carol')
        ->and($result['lastName'])->toBe('Davis')
        ->and($result['email'])->toBe('carol.davis@provider2.com')
        ->and($result['phoneNumber'])->toBe('+1-555-0201')
        ->and($result['position'])->toBe('Security Guard')
        ->and($result['department'])->toBe('Night Shift Security')
        ->and($result['startDate'])->toBe('2024-02-01')
        ->and($result['status'])->toBe('active');
});

test('maps Provider 2 status values correctly', function () {
    $mapper = new Provider2EmployeeMapper();

    $statusMappings = [
        'employed' => 'active',
        'terminated' => 'terminated',
        'on_leave' => 'inactive',
    ];

    foreach ($statusMappings as $providerStatus => $expectedStatus) {
        $result = $mapper->mapToTrackTik([
            'employee_number' => 'TEST',
            'personal_info' => [
                'given_name' => 'Test',
                'family_name' => 'User',
                'email' => 'test@example.com',
            ],
            'work_info' => [
                'current_status' => $providerStatus,
            ],
        ]);

        expect($result['status'])->toBe($expectedStatus);
    }
});

test('handles missing optional fields for Provider 2', function () {
    $mapper = new Provider2EmployeeMapper();

    $providerData = [
        'employee_number' => 'P2_MINIMAL',
        'personal_info' => [
            'given_name' => 'Jane',
            'family_name' => 'Doe',
            'email' => 'jane@example.com',
        ],
        'work_info' => [],
    ];

    $result = $mapper->mapToTrackTik($providerData);

    expect($result['phoneNumber'])->toBeNull()
        ->and($result['position'])->toBeNull()
        ->and($result['department'])->toBeNull()
        ->and($result['startDate'])->toBeNull()
        ->and($result['status'])->toBe('active');
});

test('handles missing nested objects for Provider 2', function () {
    $mapper = new Provider2EmployeeMapper();

    $providerData = [
        'employee_number' => 'P2_MINIMAL2',
    ];

    $result = $mapper->mapToTrackTik($providerData);

    expect($result['employeeId'])->toBe('P2_MINIMAL2')
        ->and($result['firstName'])->toBe('')
        ->and($result['lastName'])->toBe('')
        ->and($result['email'])->toBe('');
});


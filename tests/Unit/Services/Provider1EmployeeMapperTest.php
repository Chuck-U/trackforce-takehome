<?php

use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Services\Mapping\Provider1StatusMapper;
use App\Services\Provider1EmployeeMapper;

test('maps Provider 1 employee data to TrackTik schema', function () {
    $mapper = new Provider1EmployeeMapper(new Provider1StatusMapper());

    $providerData = Provider1EmployeeData::fromArray([
        'emp_id' => 'P1_001',
        'first_name' => 'Alice',
        'last_name' => 'Johnson',
        'email_address' => 'alice.johnson@provider1.com',
        'phone' => '+1-555-0101',
        'job_title' => 'Security Officer',
        'dept' => 'Security Operations',
        'hire_date' => '2024-01-15',
        'employment_status' => 'active',
    ]);

    $result = $mapper->mapToTrackTik($providerData);

    expect($result)->toBeInstanceOf(TrackTikEmployeeData::class)
        ->and($result->employeeId)->toBe('P1_001')
        ->and($result->firstName)->toBe('Alice')
        ->and($result->lastName)->toBe('Johnson')
        ->and($result->email)->toBe('alice.johnson@provider1.com')
        ->and($result->phoneNumber)->toBe('+1-555-0101')
        ->and($result->position)->toBe('Security Officer')
        ->and($result->department)->toBe('Security Operations')
        ->and($result->startDate)->toBe('2024-01-15')
        ->and($result->status)->toBe('active');
});

test('maps Provider 1 status values correctly', function () {
    $mapper = new Provider1EmployeeMapper(new Provider1StatusMapper());

    $statusMappings = [
        'active' => 'active',
        'inactive' => 'inactive',
        'terminated' => 'terminated',
    ];

    foreach ($statusMappings as $providerStatus => $expectedStatus) {
        $providerData = Provider1EmployeeData::fromArray([
            'emp_id' => 'TEST',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email_address' => 'test@example.com',
            'employment_status' => $providerStatus,
        ]);
        
        $result = $mapper->mapToTrackTik($providerData);

        expect($result->status)->toBe($expectedStatus);
    }
});

test('handles missing optional fields for Provider 1', function () {
    $mapper = new Provider1EmployeeMapper(new Provider1StatusMapper());

    $providerData = Provider1EmployeeData::fromArray([
        'emp_id' => 'P1_MINIMAL',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john@example.com',
    ]);

    $result = $mapper->mapToTrackTik($providerData);

    expect($result->phoneNumber)->toBeNull()
        ->and($result->position)->toBeNull()
        ->and($result->department)->toBeNull()
        ->and($result->startDate)->toBeNull()
        ->and($result->status)->toBe('active');
});


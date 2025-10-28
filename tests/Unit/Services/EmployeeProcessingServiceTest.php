<?php

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\TrackTikServiceInterface;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Domain\DataTransferObjects\TrackTikResponse;
use App\Models\Employee;
use App\Services\Employee\EmployeeProcessingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Mock the transaction facade
    DB::shouldReceive('transaction')
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

    // Create mocks for dependencies
    $this->employeeRepository = Mockery::mock(EmployeeRepositoryInterface::class);
    $this->trackTikService = Mockery::mock(TrackTikServiceInterface::class);
    
    // Create the service with mocked dependencies
    $this->service = new EmployeeProcessingService(
        $this->employeeRepository,
        $this->trackTikService
    );
});

afterEach(function () {
    Mockery::close();
});

describe('processEmployee', function () {
    test('creates new employee successfully', function () {
        Log::shouldReceive('info')->once();

        // Mock employee repository - employee doesn't exist
        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP001')
            ->once()
            ->andReturn(null);

        // Mock TrackTik service - successful creation
        $trackTikResponse = TrackTikResponse::success([
            'id' => 'tt-12345',
            'employeeId' => 'EMP001',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);
        
        $this->trackTikService
            ->shouldReceive('createEmployee')
            ->once()
            ->andReturn($trackTikResponse);

        // Mock employee repository - create new employee
        $newEmployee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP001',
            'provider' => 'provider1',
            'tracktik_id' => 'tt-12345',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $newEmployee->id = 1;

        $this->employeeRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($newEmployee);

        // Prepare test data
        $trackTikData = new TrackTikEmployeeData(
            employeeId: 'EMP001',
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            status: 'active'
        );

        $employeeData = [
            'employee_id' => 'EMP001',
            'provider' => 'provider1',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ];

        // Execute
        $result = $this->service->processEmployee(
            'provider1',
            'EMP001',
            $trackTikData,
            $employeeData
        );

        // Assert
        expect($result['response']->success)->toBeTrue()
            ->and($result['isUpdate'])->toBeFalse()
            ->and($result['response']->data['employeeId'])->toBe('EMP001')
            ->and($result['response']->data['tracktikId'])->toBe('tt-12345');
    });

    test('updates existing employee successfully', function () {
        Log::shouldReceive('info')->once();

        // Mock existing employee
        $existingEmployee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP002',
            'provider' => 'provider1',
            'tracktik_id' => 'tt-67890',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $existingEmployee->id = 1;

        // Mock employee repository - employee exists
        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP002')
            ->once()
            ->andReturn($existingEmployee);

        // Mock TrackTik service - successful update
        $trackTikResponse = TrackTikResponse::success([
            'id' => 'tt-67890',
            'employeeId' => 'EMP002',
            'firstName' => 'Jane',
            'lastName' => 'Smith-Jones',
        ]);

        $this->trackTikService
            ->shouldReceive('updateEmployee')
            ->with('tt-67890', Mockery::type(TrackTikEmployeeData::class))
            ->once()
            ->andReturn($trackTikResponse);

        // Mock employee repository - update employee
        $updatedEmployee = clone $existingEmployee;
        $updatedEmployee->last_name = 'Smith-Jones';

        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($updatedEmployee);

        // Prepare test data
        $trackTikData = new TrackTikEmployeeData(
            employeeId: 'EMP002',
            firstName: 'Jane',
            lastName: 'Smith-Jones',
            email: 'jane@example.com',
            status: 'active'
        );

        $employeeData = [
            'employee_id' => 'EMP002',
            'provider' => 'provider1',
            'first_name' => 'Jane',
            'last_name' => 'Smith-Jones',
            'email' => 'jane@example.com',
            'status' => 'active',
        ];

        // Execute
        $result = $this->service->processEmployee(
            'provider1',
            'EMP002',
            $trackTikData,
            $employeeData
        );

        // Assert
        expect($result['response']->success)->toBeTrue()
            ->and($result['isUpdate'])->toBeTrue()
            ->and($result['response']->data['employeeId'])->toBe('EMP002')
            ->and($result['response']->data['message'])->toContain('updated');
    });

    test('handles TrackTik API error during creation', function () {
        // Mock employee repository - employee doesn't exist
        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP003')
            ->once()
            ->andReturn(null);

        // Mock TrackTik service - error response
        $trackTikResponse = TrackTikResponse::error('Invalid data provided');

        $this->trackTikService
            ->shouldReceive('createEmployee')
            ->once()
            ->andReturn($trackTikResponse);

        // Prepare test data
        $trackTikData = new TrackTikEmployeeData(
            employeeId: 'EMP003',
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            status: 'active'
        );

        $employeeData = [
            'employee_id' => 'EMP003',
            'provider' => 'provider1',
        ];

        // Execute
        $result = $this->service->processEmployee(
            'provider1',
            'EMP003',
            $trackTikData,
            $employeeData
        );

        // Assert
        expect($result['response']->success)->toBeFalse()
            ->and($result['isUpdate'])->toBeFalse()
            ->and($result['response']->error)->toBe('Invalid data provided');
    });

    test('creates employee when existing employee has no tracktik_id', function () {
        Log::shouldReceive('info')->once();

        // Mock existing employee without tracktik_id
        $existingEmployee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP004',
            'provider' => 'provider1',
            'tracktik_id' => null,
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
        ]);
        $existingEmployee->id = 1;

        // Mock employee repository - employee exists but no tracktik_id
        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP004')
            ->once()
            ->andReturn($existingEmployee);

        // Mock TrackTik service - should create (not update) since no tracktik_id
        $trackTikResponse = TrackTikResponse::success([
            'id' => 'tt-new-123',
            'employeeId' => 'EMP004',
        ]);

        $this->trackTikService
            ->shouldReceive('createEmployee')
            ->once()
            ->andReturn($trackTikResponse);

        // Mock employee repository - update existing employee
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($existingEmployee);

        // Prepare test data
        $trackTikData = new TrackTikEmployeeData(
            employeeId: 'EMP004',
            firstName: 'Bob',
            lastName: 'Wilson',
            email: 'bob@example.com',
            status: 'active'
        );

        $employeeData = [
            'employee_id' => 'EMP004',
            'provider' => 'provider1',
        ];

        // Execute
        $result = $this->service->processEmployee(
            'provider1',
            'EMP004',
            $trackTikData,
            $employeeData
        );

        // Assert
        expect($result['response']->success)->toBeTrue()
            ->and($result['isUpdate'])->toBeTrue();
    });
});

describe('getEmployee', function () {
    test('retrieves employee successfully without TrackTik data', function () {
        // Mock existing employee
        $employee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP001',
            'provider' => 'provider1',
            'tracktik_id' => null,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $employee->id = 1;

        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP001')
            ->once()
            ->andReturn($employee);

        // Execute
        $result = $this->service->getEmployee('provider1', 'EMP001');

        // Assert
        expect($result->success)->toBeTrue()
            ->and($result->data['employeeId'])->toBe('EMP001')
            ->and($result->data['tracktik'])->toBeNull();
    });

    test('retrieves employee with TrackTik data', function () {
        // Mock existing employee with tracktik_id
        $employee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP002',
            'provider' => 'provider1',
            'tracktik_id' => 'tt-12345',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $employee->id = 1;

        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP002')
            ->once()
            ->andReturn($employee);

        // Mock TrackTik service
        $trackTikData = [
            'id' => 'tt-12345',
            'employeeId' => 'EMP002',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
        ];

        $this->trackTikService
            ->shouldReceive('getEmployee')
            ->with('tt-12345')
            ->once()
            ->andReturn(TrackTikResponse::success($trackTikData));

        // Execute
        $result = $this->service->getEmployee('provider1', 'EMP002');

        // Assert
        expect($result->success)->toBeTrue()
            ->and($result->data['employeeId'])->toBe('EMP002')
            ->and($result->data['tracktik'])->toBe($trackTikData)
            ->and($result->data['tracktik']['firstName'])->toBe('Jane');
    });

    test('returns error when employee not found', function () {
        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP999')
            ->once()
            ->andReturn(null);

        // Execute
        $result = $this->service->getEmployee('provider1', 'EMP999');

        // Assert
        expect($result->success)->toBeFalse()
            ->and($result->error)->toBe('Employee not found');
    });

    test('handles TrackTik API error gracefully', function () {
        // Mock existing employee with tracktik_id
        $employee = new Employee([
            'id' => 1,
            'employee_id' => 'EMP003',
            'provider' => 'provider1',
            'tracktik_id' => 'tt-error',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $employee->id = 1;

        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP003')
            ->once()
            ->andReturn($employee);

        // Mock TrackTik service - error response
        $this->trackTikService
            ->shouldReceive('getEmployee')
            ->with('tt-error')
            ->once()
            ->andReturn(TrackTikResponse::error('TrackTik API unavailable'));

        // Execute
        $result = $this->service->getEmployee('provider1', 'EMP003');

        // Assert - should still return employee data, just without TrackTik info
        expect($result->success)->toBeTrue()
            ->and($result->data['employeeId'])->toBe('EMP003')
            ->and($result->data['tracktik'])->toBeNull();
    });

    test('handles exception and returns error', function () {
        Log::shouldReceive('error')->once();

        $this->employeeRepository
            ->shouldReceive('findByProviderAndId')
            ->with('provider1', 'EMP004')
            ->once()
            ->andThrow(new \Exception('Database connection error'));

        // Execute
        $result = $this->service->getEmployee('provider1', 'EMP004');

        // Assert
        expect($result->success)->toBeFalse()
            ->and($result->error)->toBe('Unable to retrieve employee');
    });
});


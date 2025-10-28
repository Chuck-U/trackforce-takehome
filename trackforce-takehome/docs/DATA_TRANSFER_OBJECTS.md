# Data Transfer Objects (DTOs) Documentation

## Overview

This application uses Data Transfer Objects (DTOs) to provide type-safe data structures for employee information across different providers. DTOs follow Clean Architecture principles and are organized in the `app/Domain/DataTransferObjects` directory.

## Why DTOs?

### Benefits

1. **Type Safety**: Readonly properties ensure data immutability after creation
2. **Self-Documenting**: Properties clearly define the structure of each provider's data
3. **Validation at Construction**: Invalid data fails fast at object creation
4. **IDE Support**: Autocomplete and type hints improve developer experience
5. **Refactoring Safety**: Changes to property names are caught at compile time
6. **Clean Architecture**: Domain objects are separated from framework concerns

### Before vs After

**Before (Arrays):**
```php
$data = [
    'emp_id' => 'P1_001',
    'first_name' => 'Alice',
    // ... typos and missing keys not caught until runtime
];

$result = $mapper->mapToTrackTik($data);
echo $result['firstName']; // No autocomplete, runtime errors possible
```

**After (DTOs):**
```php
$data = Provider1EmployeeData::fromArray([
    'emp_id' => 'P1_001',
    'first_name' => 'Alice',
    // ... validated at creation
]);

$result = $mapper->mapToTrackTik($data);
echo $result->firstName; // Full autocomplete and type checking
```

## Available DTOs

### 1. Provider1EmployeeData

Represents employee data from Provider 1.

**Location:** `app/Domain/DataTransferObjects/Provider1EmployeeData.php`

**Properties:**
- `empId` (string, required) - Provider's employee identifier
- `firstName` (string, required) - Employee's first name
- `lastName` (string, required) - Employee's last name
- `emailAddress` (string, required) - Employee's email address
- `phone` (?string, optional) - Phone number
- `jobTitle` (?string, optional) - Job title/position
- `dept` (?string, optional) - Department name
- `hireDate` (?string, optional) - Hire date (YYYY-MM-DD format)
- `employmentStatus` (string, default: 'active') - Employment status

**Usage:**
```php
use App\Domain\DataTransferObjects\Provider1EmployeeData;

// Create from array (typically from API request)
$employeeData = Provider1EmployeeData::fromArray([
    'emp_id' => 'P1_001',
    'first_name' => 'Alice',
    'last_name' => 'Johnson',
    'email_address' => 'alice@example.com',
    'phone' => '+1-555-0101',
    'job_title' => 'Security Officer',
    'dept' => 'Security Operations',
    'hire_date' => '2024-01-15',
    'employment_status' => 'active',
]);

// Access properties
echo $employeeData->empId; // "P1_001"
echo $employeeData->firstName; // "Alice"

// Convert back to array
$array = $employeeData->toArray();
```

### 2. Provider2EmployeeData

Represents employee data from Provider 2 with nested structure.

**Location:** `app/Domain/DataTransferObjects/Provider2EmployeeData.php`

**Properties:**
- `employeeNumber` (string, required) - Provider's employee identifier
- `givenName` (string, required) - Employee's given name
- `familyName` (string, required) - Employee's family name
- `email` (string, required) - Employee's email address
- `mobile` (?string, optional) - Mobile phone number
- `role` (?string, optional) - Job role
- `division` (?string, optional) - Division/department
- `startDate` (?string, optional) - Start date (YYYY-MM-DD format)
- `currentStatus` (string, default: 'employed') - Current employment status

**Usage:**
```php
use App\Domain\DataTransferObjects\Provider2EmployeeData;

// Create from nested array structure
$employeeData = Provider2EmployeeData::fromArray([
    'employee_number' => 'P2_001',
    'personal_info' => [
        'given_name' => 'Carol',
        'family_name' => 'Davis',
        'email' => 'carol@example.com',
        'mobile' => '+1-555-0201',
    ],
    'work_info' => [
        'role' => 'Security Guard',
        'division' => 'Night Shift',
        'start_date' => '2024-02-01',
        'current_status' => 'employed',
    ],
]);

// Access flat properties (no nesting)
echo $employeeData->givenName; // "Carol"
echo $employeeData->role; // "Security Guard"

// Convert back to nested array structure
$array = $employeeData->toArray();
```

### 3. TrackTikEmployeeData

Represents employee data in TrackTik's format.

**Location:** `app/Domain/DataTransferObjects/TrackTikEmployeeData.php`

**Properties:**
- `employeeId` (string, required) - Employee identifier
- `firstName` (string, required) - First name
- `lastName` (string, required) - Last name
- `email` (string, required) - Email address
- `status` (string, required) - Employment status (active, inactive, terminated)
- `phoneNumber` (?string, optional) - Phone number
- `position` (?string, optional) - Job position
- `department` (?string, optional) - Department
- `startDate` (?string, optional) - Start date (YYYY-MM-DD format)

**Usage:**
```php
use App\Domain\DataTransferObjects\TrackTikEmployeeData;

// Create directly
$trackTikData = new TrackTikEmployeeData(
    employeeId: 'P1_001',
    firstName: 'Alice',
    lastName: 'Johnson',
    email: 'alice@example.com',
    status: 'active',
    phoneNumber: '+1-555-0101',
    position: 'Security Officer',
    department: 'Security Operations',
    startDate: '2024-01-15',
);

// Or from array
$trackTikData = TrackTikEmployeeData::fromArray([
    'employeeId' => 'P1_001',
    'firstName' => 'Alice',
    'lastName' => 'Johnson',
    'email' => 'alice@example.com',
    'status' => 'active',
    'phoneNumber' => '+1-555-0101',
    'position' => 'Security Officer',
    'department' => 'Security Operations',
    'startDate' => '2024-01-15',
]);

// Convert to array
$array = $trackTikData->toArray();

// Convert to array without null values (useful for API calls)
$cleanArray = $trackTikData->toArrayWithoutNulls();
```

## Integration with Mappers

Mappers now accept and return DTOs instead of arrays:

```php
use App\Services\Provider1EmployeeMapper;
use App\Domain\DataTransferObjects\Provider1EmployeeData;

$mapper = new Provider1EmployeeMapper();

// Create DTO from request data
$providerData = Provider1EmployeeData::fromArray($request->validated());

// Map to TrackTik DTO
$trackTikData = $mapper->mapToTrackTik($providerData);

// Send to API (convert to array)
$response = $trackTikService->createEmployee($trackTikData->toArray());
```

## Integration with Controllers

Controllers create DTOs from validated request data:

```php
public function store(Provider1EmployeeRequest $request): JsonResponse
{
    $validatedData = $request->validated();
    
    // Create DTO from validated data
    $providerData = Provider1EmployeeData::fromArray($validatedData);
    
    // Map to TrackTik schema
    $trackTikData = $this->mapper->mapToTrackTik($providerData);
    
    // Use DTO data
    $response = $this->trackTikService->createEmployee($trackTikData->toArray());
}
```

## Testing with DTOs

### Unit Tests

```php
use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;

test('maps Provider 1 employee data to TrackTik schema', function () {
    $mapper = new Provider1EmployeeMapper();
    
    $providerData = Provider1EmployeeData::fromArray([
        'emp_id' => 'P1_001',
        'first_name' => 'Alice',
        'last_name' => 'Johnson',
        'email_address' => 'alice@example.com',
    ]);
    
    $result = $mapper->mapToTrackTik($providerData);
    
    expect($result)->toBeInstanceOf(TrackTikEmployeeData::class)
        ->and($result->employeeId)->toBe('P1_001')
        ->and($result->firstName)->toBe('Alice');
});
```

### Feature Tests

Feature tests don't need to change - they still send arrays via API:

```php
test('can create employee with valid Provider 1 data', function () {
    $response = postJson('/api/provider1/employees', [
        'emp_id' => 'P1_001',
        'first_name' => 'Alice',
        'last_name' => 'Johnson',
        'email_address' => 'alice@example.com',
    ]);
    
    $response->assertStatus(201);
});
```

## Best Practices

### 1. Always Use `fromArray()` for External Data

```php
// Good
$dto = Provider1EmployeeData::fromArray($request->validated());

// Bad (bypasses validation)
$dto = new Provider1EmployeeData(...$data);
```

### 2. Use DTOs in Service Layer

```php
// Good - type-safe
public function processEmployee(Provider1EmployeeData $employee): void
{
    // ...
}

// Bad - no type safety
public function processEmployee(array $employee): void
{
    // ...
}
```

### 3. Convert to Arrays Only at Boundaries

```php
// Good - keep DTOs throughout business logic
$dto = Provider1EmployeeData::fromArray($data);
$result = $mapper->mapToTrackTik($dto);
$apiData = $result->toArray(); // Convert only for external API

// Bad - converting back and forth
$dto = Provider1EmployeeData::fromArray($data);
$array = $dto->toArray();
$dto2 = Provider1EmployeeData::fromArray($array);
```

### 4. Use Readonly Properties

All DTOs use `readonly` to ensure immutability:

```php
readonly class Provider1EmployeeData
{
    public function __construct(
        public string $empId, // Can't be changed after construction
        // ...
    ) {}
}
```

### 5. Handle Optional Fields Gracefully

```php
// DTOs handle null values properly
$dto = Provider1EmployeeData::fromArray([
    'emp_id' => 'P1_001',
    'first_name' => 'Alice',
    'last_name' => 'Johnson',
    'email_address' => 'alice@example.com',
    // phone is null (optional)
]);

echo $dto->phone ?? 'No phone'; // "No phone"
```

## Common Patterns

### Pattern 1: Request → DTO → Mapper → DTO → Service

```php
// 1. Request with validation
$validated = $request->validated();

// 2. Create provider DTO
$providerData = Provider1EmployeeData::fromArray($validated);

// 3. Map to TrackTik DTO
$trackTikData = $mapper->mapToTrackTik($providerData);

// 4. Convert to array for external service
$response = $service->send($trackTikData->toArray());
```

### Pattern 2: Testing Mapper Transformations

```php
test('mapper transforms data correctly', function () {
    $input = Provider1EmployeeData::fromArray([...]);
    $output = $mapper->mapToTrackTik($input);
    
    expect($output)->toBeInstanceOf(TrackTikEmployeeData::class)
        ->and($output->employeeId)->toBe($input->empId)
        ->and($output->firstName)->toBe($input->firstName);
});
```

### Pattern 3: Handling Multiple Providers

```php
match ($provider) {
    'provider1' => Provider1EmployeeData::fromArray($data),
    'provider2' => Provider2EmployeeData::fromArray($data),
    default => throw new InvalidArgumentException("Unknown provider: $provider"),
};
```

## Adding New DTOs

When adding a new provider, create a new DTO:

1. **Create the DTO class:**
```php
// app/Domain/DataTransferObjects/Provider3EmployeeData.php
<?php

namespace App\Domain\DataTransferObjects;

readonly class Provider3EmployeeData
{
    public function __construct(
        public string $id,
        public string $name,
        // ... add all required and optional fields
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            // ... map all fields
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // ... return all fields
        ];
    }
}
```

2. **Update the mapper interface** (if needed)
3. **Create mapper implementation**
4. **Write tests**

## Migration Guide

### From Arrays to DTOs

**Before:**
```php
public function mapToTrackTik(array $data): array
{
    return [
        'employeeId' => $data['emp_id'],
        'firstName' => $data['first_name'],
    ];
}
```

**After:**
```php
use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;

public function mapToTrackTik(Provider1EmployeeData $data): TrackTikEmployeeData
{
    return new TrackTikEmployeeData(
        employeeId: $data->empId,
        firstName: $data->firstName,
        // ...
    );
}
```

## Troubleshooting

### Issue: "Cannot modify readonly property"

```php
// Bad - trying to modify readonly property
$dto->firstName = 'New Name'; // Error!

// Good - create a new instance
$newDto = new Provider1EmployeeData(
    empId: $dto->empId,
    firstName: 'New Name',
    // ... copy other properties
);
```

### Issue: "Undefined property"

```php
// Bad - property doesn't exist
echo $dto->nonExistentProperty; // Error!

// Good - check property exists or use null coalescing
echo $dto->phone ?? 'N/A';
```

### Issue: "Type mismatch in fromArray()"

```php
// Bad - wrong data types
Provider1EmployeeData::fromArray([
    'emp_id' => 123, // Should be string
]);

// Good - ensure correct types
Provider1EmployeeData::fromArray([
    'emp_id' => '123',
]);
```

## Performance Considerations

DTOs have minimal overhead:
- **Memory**: Slightly more than arrays (~5-10% increase)
- **Speed**: No significant performance impact
- **Benefits**: Type safety and refactoring safety far outweigh minimal overhead

## Related Documentation

- [Mapper Documentation](MAPPERS.md)
- [API Documentation](../API_DOCUMENTATION.md)
- [Testing Guide](TESTING.md)
- [Clean Architecture Principles](ARCHITECTURE.md)


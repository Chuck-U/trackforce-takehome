# DTO Architecture Diagram

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         External Systems                            │
├─────────────────────────────────────────────────────────────────────┤
│  Provider 1 API          Provider 2 API          TrackTik API       │
│  (Flat JSON)             (Nested JSON)           (Flat JSON)        │
└──────┬───────────────────────┬──────────────────────────┬───────────┘
       │                       │                          │
       │ POST JSON             │ POST JSON                │ OAuth2 + REST
       ▼                       ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      HTTP Boundary Layer                            │
├─────────────────────────────────────────────────────────────────────┤
│  Laravel Routes + Middleware                                        │
│  • ValidateProviderToken                                            │
│  • LogApiRequests                                                   │
└──────┬───────────────────────┬──────────────────────────┬───────────┘
       │                       │                          │
       ▼                       ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Presentation Layer                               │
├─────────────────────────────────────────────────────────────────────┤
│  Controllers                                                        │
│  ┌──────────────────────┐  ┌──────────────────────┐                │
│  │ Provider1Controller  │  │ Provider2Controller  │                │
│  │ • Validate requests  │  │ • Validate requests  │                │
│  │ • Create DTOs        │  │ • Create DTOs        │                │
│  │ • Handle responses   │  │ • Handle responses   │                │
│  └──────────────────────┘  └──────────────────────┘                │
└──────┬───────────────────────┬──────────────────────────────────────┘
       │                       │
       │ Array → DTO           │ Array → DTO
       ▼                       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       Domain Layer                                  │
├─────────────────────────────────────────────────────────────────────┤
│  Data Transfer Objects (DTOs)                                       │
│  ┌────────────────────┐  ┌────────────────────┐  ┌───────────────┐ │
│  │ Provider1          │  │ Provider2          │  │ TrackTik      │ │
│  │ EmployeeData       │  │ EmployeeData       │  │ EmployeeData  │ │
│  │                    │  │                    │  │               │ │
│  │ • empId            │  │ • employeeNumber   │  │ • employeeId  │ │
│  │ • firstName        │  │ • givenName        │  │ • firstName   │ │
│  │ • lastName         │  │ • familyName       │  │ • lastName    │ │
│  │ • emailAddress     │  │ • email            │  │ • email       │ │
│  │ • phone            │  │ • mobile           │  │ • phoneNumber │ │
│  │ • jobTitle         │  │ • role             │  │ • position    │ │
│  │ • dept             │  │ • division         │  │ • department  │ │
│  │ • hireDate         │  │ • startDate        │  │ • startDate   │ │
│  │ • employmentStatus │  │ • currentStatus    │  │ • status      │ │
│  └────────────────────┘  └────────────────────┘  └───────────────┘ │
│                                                                     │
│  Properties: readonly (immutable)                                  │
│  Creation: fromArray() static factory                              │
│  Export: toArray() instance method                                 │
└──────┬───────────────────────┬──────────────────────────┬───────────┘
       │                       │                          │
       │ Provider DTO          │ Provider DTO             │ TrackTik DTO
       ▼                       ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Application Layer                                │
├─────────────────────────────────────────────────────────────────────┤
│  Services & Mappers                                                 │
│  ┌──────────────────────┐  ┌──────────────────────┐                │
│  │ Provider1Mapper      │  │ Provider2Mapper      │                │
│  │                      │  │                      │                │
│  │ Input:  Provider1DTO │  │ Input:  Provider2DTO │                │
│  │ Output: TrackTikDTO  │  │ Output: TrackTikDTO  │                │
│  │                      │  │                      │                │
│  │ • Map field names    │  │ • Map field names    │                │
│  │ • Transform statuses │  │ • Transform statuses │                │
│  │ • Handle nulls       │  │ • Handle nulls       │                │
│  └──────────────────────┘  └──────────────────────┘                │
│                                                                     │
│  ┌──────────────────────────────────────────────┐                  │
│  │ TrackTikService                              │                  │
│  │ • OAuth2 token management                    │                  │
│  │ • Create employee (POST /employees)          │                  │
│  │ • Update employee (PUT /employees/{id})      │                  │
│  └──────────────────────────────────────────────┘                  │
└──────┬──────────────────────────────────────────────────────────────┘
       │
       │ DTO → Array
       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                              │
├─────────────────────────────────────────────────────────────────────┤
│  Database & External APIs                                           │
│  ┌──────────────────────┐  ┌──────────────────────┐                │
│  │ Employee Model       │  │ HTTP Client          │                │
│  │ (Eloquent)           │  │ (TrackTik API)       │                │
│  │                      │  │                      │                │
│  │ • Store local copy   │  │ • Forward to API     │                │
│  │ • Track TrackTik ID  │  │ • Handle responses   │                │
│  │ • Keep provider data │  │ • Manage tokens      │                │
│  └──────────────────────┘  └──────────────────────┘                │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow Example: Provider 1 Employee Creation

```
1. External Request
   POST /api/provider1/employees
   {
     "emp_id": "P1_001",
     "first_name": "Alice",
     "last_name": "Johnson",
     "email_address": "alice@example.com"
   }
   ↓
   
2. Validation (FormRequest)
   Provider1EmployeeRequest::rules()
   ✓ Validates required fields
   ✓ Validates email format
   ✓ Validates enum values
   ↓
   
3. DTO Creation (Controller)
   $dto = Provider1EmployeeData::fromArray($validated)
   
   Provider1EmployeeData {
     empId: "P1_001"
     firstName: "Alice"
     lastName: "Johnson"
     emailAddress: "alice@example.com"
     phone: null
     jobTitle: null
     dept: null
     hireDate: null
     employmentStatus: "active"
   }
   ↓
   
4. Mapping (Mapper)
   $trackTikDto = $mapper->mapToTrackTik($dto)
   
   TrackTikEmployeeData {
     employeeId: "P1_001"      ← mapped from empId
     firstName: "Alice"         ← direct copy
     lastName: "Johnson"        ← direct copy
     email: "alice@example.com" ← mapped from emailAddress
     status: "active"           ← mapped from employmentStatus
     phoneNumber: null
     position: null
     department: null
     startDate: null
   }
   ↓
   
5. API Call (Service)
   $array = $trackTikDto->toArray()
   $response = $trackTikService->createEmployee($array)
   
   POST https://api.tracktik.com/v1/employees
   {
     "employeeId": "P1_001",
     "firstName": "Alice",
     "lastName": "Johnson",
     "email": "alice@example.com",
     "status": "active"
   }
   ↓
   
6. Database Storage (Model)
   Employee::create([
     'employee_id' => 'P1_001',
     'provider' => 'provider1',
     'first_name' => 'Alice',
     'last_name' => 'Johnson',
     'tracktik_id' => 'uuid-from-api',
     'provider_data' => $dto->toArray()
   ])
   ↓
   
7. Response
   201 Created
   {
     "success": true,
     "data": {
       "employee_id": "P1_001",
       "tracktik_id": "uuid-from-api"
     }
   }
```

## DTO Transformation Example: Provider 2 (Nested)

```
Input (Nested JSON):
{
  "employee_number": "P2_001",
  "personal_info": {
    "given_name": "Carol",
    "family_name": "Davis",
    "email": "carol@example.com",
    "mobile": "+1-555-0201"
  },
  "work_info": {
    "role": "Security Guard",
    "division": "Night Shift",
    "start_date": "2024-02-01",
    "current_status": "employed"
  }
}

↓ fromArray() flattens

Provider2EmployeeData (Flat DTO):
{
  employeeNumber: "P2_001"
  givenName: "Carol"
  familyName: "Davis"
  email: "carol@example.com"
  mobile: "+1-555-0201"
  role: "Security Guard"
  division: "Night Shift"
  startDate: "2024-02-01"
  currentStatus: "employed"
}

↓ mapToTrackTik()

TrackTikEmployeeData (Flat DTO):
{
  employeeId: "P2_001"      ← employeeNumber
  firstName: "Carol"         ← givenName
  lastName: "Davis"          ← familyName
  email: "carol@example.com" ← email (direct)
  phoneNumber: "+1-555-0201" ← mobile
  position: "Security Guard" ← role
  department: "Night Shift"  ← division
  startDate: "2024-02-01"    ← startDate (direct)
  status: "active"           ← employed → active
}

↓ toArray()

Output (Flat JSON):
{
  "employeeId": "P2_001",
  "firstName": "Carol",
  "lastName": "Davis",
  "email": "carol@example.com",
  "phoneNumber": "+1-555-0201",
  "position": "Security Guard",
  "department": "Night Shift",
  "startDate": "2024-02-01",
  "status": "active"
}
```

## Status Mapping

### Provider 1 → TrackTik
```
┌──────────────┐         ┌──────────────┐
│ Provider 1   │         │  TrackTik    │
├──────────────┤         ├──────────────┤
│ active       │────────▶│ active       │
│ inactive     │────────▶│ inactive     │
│ terminated   │────────▶│ terminated   │
│ (default)    │────────▶│ active       │
└──────────────┘         └──────────────┘
```

### Provider 2 → TrackTik
```
┌──────────────┐         ┌──────────────┐
│ Provider 2   │         │  TrackTik    │
├──────────────┤         ├──────────────┤
│ employed     │────────▶│ active       │
│ terminated   │────────▶│ terminated   │
│ on_leave     │────────▶│ inactive     │
│ (default)    │────────▶│ active       │
└──────────────┘         └──────────────┘
```

## Class Diagram

```
┌────────────────────────────────────────┐
│   <<readonly>>                         │
│   Provider1EmployeeData                │
├────────────────────────────────────────┤
│ + empId: string                        │
│ + firstName: string                    │
│ + lastName: string                     │
│ + emailAddress: string                 │
│ + phone: ?string                       │
│ + jobTitle: ?string                    │
│ + dept: ?string                        │
│ + hireDate: ?string                    │
│ + employmentStatus: string             │
├────────────────────────────────────────┤
│ + fromArray(array): self               │
│ + toArray(): array                     │
└────────────────────────────────────────┘
                │
                │ maps to
                ▼
┌────────────────────────────────────────┐
│   <<readonly>>                         │
│   TrackTikEmployeeData                 │
├────────────────────────────────────────┤
│ + employeeId: string                   │
│ + firstName: string                    │
│ + lastName: string                     │
│ + email: string                        │
│ + status: string                       │
│ + phoneNumber: ?string                 │
│ + position: ?string                    │
│ + department: ?string                  │
│ + startDate: ?string                   │
├────────────────────────────────────────┤
│ + fromArray(array): self               │
│ + toArray(): array                     │
│ + toArrayWithoutNulls(): array         │
└────────────────────────────────────────┘
                ▲
                │ maps to
                │
┌────────────────────────────────────────┐
│   <<readonly>>                         │
│   Provider2EmployeeData                │
├────────────────────────────────────────┤
│ + employeeNumber: string               │
│ + givenName: string                    │
│ + familyName: string                   │
│ + email: string                        │
│ + mobile: ?string                      │
│ + role: ?string                        │
│ + division: ?string                    │
│ + startDate: ?string                   │
│ + currentStatus: string                │
├────────────────────────────────────────┤
│ + fromArray(array): self               │
│ + toArray(): array                     │
└────────────────────────────────────────┘
```

## Testing Pyramid

```
                    ┌──────────┐
                    │ Feature  │ ← HTTP → Database → API
                    │  Tests   │   (Integration)
                    │   14     │
                    └─────┬────┘
                          │
                  ┌───────▼────────┐
                  │   Unit Tests   │ ← Mapper logic
                  │  (Mapper only) │   (Isolated)
                  │       7        │
                  └───────┬────────┘
                          │
                ┌─────────▼─────────┐
                │   DTOs            │ ← Type safety
                │ (Compile-time)    │   (Language level)
                │   3 classes       │
                └───────────────────┘
```

## Benefits Visualization

```
Traditional Array Approach:
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│ Request  │→→→│  Array   │→→→│  Array   │→→→│ Response │
└──────────┘   └──────────┘   └──────────┘   └──────────┘
               No type      No immutability   Runtime
               checking     No IDE support    errors

DTO Approach:
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│ Request  │→→→│   DTO    │→→→│   DTO    │→→→│ Response │
└──────────┘   └──────────┘   └──────────┘   └──────────┘
               ✓ Type       ✓ Immutable       Compile
               checked      ✓ IDE support     time safe
```

## Summary

The DTO architecture provides:

1. **Clear Boundaries**: Each layer has well-defined inputs/outputs
2. **Type Safety**: Compile-time checking prevents runtime errors
3. **Immutability**: Readonly properties prevent accidental mutations
4. **Maintainability**: Refactoring is safe and IDE-assisted
5. **Testability**: Easy to test transformations in isolation
6. **Documentation**: Code is self-documenting with clear types

All while maintaining clean separation between:
- **Domain** (business objects)
- **Application** (business logic)
- **Infrastructure** (external systems)
- **Presentation** (HTTP interfaces)


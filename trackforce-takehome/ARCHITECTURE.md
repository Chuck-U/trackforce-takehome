# Architecture Documentation

## Overview

This Laravel application follows SOLID design principles and implements a clean architecture pattern for handling employee data from multiple identity providers and forwarding it to TrackTik's REST API.

## Architecture Layers

### 1. Controllers Layer (`app/Http/Controllers/Api/`)
- **Provider1EmployeeController** - Handles HTTP requests for Provider 1
- **Provider2EmployeeController** - Handles HTTP requests for Provider 2

**Responsibilities:**
- HTTP request/response handling
- Input validation (via Form Requests)
- Delegation to service layer
- Response formatting

### 2. Service Layer (`app/Services/`)

#### Core Services
- **EmployeeProcessingService** - Orchestrates employee create/update operations
- **TrackTikService** - Main service for TrackTik API interactions (implements TrackTikServiceInterface)

#### Specialized Services
- **OAuth2TokenManager** - Handles OAuth2 token acquisition and caching
- **TrackTikApiClient** - Manages HTTP requests to TrackTik API

#### Mapping Services
- **Provider1EmployeeMapper** - Maps Provider 1 data to TrackTik schema
- **Provider2EmployeeMapper** - Maps Provider 2 data to TrackTik schema

#### Status Mapping Strategy
- **StatusMapperInterface** - Contract for status mapping
- **Provider1StatusMapper** - Provider 1 specific status mapping
- **Provider2StatusMapper** - Provider 2 specific status mapping

#### Logging Services
- **RequestLogger** - Handles request/response logging
- **LogLevelResolver** - Determines appropriate log levels
- **SensitiveDataSanitizer** - Sanitizes sensitive data in logs

### 3. Repository Layer (`app/Repositories/`)
- **EmployeeRepository** - Implements EmployeeRepositoryInterface for database operations

### 4. Domain Layer (`app/Domain/DataTransferObjects/`)
- **TrackTikEmployeeData** - DTO for TrackTik employee data
- **Provider1EmployeeData** - DTO for Provider 1 employee data
- **Provider2EmployeeData** - DTO for Provider 2 employee data
- **TrackTikResponse** - DTO for TrackTik API responses

### 5. Contracts Layer (`app/Contracts/`)
- **TrackTikServiceInterface** - Contract for TrackTik service operations
- **EmployeeRepositoryInterface** - Contract for employee repository operations

### 6. Middleware Layer (`app/Http/Middleware/`)
- **ValidateProviderToken** - Validates provider authentication tokens
- **LogApiRequests** - Logs API requests and responses

### 7. Response Layer (`app/Http/Responses/`)
- **ApiResponseFactory** - Standardizes API response formatting

## SOLID Principles Implementation

### Single Responsibility Principle (SRP)
- **TrackTikService** split into:
  - `OAuth2TokenManager` - Token management only
  - `TrackTikApiClient` - HTTP operations only
  - `TrackTikService` - Business logic orchestration only
- **Controllers** simplified to handle only HTTP concerns
- **LogApiRequests** middleware split into specialized logging services

### Open/Closed Principle (OCP)
- **StatusMapperInterface** allows adding new providers without modifying existing code
- **EmployeeMapperInterface** enables new provider implementations
- **Strategy pattern** for status mapping eliminates code duplication

### Liskov Substitution Principle (LSP)
- All interface implementations are fully substitutable
- Status mappers can be swapped without breaking functionality

### Interface Segregation Principle (ISP)
- **TrackTikServiceInterface** contains only necessary methods
- **EmployeeRepositoryInterface** focused on employee-specific operations
- **StatusMapperInterface** separated from general mapping concerns

### Dependency Inversion Principle (DIP)
- Controllers depend on service interfaces, not concrete implementations
- Services depend on repository interfaces
- All dependencies injected via constructor injection
- Service provider handles interface-to-implementation bindings

## Service Provider Configuration

The `AppServiceProvider` registers all interface bindings with contextual binding for status mappers:

```php
// Repository bindings
$this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);

// TrackTik service bindings
$this->app->bind(TrackTikServiceInterface::class, TrackTikService::class);

// Contextual binding for status mappers
$this->app->when(Provider1EmployeeMapper::class)
    ->needs(StatusMapperInterface::class)
    ->give(Provider1StatusMapper::class);
```

## Adding New Providers

To add a new provider (e.g., Provider 3):

1. **Create Provider-Specific Classes:**
   - `Provider3EmployeeData` DTO
   - `Provider3EmployeeMapper` implementing `EmployeeMapperInterface`
   - `Provider3StatusMapper` implementing `StatusMapperInterface`
   - `Provider3EmployeeController`

2. **Register in Service Provider:**
   ```php
   $this->app->when(Provider3EmployeeMapper::class)
       ->needs(StatusMapperInterface::class)
       ->give(Provider3StatusMapper::class);
   ```

3. **Add Routes:**
   ```php
   Route::post('/provider3/employees', [Provider3EmployeeController::class, 'store']);
   ```

4. **Create Tests:**
   - Unit tests for mapper and status mapper
   - Feature tests for controller

## Data Flow

```
Provider API → Controller → EmployeeProcessingService → TrackTikService → TrackTik API
                ↓                    ↓                        ↓
            FormRequest         EmployeeRepository      OAuth2TokenManager
                ↓                    ↓                        ↓
            Validation          Database Storage        Token Cache
```

## Testing Strategy

- **Unit Tests:** Test individual classes in isolation with mocked dependencies
- **Feature Tests:** Test complete API endpoints with real database
- **Interface Mocking:** All external dependencies mocked via interfaces

## Benefits of This Architecture

1. **Maintainability:** Clear separation of concerns
2. **Testability:** Easy to mock dependencies and test in isolation
3. **Extensibility:** Easy to add new providers without modifying existing code
4. **Flexibility:** Services can be swapped or extended independently
5. **Code Reuse:** Common functionality extracted into reusable services
6. **Type Safety:** Strong typing with DTOs and interfaces
7. **Error Handling:** Centralized error handling and response formatting

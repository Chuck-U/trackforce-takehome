# Data Transfer Objects (DTOs)

This directory contains immutable Data Transfer Objects that represent employee data from different providers following Clean Architecture principles.

## Structure

```
DataTransferObjects/
├── Provider1EmployeeData.php    # Provider 1 employee schema
├── Provider2EmployeeData.php    # Provider 2 employee schema (nested)
├── TrackTikEmployeeData.php     # TrackTik API schema
└── README.md                     # This file
```

## Quick Start

### Creating a DTO from Request Data

```php
use App\Domain\DataTransferObjects\Provider1EmployeeData;

$dto = Provider1EmployeeData::fromArray($request->validated());
```

### Using DTOs with Mappers

```php
use App\Services\Provider1EmployeeMapper;

$mapper = new Provider1EmployeeMapper();
$trackTikData = $mapper->mapToTrackTik($providerDto);
```

### Converting to Array for APIs

```php
$apiPayload = $trackTikData->toArray();
```

## Key Features

- ✅ **Readonly Properties**: Immutable after creation
- ✅ **Type Safety**: Full PHP type checking
- ✅ **Named Arguments**: Clear and self-documenting
- ✅ **IDE Support**: Autocomplete and refactoring
- ✅ **Clean Architecture**: Domain layer separation

## Available DTOs

| DTO | Purpose | Schema File |
|-----|---------|-------------|
| `Provider1EmployeeData` | Provider 1 flat structure | `schemas/provider1-schema.json` |
| `Provider2EmployeeData` | Provider 2 nested structure | `schemas/provider2-schema.json` |
| `TrackTikEmployeeData` | TrackTik API format | `schemas/tracktik-schema.json` |

## Documentation

For comprehensive documentation, see:
- **[docs/DATA_TRANSFER_OBJECTS.md](../../../docs/DATA_TRANSFER_OBJECTS.md)** - Complete DTO guide
- **[schemas/](../../../../schemas/)** - JSON schemas for validation
- **[tests/Unit/Services/](../../../tests/Unit/Services/)** - Mapper tests using DTOs

## Design Principles

1. **Immutability**: All properties are `readonly`
2. **Factory Method**: Use `fromArray()` for creation from external data
3. **Flat Structure**: Even nested provider data is flattened in DTOs
4. **Null Safety**: Optional fields use nullable types (`?string`)
5. **No Business Logic**: Pure data containers only

## Testing

All DTOs are thoroughly tested via:
- Unit tests for mappers
- Feature tests for API endpoints
- Integration tests for full flow

Run tests:
```bash
php artisan test --filter=EmployeeMapperTest
```

## Adding New DTOs

1. Create new DTO class in this directory
2. Implement constructor with readonly properties
3. Add `fromArray()` static factory method
4. Add `toArray()` instance method
5. Update mapper interface if needed
6. Write comprehensive tests

See [docs/DATA_TRANSFER_OBJECTS.md](../../../docs/DATA_TRANSFER_OBJECTS.md#adding-new-dtos) for detailed guide.


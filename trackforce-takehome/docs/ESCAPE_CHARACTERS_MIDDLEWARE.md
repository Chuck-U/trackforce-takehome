# Escape Characters Middleware

## Overview

The `CheckEscapeCharacters` middleware provides security by detecting and blocking requests containing escape characters in their input data. This helps prevent injection attacks and malformed data from entering the system.

## Implementation

### Files Created

1. **Exception**: `app/Exceptions/EscapeCharacterException.php`
   - Custom exception thrown when escape characters are detected
   - Returns a 400 JSON response with details about the violation

2. **Middleware**: `app/Http/Middleware/CheckEscapeCharacters.php`
   - Inspects all incoming request data recursively
   - Detects various types of escape characters and sequences
   - Throws `EscapeCharacterException` when violations are found

### Detection Capabilities

The middleware detects:
- **Backslash characters** (`\`)
- **Control characters** (ASCII 0-31, excluding normal whitespace)
- **Literal escape sequences**: `\n`, `\r`, `\t`, `\0`
- **Hex escape sequences**: `\x1b`, `\x00`, etc.
- **Unicode escape sequences**: `\u0000`, etc.

### Applied Routes

The middleware is applied to:
- `POST /api/provider1/employees`
- `POST /api/provider2/employees`

### Configuration

The middleware is registered in `bootstrap/app.php` with the alias `check.escape`:

```php
$middleware->alias([
    'check.escape' => \App\Http\Middleware\CheckEscapeCharacters::class,
]);
```

Applied to routes in:
- `routes/provider1.php`
- `routes/api.php` (Provider 2)

### Response Format

When escape characters are detected, the middleware returns:

```json
{
    "error": "Invalid Input",
    "message": "Escape characters detected in field 'field_name': value...",
    "status": 400
}
```

### Testing

Comprehensive test coverage is provided in:
- `tests/Feature/CheckEscapeCharactersMiddlewareTest.php`

Tests include:
- Valid input passing through middleware
- Detection of various escape character types
- Nested object validation
- Control character detection
- Both provider routes validation

## Usage

### Applying to Additional Routes

To apply this middleware to other routes, simply add `->middleware('check.escape')`:

```php
Route::post('/your-route', [YourController::class, 'method'])
    ->middleware('check.escape');
```

### Customizing Detection

To customize what characters are detected, modify the validation logic in:
- `CheckEscapeCharacters::containsEscapeCharacters()`
- `CheckEscapeCharacters::containsLiteralEscapeSequences()`

## Security Benefits

1. **Injection Prevention**: Blocks common escape sequences used in injection attacks
2. **Data Integrity**: Ensures clean, expected data enters the system
3. **Early Validation**: Catches issues before they reach business logic
4. **Clear Error Messages**: Provides specific feedback about violations

## Performance

The middleware performs recursive checks on all input data. For large payloads:
- Uses efficient string functions (`strpos`, `preg_match`)
- Short-circuits on first violation found
- Minimal overhead for valid requests


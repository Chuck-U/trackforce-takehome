# API Logging Middleware Documentation

## Overview

The `LogApiRequests` middleware provides comprehensive logging for all API requests with dynamic log levels based on response status codes and request patterns.

## Features

- **Automatic Log Level Detection**: Adjusts log levels based on HTTP status codes
- **Request/Response Logging**: Logs incoming requests and outgoing responses
- **Execution Time Tracking**: Measures and logs request execution time
- **Provider Detection**: Automatically identifies which provider is making the request
- **Sensitive Data Sanitization**: Redacts sensitive fields like passwords and tokens
- **Error Context**: Includes full response body for error status codes

## Log Levels

### Request Logging

- **Debug**: Health check and status endpoints
- **Info**: Provider endpoints and general API requests

### Response Logging

- **Info**: Successful responses (200-299)
- **Warning**: Client errors (400-499)
- **Error**: Server errors (500-599)

## Logged Information

### Request Context

```json
{
  "method": "POST",
  "url": "http://localhost/api/provider1/employees",
  "ip": "127.0.0.1",
  "user_agent": "curl/7.68.0",
  "provider": "provider1",
  "body": {
    "emp_id": "P1_001",
    "first_name": "John",
    "last_name": "Doe",
    "email_address": "john@example.com"
  }
}
```

### Response Context

```json
{
  "method": "POST",
  "url": "http://localhost/api/provider1/employees",
  "status_code": 201,
  "execution_time_ms": 245.67,
  "provider": "provider1"
}
```

### Error Response Context

For responses with status codes >= 400, the full response body is included:

```json
{
  "method": "POST",
  "url": "http://localhost/api/provider1/employees",
  "status_code": 400,
  "execution_time_ms": 12.34,
  "provider": "provider1",
  "response": {
    "success": false,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "Invalid employee data",
      "details": [...]
    }
  }
}
```

## Sensitive Data Sanitization

The following fields are automatically redacted from logs:

- `password`
- `password_confirmation`
- `token`
- `api_key`
- `secret`
- `authorization`

Example:
```php
// Original request
["password" => "secret123"]

// Logged as
["password" => "[REDACTED]"]
```

## Configuration

### Enabling the Middleware

The middleware is automatically applied to all API routes via `bootstrap/app.php`:

```php
$middleware->api(append: [
    \App\Http\Middleware\LogApiRequests::class,
]);
```

### Manual Application

You can also apply it to specific routes using the alias:

```php
Route::post('/api/endpoint', [Controller::class, 'method'])
    ->middleware('log.api');
```

## Usage Examples

### Viewing Logs

Logs are written to the configured log channel (typically `storage/logs/laravel.log`):

```bash
# View all API logs
tail -f storage/logs/laravel.log | grep "API Request"

# View only errors
tail -f storage/logs/laravel.log | grep "API Request Failed"

# View logs for a specific provider
tail -f storage/logs/laravel.log | grep "provider1"
```

### Log Samples

**Successful Request:**
```
[2025-10-27 14:30:15] local.INFO: API Request Received {"method":"POST","url":"http://localhost/api/provider1/employees",...}
[2025-10-27 14:30:15] local.INFO: API Request Successful {"method":"POST","status_code":201,"execution_time_ms":245.67,...}
```

**Client Error:**
```
[2025-10-27 14:30:20] local.INFO: API Request Received {"method":"POST","url":"http://localhost/api/provider1/employees",...}
[2025-10-27 14:30:20] local.WARNING: API Request Failed - Client Error {"status_code":400,"response":{...}}
```

**Server Error:**
```
[2025-10-27 14:30:25] local.INFO: API Request Received {"method":"POST","url":"http://localhost/api/provider2/employees",...}
[2025-10-27 14:30:25] local.ERROR: API Request Failed - Server Error {"status_code":500,"response":{...}}
```

## Performance Impact

- Minimal overhead (~2-5ms per request)
- Execution time is measured using `microtime(true)`
- Logging is asynchronous and won't block responses
- Consider using a dedicated log channel for high-traffic applications

## Integration with Log Aggregators

The structured logging format works seamlessly with log aggregation tools:

### Elasticsearch/Logstash

```conf
filter {
  json {
    source => "message"
  }
}
```

### CloudWatch

```php
// config/logging.php
'cloudwatch' => [
    'driver' => 'custom',
    'via' => CloudWatchLogger::class,
    'formatter' => \Monolog\Formatter\JsonFormatter::class,
],
```

### Datadog

```php
// config/logging.php
'datadog' => [
    'driver' => 'monolog',
    'handler' => DatadogHandler::class,
    'formatter' => \Monolog\Formatter\JsonFormatter::class,
],
```

## Advanced Customization

### Adding Custom Context

Extend the middleware to add custom context:

```php
// In LogApiRequests::logRequest()
$context['custom_field'] = $this->getCustomData($request);
```

### Changing Log Levels

Modify the `getLogLevel()` and `getResponseLogLevel()` methods:

```php
private function getLogLevel(Request $request): string
{
    if ($request->user()?->isAdmin()) {
        return 'debug';
    }
    
    return 'info';
}
```

### Filtering Sensitive Routes

Skip logging for specific routes:

```php
public function handle(Request $request, Closure $next): Response
{
    if (str_contains($request->path(), 'sensitive')) {
        return $next($request);
    }
    
    // Continue with logging...
}
```

## Testing

The middleware is tested to ensure:

- ✅ Successful requests pass through without issues
- ✅ Error responses are properly logged
- ✅ Provider detection works correctly
- ✅ Normal application flow is not disrupted

Run tests:
```bash
php artisan test --filter=LogApiRequestsTest
```

## Troubleshooting

### Logs Not Appearing

1. Check log configuration in `config/logging.php`
2. Verify log file permissions: `chmod 777 storage/logs`
3. Check disk space: `df -h`
4. Verify middleware is registered in `bootstrap/app.php`

### Performance Issues

1. Consider using queue-based logging for high traffic
2. Use separate log channels for different severity levels
3. Implement log rotation
4. Consider external log aggregation services

### Sensitive Data Still Appearing

1. Add field names to the `$sensitiveFields` array in `sanitizeRequestData()`
2. Implement custom sanitization logic for nested structures
3. Test with actual data to verify redaction

## Best Practices

1. **Regular Log Rotation**: Implement log rotation to prevent disk space issues
2. **Monitoring**: Set up alerts for high error rates
3. **Privacy Compliance**: Ensure PII is properly redacted
4. **Performance Monitoring**: Track execution times to identify slow endpoints
5. **Log Retention**: Define and implement log retention policies

## Related Documentation

- [Laravel Logging](https://laravel.com/docs/logging)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/)


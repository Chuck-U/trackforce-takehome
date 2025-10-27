# Swagger API Documentation

## Overview
The TrackForce Employee Integration API now includes comprehensive Swagger/OpenAPI documentation for all API endpoints.

## Accessing the Documentation

### Swagger UI Interface
Once the application is running, you can access the interactive Swagger UI at:

```
http://localhost:8000/api/documentation
```

This provides a user-friendly interface where you can:
- View all available API endpoints
- See detailed request/response schemas
- Test API endpoints directly from your browser
- View example requests and responses

### API Specification Files
The OpenAPI specification is also available in JSON format at:

```
http://localhost:8000/docs/api-docs.json
```

Or you can access the file directly at:
```
storage/api-docs/api-docs.json
```

## Available Endpoints

### Provider 1 Endpoints
- **POST /api/provider1/employees** - Create or update an employee from Provider 1
  - Accepts flat JSON structure with fields like `emp_id`, `first_name`, `last_name`, etc.

### Provider 2 Endpoints
- **POST /api/provider2/employees** - Create or update an employee from Provider 2
  - Accepts nested JSON structure with `employee_number`, `personal_info`, and `work_info` objects

## Authentication
The API supports Bearer token authentication (JWT). To use authenticated endpoints:
1. Add the `provider.auth` middleware to the routes in `routes/api.php`
2. Include the Bearer token in the Authorization header: `Authorization: Bearer YOUR_TOKEN`

## Regenerating Documentation

If you make changes to the API endpoints or annotations, regenerate the documentation with:

```bash
php artisan l5-swagger:generate
```

## Configuration

The Swagger configuration can be customized in:
```
config/l5-swagger.php
```

Key configuration options:
- Route for documentation UI: `api/documentation` (default)
- Annotations path: `app/` directory (scans all controllers)
- Documentation format: JSON (can be changed to YAML)

## OpenAPI Annotations

The API documentation is generated from PHP attributes (annotations) in the controllers:
- Main API info: `app/Http/Controllers/Controller.php`
- Provider 1 endpoints: `app/Http/Controllers/Api/Provider1EmployeeController.php`
- Provider 2 endpoints: `app/Http/Controllers/Api/Provider2EmployeeController.php`

## Example Request (Provider 1)

```json
{
  "emp_id": "EMP001",
  "first_name": "John",
  "last_name": "Doe",
  "email_address": "john.doe@example.com",
  "phone": "555-1234",
  "job_title": "Security Officer",
  "dept": "Security",
  "hire_date": "2024-01-15",
  "employment_status": "active"
}
```

## Example Request (Provider 2)

```json
{
  "employee_number": "EMP2001",
  "personal_info": {
    "given_name": "Jane",
    "family_name": "Smith",
    "email": "jane.smith@example.com",
    "mobile": "555-5678"
  },
  "work_info": {
    "role": "Security Guard",
    "division": "Operations",
    "start_date": "2024-02-01",
    "current_status": "employed"
  }
}
```

## Response Format

All endpoints return a consistent response format:

### Success Response (201/200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employeeId": "EMP001",
    "provider": "provider1",
    "tracktikId": "tt-12345",
    "message": "Employee created successfully"
  }
}
```

### Error Response (400/500)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid employee data",
    "details": [
      {
        "field": "email_address",
        "message": "The email address field is required."
      }
    ]
  }
}
```

## Testing with Swagger UI

1. Navigate to `http://localhost:8000/api/documentation`
2. Click on any endpoint to expand it
3. Click "Try it out" button
4. Fill in the request body with sample data
5. Click "Execute" to send the request
6. View the response directly in the UI

## Additional Resources

- [OpenAPI Specification](https://swagger.io/specification/)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [Swagger PHP Documentation](https://github.com/zircote/swagger-php)



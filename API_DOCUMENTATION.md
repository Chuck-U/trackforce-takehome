# TrackTik API Documentation

## Base URL
`https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/v1`

## Authentication

### OAuth2 Token Request
```http
POST https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials&
client_id=tracktik_test_728e5c61ad2543d6&
client_secret=secret_4de5efe9c29941a9849278f321830e59&
scope=employees:read employees:write
```

### Response
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "employees:read employees:write"
}
```

## Employees API

### Create Employee
```http
POST /employees
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john.doe@example.com",
  "phoneNumber": "+1234567890",
  "position": "Security Guard",
  "department": "Security",
  "startDate": "2024-01-15",
  "employeeId": "EMP001",
  "status": "active"
}
```

### Update Employee
```http
PUT /employees/{employeeId}
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "firstName": "John",
  "lastName": "Smith",
  "email": "john.smith@example.com",
  "phoneNumber": "+1234567890",
  "position": "Senior Security Guard",
  "department": "Security",
  "status": "active"
}
```

### Get Employee
```http
GET /employees/{employeeId}
Authorization: Bearer {access_token}
```

### Response Format
```json
{
  "success": true,
  "data": {
    "id": "uuid-here",
    "employeeId": "EMP001",
    "firstName": "John",
    "lastName": "Doe",
    "email": "john.doe@example.com",
    "phoneNumber": "+1234567890",
    "position": "Security Guard",
    "department": "Security",
    "startDate": "2024-01-15",
    "status": "active",
    "createdAt": "2024-01-15T10:00:00Z",
    "updatedAt": "2024-01-15T10:00:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid employee data",
    "details": [
      {
        "field": "email",
        "message": "Invalid email format"
      }
    ]
  }
}
```

## Rate Limits
- 100 requests per minute per client
- 1000 requests per hour per client

## Submission API

### Submit Technical Test Solution
```http
POST /submit
Content-Type: application/json

{
  "testPackageId": "your-test-package-id",
  "githubUrl": "https://github.com/yourusername/your-repo",
  "repositoryName": "your-repo",
  "notes": "Optional notes about your implementation"
}
```

### Response Format
```json
{
  "success": true,
  "submissionId": "uuid-here",
  "message": "Submission received successfully",
  "status": "processing"
}
```

### Error Response
```json
{
  "error": "Invalid test package ID",
  "message": "Test package not found or expired"
}
```

## Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict (already submitted)
- `410` - Gone (test package expired)
- `429` - Rate Limited
- `500` - Internal Server Error

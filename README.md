# TrackTik Employee API

A Laravel-based REST API that receives employee data from multiple identity providers and forwards it to TrackTik's REST API with OAuth2 authentication.

## Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- SQLite (default) or your preferred database

### Installation

1. **Clone and navigate to the project**
   ```bash
   cd trackforce-takehome
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   add values to environment for tracktik values
   php artisan key:generate
   ```

4. **Set up database**
   ```bash
   php artisan migrate
   ```

5. **Start the server**
   ```bash
   php artisan serve
   ```
   
   API available at: `http://localhost:8000`

## Configuration

### Environment Variables (.env)

#### Application Settings
```env
APP_NAME=TrackTikAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

#### Database (SQLite default)
```env
DB_CONNECTION=sqlite
# Database file created at: database/database.sqlite
```

For MySQL/PostgreSQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trackforce
DB_USERNAME=root
DB_PASSWORD=
```

#### TrackTik OAuth2 Configuration
```env
TRACKTIK_CLIENT_ID=tracktik_test_728e5c61ad2543d6
TRACKTIK_CLIENT_SECRET=secret_4de5efe9c29941a9849278f321830e59
TRACKTIK_TOKEN_URL=https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/oauth/token
TRACKTIK_BASE_URL=https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/v1
TRACKTIK_SCOPE="employees:read employees:write"
```

> **Note**: OAuth credentials are available in `oauth-credentials.json` and expire after 7 days.

## API Endpoints

### Base URL
```
http://localhost:8000/api
```


#### *NOTE* 
Production environment allows for using a bearer token for other services that may handle oauth on their end. see [`routes/api.php`](routes/api.php).


# Authentication
All endpoints require a Bearer token:
```bash
Authorization: Bearer your-token-here
```

### Provider 1 Endpoints
```bash
# Create/Update Employee
POST   /provider1/employees

# Get Employee
GET    /provider1/employees/{employee_id}

# Update Employee  
PUT    /provider1/employees/{employee_id}
```

### Provider 2 Endpoints
```bash
# Create/Update Employee
POST   /provider2/employees

# Get Employee
GET    /provider2/employees/{employee_id}

# Update Employee
PUT    /provider2/employees/{employee_id}
```

## API Schemas

### Provider 1 (Flat Structure)
```json
{
  "emp_id": "EMP001",
  "first_name": "John",
  "last_name": "Doe",
  "email_address": "john.doe@example.com",
  "phone": "+1-555-0101",
  "job_title": "Security Officer",
  "dept": "Security",
  "hire_date": "2024-01-15",
  "employment_status": "active"
}
```

### Provider 2 (Nested Structure)
```json
{
  "employee_number": "EMP2001",
  "personal_info": {
    "given_name": "Jane",
    "family_name": "Smith",
    "email": "jane.smith@example.com",
    "mobile": "+1-555-0201"
  },
  "work_info": {
    "role": "Security Guard",
    "division": "Operations",
    "start_date": "2024-02-01",
    "current_status": "employed"
  }
}
```

## Testing

### Run All Tests
```bash
composer test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Test Coverage
- ✅ 63 tests with 204 assertions
- Unit tests for mappers and services
- Feature tests for API endpoints
- OAuth authentication flow tests
- Cross-schema validation tests

## Interactive Documentation

### Swagger UI
Once the server is running, visit:
```
http://localhost:8000/api/documentation
```

Features:
- Interactive API testing
- Request/response examples
- Schema definitions
- Try-it-out functionality

### Generate Swagger Docs
```bash
php artisan l5-swagger:generate
```

## Architecture

```
Provider 1/2 → Laravel API → OAuth2 → TrackTik API
                    ↓
                Database (SQLite)
```

### Key Components
- **Controllers**: Handle HTTP requests and validation
- **Services**: Business logic and processing
- **Mappers**: Transform provider schemas to TrackTik format
- **Repositories**: Database abstraction layer
- **Middleware**: Authentication, logging, security

## Development

### Composer Scripts
```bash
composer setup      # Full setup (install, copy env, key, migrate)
composer dev        # Start development server
composer test       # Run tests
composer lint       # Code style check
composer format     # Auto-format code
```

### Code Quality
- PSR-12 coding standard
- Laravel Pint for formatting
- Pest for testing
- Comprehensive error handling

## Security

- ✅ OAuth2 authentication for TrackTik API
- ✅ Bearer token authentication for endpoints
- ✅ Input validation and sanitization
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Escape character detection
- ✅ Rate limiting ready

## Troubleshooting

### Database Issues
```bash
# Reset database
php artisan migrate:fresh

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Project Structure

```
trackforce-takehome/
├── app/
│   ├── Contracts/           # Interfaces
│   ├── Domain/             # DTOs and domain logic
│   ├── Http/
│   │   ├── Controllers/    # API controllers
│   │   ├── Middleware/     # Custom middleware
│   │   └── Requests/       # Form requests
│   ├── Models/             # Eloquent models
│   ├── Repositories/       # Data access layer
│   └── Services/           # Business logic
├── config/                 # Configuration files
├── database/
│   ├── migrations/         # Database migrations
│   └── factories/          # Test factories
├── routes/
│   └── api.php            # API routes
├── tests/
│   ├── Feature/           # Integration tests
│   └── Unit/              # Unit tests
├── .env                   # Environment config
└── composer.json          # Dependencies
```

## Documentation

- `API_DOCUMENTATION.md` - TrackTik API reference
- `IMPLEMENTATION.md` - Implementation details
- `TESTING_QUICK_REFERENCE.md` - Testing guide
- `schemas/` - JSON schemas for both providers
- `sample-data/` - Example payloads



---

**Test ID**: `585e1213-da80-4f33-8a90-baf07d55ab4d`

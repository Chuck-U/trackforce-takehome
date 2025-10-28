# TrackTik Technical Test

## Test ID: 585e1213-da80-4f33-8a90-baf07d55ab4d

## Overview

Welcome to the TrackTik technical assessment! We would like you to create a small API that receives employee data from two different identity providers and forwards it to TrackTik's REST API.

## Requirements

### Core Functionality
1. **API Endpoints**: Create endpoints to receive employee data from two different identity providers
2. **Data Mapping**: Map each provider's employee schema to TrackTik's employee schema
3. **API Integration**: Forward mapped data to TrackTik's REST API
4. **Authentication**: Implement OAuth2 authentication for TrackTik API calls

### Technical Requirements
- **Language**: PHP (any framework of your choice)
- **Database**: Any database system you prefer
- **Authentication**: OAuth2 implementation for TrackTik API
- **Error Handling**: Proper error handling and logging
- **Testing**: Unit tests with good coverage
- **Documentation**: Clear README and API documentation

## Installation & Setup

This is a **backend-only API application** built with Laravel. No frontend or React components are included.

### Prerequisites
- PHP 8.2 or higher
- Composer
- SQLite (or your preferred database)

### Setup Instructions

1. **Navigate to the application directory**
   ```bash
   cd trackforce-takehome
   ```

2. **Run the setup script**
   ```bash
   composer setup
   ```
   This will:
   - Install dependencies
   - Copy `.env.example` to `.env`
   - Generate application key
   - Run database migrations

3. **Start the development server**
   ```bash
   composer dev
   ```
   The API will be available at `http://localhost:8000`

### Getting Started

1. **Review the schemas** in the `schemas/` directory
2. **Check sample data** in the `sample-data/` directory
3. **Read API documentation** in `API_DOCUMENTATION.md`
4. **Use OAuth2 credentials** from `oauth-credentials.json`
5. **Check submission format** in `sample-submission-payload.json`

## API Documentation

### Interactive Swagger Documentation
The API includes comprehensive Swagger/OpenAPI documentation. Once the application is running, access it at:

**Swagger UI**: `http://localhost:8000/api/documentation`

The Swagger UI provides:
- Interactive API testing interface
- Detailed request/response schemas for all endpoints
- Example payloads for both Provider 1 and Provider 2
- Real-time API testing capabilities

For more details, see `SWAGGER_API_DOCS.md`

## Testing & Code Coverage

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test:coverage

# Generate HTML coverage report
composer coverage

# View coverage report
xdg-open storage/coverage/html/index.html  # Linux
open storage/coverage/html/index.html       # macOS
```

### Code Coverage Reports

The project is configured to generate comprehensive code coverage reports:
- **HTML Report**: `storage/coverage/html/index.html` (interactive, detailed)
- **Clover XML**: `storage/coverage/clover.xml` (for CI/CD)
- **Text Report**: `storage/coverage/coverage.txt` (quick summary)

### Debugging with Xdebug

The project includes complete Xdebug setup for step debugging and profiling:

1. **Install Xdebug**: See `XDEBUG_SETUP.md` for detailed instructions
2. **VS Code Configuration**: Pre-configured in `.vscode/launch.json`
3. **Debug Tests**: Use the "Debug Pest Tests" launch configuration
4. **Debug API**: Use the "Listen for Xdebug" configuration

For detailed setup and usage, see:
- `XDEBUG_SETUP.md` - Complete Xdebug installation and configuration guide
- `CODE_COVERAGE.md` - Comprehensive code coverage documentation

## Architecture Diagram

```
Provider 1 API → Your API → TrackTik API
Provider 2 API → Your API → TrackTik API
```

## Submission

Submit your solution by calling our submission API with the simplified payload:

```bash
curl -X POST https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/submit \
  -H "Content-Type: application/json" \
  -d '{
    "testPackageId": "585e1213-da80-4f33-8a90-baf07d55ab4d",
    "githubUrl": "https://github.com/yourusername/your-repo"
  }'
```

**Sample Payload:** Check the `sample-submission-payload.json` file included in this package for the exact format.

**Important:** You only need your testPackageId (585e1213-da80-4f33-8a90-baf07d55ab4d) and your GitHub repository URL. Your candidate information is automatically retrieved from the test package.

**Submission Endpoint:** `https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/submit`

## Evaluation Criteria

Your solution will be evaluated on:
- **Code Quality** (25%): Clean, readable, maintainable code
- **Architecture** (20%): Scalable design, proper use of design patterns
- **API Design** (20%): RESTful principles, proper error handling
- **Security** (15%): Input validation, SQL injection prevention
- **Testing** (10%): Test coverage and quality
- **Documentation** (10%): Clear instructions and API docs

## Time Expectation

This test should take approximately 4-6 hours to complete. Focus on demonstrating your best practices and technical skills.

## Questions?

If you have any questions, please don't hesitate to reach out to your recruiter.

Good luck! 🚀

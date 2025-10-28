# Code Coverage Guide

This document explains how to generate, view, and interpret code coverage reports for the TrackForce project.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Running Coverage Reports](#running-coverage-reports)
- [Understanding Coverage Metrics](#understanding-coverage-metrics)
- [Viewing Coverage Reports](#viewing-coverage-reports)
- [Coverage Configuration](#coverage-configuration)
- [Best Practices](#best-practices)
- [CI/CD Integration](#cicd-integration)

## Overview

Code coverage measures how much of your codebase is executed during testing. It helps identify:
- Untested code paths
- Areas needing more tests
- Dead or unreachable code
- Critical paths that need thorough testing

## Quick Start

### Prerequisites

Ensure you have one of these installed:
- **Xdebug** (for debugging + coverage) - See [XDEBUG_SETUP.md](XDEBUG_SETUP.md)
- **PCOV** (for coverage only - faster)

### Generate Coverage Report

```bash
# Simple terminal report
composer test:coverage

# Full HTML report (recommended)
composer coverage

# Open the HTML report in browser
xdg-open storage/coverage/html/index.html  # Linux
open storage/coverage/html/index.html       # macOS
start storage/coverage/html/index.html      # Windows
```

## Running Coverage Reports

### Available Commands

```bash
# Terminal output with coverage summary
composer test:coverage

# Generate HTML report
composer test:coverage-html

# Generate Clover XML (for CI/CD)
composer test:coverage-clover

# Full report with terminal message
composer coverage
```

### Manual Commands

```bash
# Using Pest/PHPUnit directly
php artisan test --coverage

# With minimum coverage threshold
php artisan test --coverage --min=80

# Coverage for specific test suite
php artisan test --testsuite=Unit --coverage

# Coverage for specific file
php artisan test tests/Unit/Services/Provider1EmployeeMapperTest.php --coverage

# Using Xdebug explicitly
XDEBUG_MODE=coverage php artisan test --coverage
```

## Understanding Coverage Metrics

### Types of Coverage

#### 1. Line Coverage
- **What**: Percentage of executable lines that were executed
- **Goal**: Aim for 80%+ on business logic
- **Example**: If a file has 100 lines of code and 80 were executed, that's 80% line coverage

#### 2. Method Coverage
- **What**: Percentage of methods/functions that were called
- **Goal**: Aim for 90%+ on critical classes
- **Example**: Class has 10 methods, 9 were called = 90% method coverage

#### 3. Class Coverage
- **What**: Percentage of classes that had at least one method called
- **Goal**: 100% on application code (excluding framework classes)

### Coverage Thresholds

The project uses these thresholds (configured in `phpunit.xml`):

- **< 50%**: Low coverage (red) - Needs immediate attention
- **50-79%**: Medium coverage (yellow) - Needs improvement
- **80-100%**: High coverage (green) - Good quality

## Viewing Coverage Reports

### Terminal Output

```bash
composer test:coverage
```

Output example:
```
Tests:    25 passed (132 assertions)
Duration: 0.52s

  App\Services\Provider1EmployeeMapper ................ 100.0 %
  App\Services\Provider2EmployeeMapper ................ 100.0 %
  App\Http\Controllers\Api\Provider1EmployeeController  85.2 %
  App\Http\Controllers\Api\Provider2EmployeeController  85.2 %
  
  Total Coverage ....................................... 87.5 %
```

### HTML Report

The HTML report provides the most detailed view:

```bash
composer coverage
```

**Location**: `storage/coverage/html/index.html`

#### HTML Report Features:

1. **Dashboard**
   - Overall coverage percentages
   - High/medium/low coverage indicators
   - Directory and file breakdown

2. **File-Level View**
   - Line-by-line coverage
   - Executed lines (green)
   - Not executed lines (red)
   - Dead code (gray)

3. **Interactive Features**
   - Click directories to drill down
   - Sort by coverage percentage
   - Filter by coverage level
   - Search for specific files

### Text Report

```bash
# View text report
cat storage/coverage/coverage.txt
```

Good for scripting and CI/CD pipelines.

### XML Reports

```bash
# Clover XML (Jenkins, CI/CD tools)
storage/coverage/clover.xml

# PHPUnit XML (detailed metrics)
storage/coverage/xml/
```

## Coverage Configuration

### PHPUnit Configuration (`phpunit.xml`)

```xml
<coverage
    includeUncoveredFiles="true"
    pathCoverage="false"
    ignoreDeprecatedCodeUnits="true"
    disableCodeCoverageIgnore="false"
>
    <report>
        <html outputDirectory="storage/coverage/html"/>
        <clover outputFile="storage/coverage/clover.xml"/>
        <text outputFile="storage/coverage/coverage.txt"/>
        <xml outputDirectory="storage/coverage/xml"/>
    </report>
</coverage>
```

### Source Configuration

**Included**:
```xml
<include>
    <directory>app</directory>
</include>
```

**Excluded** (framework boilerplate):
```xml
<exclude>
    <directory>app/Console</directory>
    <directory>app/Exceptions</directory>
    <file>app/Providers/AppServiceProvider.php</file>
</exclude>
```

### Ignoring Code in Coverage

Use annotations to exclude code from coverage:

```php
// @codeCoverageIgnore
public function debug()
{
    // Debugging code not counted in coverage
}

// @codeCoverageIgnoreStart
private function developerTool()
{
    // Multiple lines excluded
}
// @codeCoverageIgnoreEnd
```

## Best Practices

### 1. Focus on Business Logic

Prioritize coverage for:
- ✅ Services and business logic
- ✅ Domain models and DTOs
- ✅ Data mappers and transformers
- ✅ Custom validation logic
- ✅ API controllers (main flows)

Lower priority:
- ⚠️ Framework boilerplate
- ⚠️ Simple getters/setters
- ⚠️ Configuration files
- ⚠️ Database migrations

### 2. Set Realistic Goals

```
Recommended Coverage Targets:
- Services/Business Logic: 90-100%
- Controllers: 80-90%
- Models: 70-80%
- Overall Project: 80%+
```

### 3. Quality Over Quantity

- Don't chase 100% coverage blindly
- Focus on meaningful tests
- Test edge cases and error paths
- Avoid testing framework code

### 4. Regular Reviews

```bash
# Run coverage weekly or per sprint
composer coverage

# Review:
# - Which files have low coverage?
# - Are critical paths tested?
# - Are there untested edge cases?
```

### 5. Integration with Git Hooks

Create `.git/hooks/pre-push`:

```bash
#!/bin/bash
echo "Running tests with coverage..."
composer test:coverage --min=80

if [ $? -ne 0 ]; then
    echo "Coverage below threshold. Push aborted."
    exit 1
fi
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: pcov
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Tests with Coverage
        run: composer test:coverage-clover
      
      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: ./storage/coverage/clover.xml
          fail_ci_if_error: true
```

### GitLab CI Example

```yaml
test:
  image: php:8.2
  before_script:
    - pecl install pcov
    - docker-php-ext-enable pcov
    - composer install
  script:
    - composer test:coverage-clover
  coverage: '/^\s*Total Coverage\s*\:\s*(\d+\.\d+)\%/'
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: storage/coverage/clover.xml
```

### Coverage Badges

Add to your README.md:

```markdown
![Coverage](https://img.shields.io/badge/Coverage-87%25-green)
```

Or use services like:
- [Codecov](https://codecov.io/)
- [Coveralls](https://coveralls.io/)
- [Code Climate](https://codeclimate.com/)

## Troubleshooting

### Coverage Not Generated

1. **Check PHP extension**:
   ```bash
   php -m | grep -E 'xdebug|pcov'
   ```

2. **Verify configuration**:
   ```bash
   php -i | grep -E 'xdebug.mode|pcov.enabled'
   ```

3. **Ensure storage is writable**:
   ```bash
   chmod -R 775 storage/
   ```

4. **Clear cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Slow Coverage Generation

**Solution 1**: Use PCOV instead of Xdebug
```bash
sudo pecl install pcov
# Update PHP configuration to use PCOV
```

**Solution 2**: Exclude vendor directory (already configured)

**Solution 3**: Run coverage only on changed files
```bash
# Example with git diff
php artisan test --coverage tests/Unit/ChangedFileTest.php
```

### Missing Coverage for Some Files

Check `phpunit.xml` source configuration:
- Ensure files are in `<include>` section
- Verify they're not in `<exclude>` section

### Coverage Numbers Don't Match

Different tools calculate coverage differently:
- **Xdebug**: More accurate but slower
- **PCOV**: Faster but may differ slightly
- Use consistent tool for comparison

## Coverage Reports Location

```
storage/coverage/
├── html/
│   ├── index.html          # Main report (open this)
│   ├── dashboard.html      # Coverage dashboard
│   └── ...                 # Additional HTML files
├── xml/                    # PHPUnit XML format
├── clover.xml             # Clover XML (for CI)
└── coverage.txt           # Text summary
```

## Example Workflow

```bash
# 1. Make code changes
vim app/Services/SomeService.php

# 2. Write/update tests
vim tests/Unit/Services/SomeServiceTest.php

# 3. Run tests with coverage
composer test:coverage

# 4. If coverage drops, check HTML report
composer coverage
xdg-open storage/coverage/html/index.html

# 5. Identify uncovered lines
# 6. Add tests for uncovered code
# 7. Re-run coverage
composer test:coverage

# 8. Commit when satisfied
git add .
git commit -m "Add tests for SomeService"
```

## Key Metrics to Track

### Per Sprint/Release

- Overall coverage percentage
- Coverage delta (change since last release)
- Files with <50% coverage
- Critical paths coverage

### Per Pull Request

- New code coverage (should be >80%)
- Coverage shouldn't decrease
- Critical files maintain >90%

## Additional Resources

- [PHPUnit Code Coverage Documentation](https://phpunit.de/manual/current/en/code-coverage-analysis.html)
- [Pest PHP Testing Framework](https://pestphp.com/)
- [Martin Fowler - Test Coverage](https://martinfowler.com/bliki/TestCoverage.html)
- [XDEBUG_SETUP.md](XDEBUG_SETUP.md) - Xdebug installation guide

## Summary

✅ Run `composer coverage` regularly
✅ Aim for 80%+ overall coverage
✅ Focus on business logic first
✅ Review HTML reports for insights
✅ Integrate coverage into CI/CD
✅ Don't sacrifice test quality for coverage numbers


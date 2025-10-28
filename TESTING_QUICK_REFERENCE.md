# Testing & Coverage Quick Reference

Quick commands and tips for testing and code coverage in the TrackForce project.

## Quick Commands

### Testing

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/Services/Provider1EmployeeMapperTest.php

# Run with verbose output
php artisan test --verbose

# Stop on first failure
php artisan test --stop-on-failure
```

### Code Coverage

```bash
# Terminal coverage summary
composer test:coverage

# Full HTML report (opens in browser automatically)
composer coverage

# Specific formats
composer test:coverage-html    # HTML only
composer test:coverage-clover  # Clover XML only

# Manual with options
php artisan test --coverage --min=80
```

### Debugging

```bash
# With Xdebug
XDEBUG_MODE=debug php artisan test

# Debug specific test
XDEBUG_MODE=debug php artisan test tests/Unit/SomeTest.php

# Coverage only (no debugging)
XDEBUG_MODE=coverage php artisan test --coverage
```

## File Locations

```
Tests:
  tests/Unit/           - Unit tests
  tests/Feature/        - Feature/integration tests
  tests/Pest.php        - Pest configuration
  tests/TestCase.php    - Base test case

Coverage Reports:
  storage/coverage/html/index.html  - HTML report (main)
  storage/coverage/clover.xml       - Clover XML
  storage/coverage/coverage.txt     - Text summary
  storage/coverage/xml/             - Detailed XML

Configuration:
  phpunit.xml           - PHPUnit configuration
  .vscode/launch.json   - VS Code debug config
  .vscode/settings.json - VS Code settings
```

## VS Code Debugging

### Available Configurations

1. **Listen for Xdebug**: General debugging
2. **Debug Pest Tests**: Debug current test file
3. **Debug Artisan Command**: Debug CLI commands
4. **Debug Current Test File**: Debug active test

### How to Debug

1. Set breakpoint (click left of line number)
2. Press F5 or Run > Start Debugging
3. Select configuration (e.g., "Debug Pest Tests")
4. Run your code/test
5. Debugger pauses at breakpoint

### Keyboard Shortcuts

- `F5`: Start/Continue
- `F10`: Step Over
- `F11`: Step Into
- `Shift+F11`: Step Out
- `Shift+F5`: Stop

## Coverage Thresholds

```
< 50%   = Low (red)     - Needs attention
50-79%  = Medium (yellow) - Needs improvement
80-100% = High (green)  - Good coverage
```

## Common Issues

### Issue: Coverage not generated
```bash
# Check if Xdebug/PCOV is installed
php -m | grep -E 'xdebug|pcov'

# Set mode explicitly
XDEBUG_MODE=coverage composer test:coverage

# Check permissions
chmod -R 775 storage/
```

### Issue: Tests fail but work locally
```bash
# Clear config cache
php artisan config:clear

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Issue: Xdebug not working
```bash
# Check if enabled
php -v | grep Xdebug

# Check mode
php -i | grep xdebug.mode

# Enable if disabled
sudo phpenmod xdebug
```

## Best Practices

### Writing Tests

✅ **DO**:
- Test business logic thoroughly
- Test edge cases and error paths
- Use descriptive test names
- Mock external dependencies
- Keep tests fast and isolated

❌ **DON'T**:
- Test framework code
- Test getters/setters without logic
- Make external API calls in tests
- Rely on test execution order

### Coverage Goals

```
Priority Areas (90-100%):
- Services (business logic)
- Data mappers
- DTOs and domain models
- Custom validators

Medium Priority (70-90%):
- Controllers
- Middleware
- Form requests

Low Priority (<70%):
- Configuration files
- Service providers
- Console commands
```

## Pest Syntax Cheatsheet

```php
// Basic test
test('description', function () {
    expect(true)->toBeTrue();
});

// Using it()
it('does something', function () {
    // test code
});

// Setup
beforeEach(function () {
    // runs before each test
});

// Expectations
expect($value)->toBe($expected);
expect($value)->toEqual($expected);
expect($array)->toHaveCount(3);
expect($string)->toContain('text');
expect($value)->toBeNull();
expect($value)->toBeTrue();
expect($callable)->toThrow(Exception::class);

// Laravel specific
$this->assertDatabaseHas('table', ['field' => 'value']);
$this->actingAs($user);
$response = $this->post('/api/endpoint', $data);
$response->assertStatus(200);
$response->assertJson(['key' => 'value']);
```

## CI/CD Integration

### GitHub Actions

```yaml
- name: Run Tests with Coverage
  run: composer test:coverage-clover

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    files: ./storage/coverage/clover.xml
```

### Coverage Badge

Add to README.md:
```markdown
![Coverage](https://img.shields.io/badge/Coverage-87%25-green)
```

## Useful Links

- Full Docs: `XDEBUG_SETUP.md`, `CODE_COVERAGE.md`
- Pest Docs: https://pestphp.com/
- Laravel Testing: https://laravel.com/docs/testing
- PHPUnit: https://phpunit.de/

## Quick Troubleshooting

```bash
# Reset everything
php artisan config:clear
php artisan cache:clear
composer dump-autoload
chmod -R 775 storage/

# Check PHP/Xdebug info
php -v
php -m
php -i | grep xdebug

# Test without coverage (faster)
composer test

# Generate coverage again
composer coverage
```

## Environment Variables

```bash
# Enable debugging
export XDEBUG_MODE=debug

# Enable coverage
export XDEBUG_MODE=coverage

# Both
export XDEBUG_MODE=debug,coverage

# Disable Xdebug
export XDEBUG_MODE=off
```

## Tips

💡 **Run coverage regularly** to catch untested code early

💡 **Use HTML reports** for detailed analysis of uncovered lines

💡 **Focus on business logic** first, framework code later

💡 **Keep tests fast** - use mocks and in-memory databases

💡 **Use descriptive names** - tests are documentation

💡 **CI should fail** if coverage drops below threshold

---

**See Also**: `XDEBUG_SETUP.md` | `CODE_COVERAGE.md` | `README.md`


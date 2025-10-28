# Xdebug and Code Coverage Setup Summary

This document provides a quick overview of the Xdebug and code coverage setup for the TrackForce project.

## ✅ What Has Been Configured

### 1. PHPUnit Configuration (`phpunit.xml`)
- ✅ Coverage reporting configured (HTML, Clover, Text, XML)
- ✅ Source directories configured (app/)
- ✅ Exclusions set (Console, Exceptions, Providers)
- ✅ Coverage thresholds configured (50% low, 80% high)
- ✅ Cache directory configured

### 2. Composer Scripts (`composer.json`)
New test commands available:
```bash
composer test                  # Run tests without coverage
composer test:coverage         # Run tests with terminal coverage
composer test:coverage-html    # Generate HTML coverage report
composer test:coverage-clover  # Generate Clover XML report
composer coverage              # Full HTML report + message
```

### 3. VS Code Configuration (`.vscode/`)
- ✅ `launch.json` - Debugging configurations for:
  - General Xdebug listening
  - Debugging Pest tests
  - Debugging Artisan commands
  - Debugging current test file
- ✅ `settings.json` - Optimized workspace settings

### 4. Documentation
- ✅ `XDEBUG_SETUP.md` - Complete Xdebug installation guide
- ✅ `CODE_COVERAGE.md` - Comprehensive coverage documentation
- ✅ `TESTING_QUICK_REFERENCE.md` - Quick command reference
- ✅ `README.md` - Updated with testing section

### 5. Git Configuration (`.gitignore`)
- ✅ `storage/coverage/` - Ignored
- ✅ `storage/cache/` - Ignored
- ✅ `.vscode/` - Kept (useful for team)

### 6. Storage Directories
- ✅ `storage/cache/` - Created
- ✅ `storage/coverage/` - Created

## 📋 Next Steps

### For Development Team

1. **Install Xdebug** (one-time setup):
   ```bash
   # Ubuntu/Debian
   sudo pecl install xdebug
   # or
   sudo apt-get install php8.2-xdebug
   
   # macOS
   pecl install xdebug
   ```

2. **Configure Xdebug** (see `XDEBUG_SETUP.md` for details):
   ```ini
   # For coverage only (fastest)
   zend_extension=xdebug.so
   xdebug.mode=coverage
   
   # For debugging + coverage
   zend_extension=xdebug.so
   xdebug.mode=debug,coverage
   xdebug.start_with_request=trigger
   xdebug.client_port=9003
   ```

3. **Run tests with coverage**:
   ```bash
   composer coverage
   xdg-open storage/coverage/html/index.html
   ```

### Alternative: PCOV (Faster Coverage)

If you only need coverage (not step debugging):

```bash
# Install PCOV
sudo pecl install pcov

# Configure
echo "extension=pcov.so" | sudo tee /etc/php/8.2/mods-available/pcov.ini
echo "pcov.enabled=1" | sudo tee -a /etc/php/8.2/mods-available/pcov.ini
sudo phpenmod pcov

# Disable Xdebug for better performance
sudo phpdismod xdebug
```

PCOV is **significantly faster** than Xdebug for coverage.

## 🚀 Quick Start

### Running Tests

```bash
# Without coverage (fast)
composer test

# With coverage (requires Xdebug/PCOV)
composer test:coverage

# Generate full HTML report
composer coverage
```

### Viewing Coverage

```bash
# Generate and view HTML report
composer coverage

# Linux
xdg-open storage/coverage/html/index.html

# macOS
open storage/coverage/html/index.html

# Windows (WSL)
explorer.exe storage/coverage/html/index.html
```

### Debugging in VS Code

1. Install "PHP Debug" extension
2. Set breakpoints in code
3. Press F5 or Run > Start Debugging
4. Select configuration:
   - "Listen for Xdebug" - General debugging
   - "Debug Pest Tests" - Debug test files
   - "Debug Artisan Command" - Debug CLI
5. Trigger your code

## 📊 Coverage Reports

### Report Locations

```
storage/coverage/
├── html/
│   └── index.html          # 👈 Open this for interactive report
├── clover.xml             # For CI/CD tools
├── coverage.txt           # Text summary
└── xml/                   # Detailed XML metrics
```

### Coverage Metrics

```
< 50%   = Low (red)      - Needs immediate attention
50-79%  = Medium (yellow) - Needs improvement  
80-100% = High (green)   - Good coverage
```

### Recommended Targets

```
Services/Business Logic:  90-100%
Controllers:              80-90%
Models:                   70-80%
Overall Project:          80%+
```

## 🐛 Troubleshooting

### Tests Run But No Coverage

```bash
# Check if Xdebug/PCOV is installed
php -m | grep -E 'xdebug|pcov'

# If not found, install one of them
sudo pecl install xdebug
# or
sudo pecl install pcov
```

### Xdebug Not Working

```bash
# Verify Xdebug is loaded
php -v | grep Xdebug

# Check configuration
php -i | grep xdebug

# Enable if disabled
sudo phpenmod xdebug
```

### Coverage Generation is Slow

1. **Use PCOV** instead of Xdebug (10-20x faster)
2. **Disable Xdebug** when not debugging
3. **Run coverage** only for changed files:
   ```bash
   php artisan test tests/Unit/ChangedTest.php --coverage
   ```

### Storage Permission Issues

```bash
chmod -R 775 storage/
chown -R $USER:www-data storage/
```

## 📚 Documentation Reference

| Document | Purpose |
|----------|---------|
| `XDEBUG_SETUP.md` | Complete Xdebug installation and configuration |
| `CODE_COVERAGE.md` | Detailed coverage documentation |
| `TESTING_QUICK_REFERENCE.md` | Quick command cheatsheet |
| `README.md` | Project overview with testing section |

## 🔧 CI/CD Integration

### GitHub Actions Example

```yaml
- name: Setup PHP with PCOV
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'
    coverage: pcov

- name: Run Tests with Coverage
  run: composer test:coverage-clover

- name: Upload to Codecov
  uses: codecov/codecov-action@v3
  with:
    files: ./storage/coverage/clover.xml
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
```

## ✨ Features

### Composer Commands

| Command | Description |
|---------|-------------|
| `composer test` | Run all tests (no coverage) |
| `composer test:coverage` | Terminal coverage report |
| `composer test:coverage-html` | HTML report only |
| `composer test:coverage-clover` | Clover XML only |
| `composer coverage` | Full HTML + success message |

### VS Code Debugging

| Configuration | Use Case |
|--------------|----------|
| Listen for Xdebug | General debugging |
| Debug Pest Tests | Debug test files |
| Debug Artisan Command | Debug CLI commands |
| Debug Current Test File | Debug active test |

### Keyboard Shortcuts (VS Code)

| Key | Action |
|-----|--------|
| `F5` | Start/Continue debugging |
| `F10` | Step Over |
| `F11` | Step Into |
| `Shift+F11` | Step Out |
| `Shift+F5` | Stop debugging |

## 🎯 Best Practices

1. ✅ **Run tests** before committing
2. ✅ **Check coverage** for new features (aim for 80%+)
3. ✅ **Review HTML reports** to identify gaps
4. ✅ **Keep Xdebug disabled** during normal development
5. ✅ **Use PCOV** in CI/CD for speed
6. ✅ **Set coverage gates** in pull requests
7. ✅ **Focus on business logic** first

## 🔗 Additional Resources

- [Xdebug Documentation](https://xdebug.org/docs/)
- [Pest PHP](https://pestphp.com/)
- [PHPUnit](https://phpunit.de/)
- [Laravel Testing](https://laravel.com/docs/testing)
- [VS Code PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)

## 📝 Notes

- The setup works **without** Xdebug/PCOV installed - tests will run normally
- Coverage generation **requires** either Xdebug or PCOV
- PCOV is recommended for CI/CD (faster)
- Xdebug is recommended for local development (debugging + coverage)
- All configuration is version-controlled except coverage reports
- Storage directories are auto-created if needed

---

**Status**: ✅ Fully Configured and Ready to Use

**Need Help?** See detailed docs in `XDEBUG_SETUP.md` or `CODE_COVERAGE.md`


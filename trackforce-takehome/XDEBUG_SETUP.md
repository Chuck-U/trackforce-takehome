# Xdebug Setup Guide

This guide explains how to set up Xdebug for debugging and code coverage in the TrackForce project.

## Table of Contents

- [What is Xdebug?](#what-is-xdebug)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)

## What is Xdebug?

Xdebug is a PHP extension that provides debugging and profiling capabilities. It's particularly useful for:
- Step debugging with breakpoints
- Code coverage analysis
- Performance profiling
- Stack traces

## Installation

### Ubuntu/Debian

```bash
# Install Xdebug via pecl
sudo pecl install xdebug

# Or via apt (if available)
sudo apt-get install php8.2-xdebug
```

### macOS (with Homebrew)

```bash
pecl install xdebug
```

### Verify Installation

```bash
php -v
```

You should see Xdebug mentioned in the output:
```
PHP 8.2.x (cli) (built: ...)
    with Xdebug v3.x.x, Copyright (c) 2002-2024, by Derick Rethans
```

## Configuration

### Xdebug Configuration File

Create or edit the Xdebug configuration file:

**Location**: `/etc/php/8.2/mods-available/xdebug.ini` (Ubuntu/Debian) or similar

### For Code Coverage Only

If you only need code coverage (fastest option):

```ini
zend_extension=xdebug.so
xdebug.mode=coverage
```

### For Step Debugging

If you need step debugging with your IDE:

```ini
zend_extension=xdebug.so
xdebug.mode=debug,coverage
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003
xdebug.idekey=VSCODE
```

### For Development (All Features)

```ini
zend_extension=xdebug.so
xdebug.mode=develop,debug,coverage,profile
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003
xdebug.output_dir=/tmp
xdebug.idekey=VSCODE
```

### Configuration Options Explained

- `xdebug.mode`: Comma-separated list of modes
  - `debug`: Enable step debugging
  - `coverage`: Enable code coverage
  - `develop`: Enable development helpers (better error messages)
  - `profile`: Enable profiling
  - `trace`: Enable function traces

- `xdebug.start_with_request`: When to start Xdebug
  - `yes`: Always start
  - `trigger`: Only when triggered (recommended)
  - `no`: Never start automatically

- `xdebug.client_host`: The IP address of your IDE
- `xdebug.client_port`: The port your IDE listens on (default: 9003)
- `xdebug.idekey`: Identifier for your IDE

### Enable/Disable Xdebug

```bash
# Enable
sudo phpenmod xdebug

# Disable (improves performance when not needed)
sudo phpdismod xdebug

# Restart PHP-FPM (if using it)
sudo systemctl restart php8.2-fpm
```

## Usage

### Code Coverage

The project is already configured for code coverage. Simply run:

```bash
# Terminal coverage report
composer test:coverage

# HTML coverage report
composer coverage

# Open the HTML report
xdg-open storage/coverage/html/index.html
```

### Step Debugging with VS Code

#### 1. Install PHP Debug Extension

Install the "PHP Debug" extension by Xdebug.

#### 2. Configure Launch Configuration

Create `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/path/to/project": "${workspaceFolder}"
            }
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 9003
        }
    ]
}
```

#### 3. Start Debugging

1. Set breakpoints in your code (click left of line numbers)
2. Start the debugger (F5 or Run > Start Debugging)
3. Trigger your code (run tests, make API request, etc.)
4. Debugger will pause at breakpoints

### Debugging Tests

To debug Pest tests:

```bash
# Set XDEBUG_MODE environment variable
XDEBUG_MODE=debug php artisan test
```

Or add to your test command:

```json
{
    "name": "Debug Pest Tests",
    "type": "php",
    "request": "launch",
    "program": "${workspaceFolder}/vendor/bin/pest",
    "cwd": "${workspaceFolder}",
    "args": [],
    "port": 9003
}
```

## Troubleshooting

### Xdebug Not Working

1. **Check if Xdebug is loaded**:
   ```bash
   php -m | grep xdebug
   ```

2. **Check Xdebug configuration**:
   ```bash
   php -i | grep xdebug
   ```

3. **Verify the port is not in use**:
   ```bash
   sudo netstat -tulpn | grep 9003
   ```

4. **Check PHP CLI vs PHP-FPM configuration**:
   - CLI: `/etc/php/8.2/cli/conf.d/`
   - FPM: `/etc/php/8.2/fpm/conf.d/`
   
   Ensure Xdebug is configured for the version you're using.

### Coverage Not Generated

1. **Ensure Xdebug is in coverage mode**:
   ```bash
   XDEBUG_MODE=coverage php artisan test --coverage
   ```

2. **Check phpunit.xml configuration**: The `XDEBUG_MODE` environment variable should be set in the `<php>` section.

3. **Verify storage directory is writable**:
   ```bash
   chmod -R 775 storage/
   ```

### Performance Issues

Xdebug can slow down PHP significantly. When not debugging:

1. **Disable Xdebug**:
   ```bash
   sudo phpdismod xdebug
   ```

2. **Use coverage mode only** when running tests:
   ```ini
   xdebug.mode=coverage
   ```

3. **Consider PCOV** as a faster alternative for code coverage only:
   ```bash
   sudo pecl install pcov
   ```

### Alternative: PCOV for Coverage

If you only need code coverage (not step debugging), PCOV is faster:

```bash
# Install PCOV
sudo pecl install pcov

# Disable Xdebug
sudo phpdismod xdebug

# Enable PCOV
echo "extension=pcov.so" | sudo tee /etc/php/8.2/mods-available/pcov.ini
sudo phpenmod pcov

# Configure PCOV
echo "pcov.enabled=1" | sudo tee -a /etc/php/8.2/mods-available/pcov.ini
echo "pcov.directory=." | sudo tee -a /etc/php/8.2/mods-available/pcov.ini
```

## Additional Resources

- [Xdebug Documentation](https://xdebug.org/docs/)
- [VS Code PHP Debug Extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Pest PHP Documentation](https://pestphp.com/)

## Environment Variables

You can control Xdebug behavior with environment variables:

```bash
# Enable specific modes
XDEBUG_MODE=debug php artisan serve
XDEBUG_MODE=coverage php artisan test

# Disable Xdebug completely
XDEBUG_MODE=off php artisan serve

# Multiple modes
XDEBUG_MODE=debug,coverage php artisan test
```

## Best Practices

1. **Keep Xdebug disabled** during normal development for better performance
2. **Enable it only when needed** for debugging or coverage
3. **Use PCOV** for CI/CD pipelines (faster coverage generation)
4. **Set coverage thresholds** in your CI to maintain code quality
5. **Review coverage reports** regularly to identify untested code


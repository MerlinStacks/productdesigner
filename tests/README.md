# Testing CustomKings Product Personalizer

This directory contains the test suite for the CustomKings Product Personalizer plugin.

## Test Structure

- `Unit/` - Unit tests that test individual components in isolation
- `Integration/` - Integration tests that test how components work together
- `TestCase/` - Base test case classes
- `bin/` - Test environment setup scripts

## Running Tests

### Prerequisites

1. PHP 7.4 or higher
2. Composer
3. MySQL
4. WordPress test suite

### Installation

1. Install dependencies:
   ```bash
   composer install
   ```

2. Set up the WordPress test environment:
   ```bash
   # On Windows
   .\tests\bin\install-wp-tests.ps1
   
   # On Unix-like systems
   bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

### Running Tests

Run all tests:
```bash
composer test
```

Run unit tests only:
```bash
composer test:unit
```

Run integration tests only:
```bash
composer test:integration
```

Generate code coverage report:
```bash
composer test:coverage
```

## Writing Tests

### Unit Tests

Unit tests should extend `CustomKings\Tests\TestCase\UnitTestCase` and test individual classes in isolation.

Example:
```php
class MyClassTest extends UnitTestCase
{
    public function test_method_does_something()
    {
        // Test code here
    }
}
```

### Integration Tests

Integration tests should extend `CustomKings\Tests\TestCase\IntegrationTestCase` and test how components work together.

Example:
```php
class MyIntegrationTest extends IntegrationTestCase
{
    public function test_components_work_together()
    {
        // Test code here
    }
}
```

## Continuous Integration

GitHub Actions is configured to run tests on push and pull requests. The workflow file is located at `.github/workflows/phpunit.yml`.

## Code Coverage

Code coverage reports are generated in the `coverage` directory when running `composer test:coverage`. These reports are also uploaded to Codecov for pull requests.

## Debugging Tests

To debug tests, you can use Xdebug with your IDE. Set breakpoints and run:

```bash
php -dxdebug.mode=debug -dxdebug.start_with_request=yes vendor/bin/phpunit --filter=TestClassName::testMethodName
```

## Best Practices

1. Write tests for new features and bug fixes
2. Keep tests focused and test one thing per test method
3. Use descriptive test method names
4. Mock external dependencies in unit tests
5. Test edge cases and error conditions
6. Keep tests fast and independent
7. Update tests when changing functionality

## Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [WordPress PHPUnit Test Suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [Brain Monkey Documentation](https://brain-wp.github.io/BrainMonkey/)

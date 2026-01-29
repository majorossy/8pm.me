# Test Quick Start Guide

Quick reference for running the comprehensive test suite added in Fixes #42, #43, #44.

## Quick Commands

```bash
# Run all tests
vendor/bin/phpunit -c src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml

# Run with verbose output
vendor/bin/phpunit -c src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml --testdox

# Run only unit tests
vendor/bin/phpunit -c src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml --testsuite unit

# Run only integration tests
vendor/bin/phpunit -c src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml --testsuite integration
```

## New Tests (Fixes #42, #43, #44)

### Error Handling Tests (Fix #42)

```bash
# All error handling tests in ArchiveApiClientTest
vendor/bin/phpunit --filter ArchiveApiClientTest

# Specific new error tests
vendor/bin/phpunit --filter "testConnectionTimeoutWithMultipleRetries"
vendor/bin/phpunit --filter "testRateLimitRetryWithBackoff"
vendor/bin/phpunit --filter "testIntermittentNetworkFailureRecovery"
vendor/bin/phpunit --filter "testCircuitBreakerOpensAfterFailureThreshold"
vendor/bin/phpunit --filter "testCircuitBreakerBlocksRequestsWhenOpen"
```

### Idempotency Tests (Fix #43)

```bash
# All idempotency tests
vendor/bin/phpunit --filter IdempotencyTest

# Individual idempotency tests
vendor/bin/phpunit --filter "testImportIsIdempotent"
vendor/bin/phpunit --filter "testPartialReimportNoDuplicates"
vendor/bin/phpunit --filter "testInterruptedImportCanResume"
vendor/bin/phpunit --filter "testReimportUpdatesNotDuplicates"
vendor/bin/phpunit --filter "testConcurrentImportsHandleSkuCollisions"
```

### API Contract Tests (Fix #44)

```bash
# Mock API tests (safe for CI/CD)
vendor/bin/phpunit --filter MockArchiveOrgApiContractTest

# Real API tests (requires network, run manually)
SKIP_EXTERNAL_TESTS=0 vendor/bin/phpunit --filter RealArchiveOrgApiContractTest

# Both real and mock
vendor/bin/phpunit --filter ArchiveOrgApiContractTest
```

## Test Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit -c src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml --coverage-html coverage-report

# View report
open coverage-report/index.html  # macOS
xdg-open coverage-report/index.html  # Linux
```

## Test Counts

- **Total Tests:** 132 (105 unit + 27 integration)
- **New Tests (this session):** 20
  - Error handling: 5 (ArchiveApiClientTest)
  - Idempotency: 6 (IdempotencyTest)
  - API contract: 9 (ArchiveOrgApiContractTest - real + mock)

## Test Files

### Unit Tests
- `ArchiveApiClientTest.php` - 14 tests (5 new)
- `ShowImporterTest.php` - 3 tests (already complete)
- `TrackImporterTest.php` - 2 tests (already complete)
- `IdempotencyTest.php` - 6 tests (NEW)
- `StringNormalizerTest.php` - 20 tests
- `ArtistConfigValidatorTest.php` - 25 tests
- `TrackMatcherServiceTest.php` - 17 tests
- `LockServiceTest.php` - 18 tests

### Integration Tests
- `DownloadPopulateTest.php` - 7 tests
- `ArchiveOrgApiContractTest.php` - 9 × 2 tests (NEW)
- `ConcurrencyTest.php` - 11 tests

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
          php-version: '8.1'

      - name: Install Dependencies
        run: composer install

      - name: Run Unit Tests
        run: vendor/bin/phpunit --testsuite unit --exclude-group external

      - name: Run Mock API Contract Tests
        run: vendor/bin/phpunit --filter MockArchiveOrgApiContractTest

      - name: Generate Coverage
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml

  nightly:
    runs-on: ubuntu-latest
    if: github.event_name == 'schedule'
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install Dependencies
        run: composer install

      - name: Run Real API Contract Tests
        env:
          SKIP_EXTERNAL_TESTS: 0
        run: vendor/bin/phpunit --filter RealArchiveOrgApiContractTest
```

## Troubleshooting

### Tests Failing?

1. **Clear cache:**
   ```bash
   rm -rf src/app/code/ArchiveDotOrg/Core/Test/.phpunit.result.cache
   ```

2. **Verify PHPUnit version:**
   ```bash
   vendor/bin/phpunit --version  # Should be 9.x
   ```

3. **Check syntax:**
   ```bash
   php -l src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/IdempotencyTest.php
   ```

### Skip External Tests

Set environment variable to skip tests requiring network:

```bash
export SKIP_EXTERNAL_TESTS=1
vendor/bin/phpunit
```

## Documentation

- **Full Summary:** `docs/TEST_COMPLETION_SUMMARY.md`
- **Module Documentation:** `src/app/code/ArchiveDotOrg/Core/CLAUDE.md`
- **Test Configuration:** `src/app/code/ArchiveDotOrg/Core/Test/phpunit.xml`

# Phase 6: Testing & Documentation

**Timeline:** Week 9-10
**Status:** ⏸️ Blocked by Phase 5
**Prerequisites:** Phase 0-5 complete

---

## Overview

Comprehensive testing and documentation before production rollout.

**Test categories:**
- Unit tests for core services
- Integration tests for full flows
- Performance benchmarks
- Documentation for developers and admins

**Completion Criteria:**
- [ ] 100% test coverage on critical services
- [ ] All benchmarks meet performance targets
- [ ] Developer guide complete
- [ ] Admin user guide complete

---

## Recent Updates (Phase -1.C - Test Plan Alignment)

**Changes made (2026-01-28):**
1. ✅ Updated all references from "Soundex" to "Metaphone" (correct algorithm)
2. ✅ Added status tags (✅ existing, 🔧 planned) to each test task
3. ✅ Verified all test file paths match Magento's structure
4. ✅ Added comprehensive error handling test cases to all tasks
5. ✅ Referenced actual class locations (with line numbers where applicable)
6. ✅ Distinguished between existing code and planned interfaces
7. ✅ Added missing test cases (index building, confidence scores, stale locks, etc.)

---

## Test Target Classification

Tests are organized into three categories based on implementation status:

### ✅ Testing Existing Code (Immediate)
- **LockService** - `Model/LockService.php` (exists)
- **TrackPopulatorService.normalizeTitle()** - `Model/TrackPopulatorService.php:277` (exists)

### 🔧 Testing Planned Implementations (Phase 0-3)
- **TrackMatcherService** - Interface exists (`Api/TrackMatcherServiceInterface.php`), implementation planned for Phase 0
- **ArtistConfigValidator** - Interface exists (`Api/ArtistConfigValidatorInterface.php`), implementation planned for Phase 2
- **StringNormalizer** - Planned for Phase 0 (functionality currently inline in TrackPopulatorService)

### 📝 Notes
- All test file paths follow Magento's standard test directory structure: `src/app/code/{Vendor}/{Module}/Test/Unit/`
- Tests can be written before implementations (TDD approach recommended)
- Each test file should mock dependencies using PHPUnit's mocking framework
- Integration tests should use Magento's test database and fixtures

---

## 🟩 P3 - Unit Tests

### Task 6.1: Test LockService
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/LockServiceTest.php`

**Status:** ✅ Tests existing implementation
**Target:** `ArchiveDotOrg\Core\Model\LockService` (exists at `Model/LockService.php`)
**Interface:** `ArchiveDotOrg\Core\Api\LockServiceInterface` (exists)

**Test cases:**
- [ ] Acquire lock succeeds when no lock exists
- [ ] Acquire lock fails when lock already held (throws LockException)
- [ ] Release lock succeeds
- [ ] Release lock is idempotent (no error if lock doesn't exist)
- [ ] Stale lock detection (lock from dead process)
- [ ] Lock directory creation (creates parent directories if missing)
- [ ] Lock timeout behavior

**Error handling test cases:**
- [ ] LockException thrown with correct message when lock exists
- [ ] LockException::alreadyLocked() factory method works
- [ ] LockException::timeout() factory method works
- [ ] Permission denied on lock directory handled gracefully
- [ ] Disk full scenario handled
- [ ] Process ID validation works correctly

**Target:** 100% coverage

---

### Task 6.2: Test TrackMatcherService
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/TrackMatcherServiceTest.php`

**Status:** 🔧 Tests planned implementation (interface exists, implementation planned for Phase 0)
**Target:** `ArchiveDotOrg\Core\Model\TrackMatcherService` (to be created)
**Interface:** `ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface` (exists)

**Test cases:**
- [ ] Exact match found (hash lookup)
- [ ] Alias match found (from YAML config)
- [ ] Metaphone phonetic match found
- [ ] No match returns null
- [ ] Case insensitive matching
- [ ] Unicode normalization applied (via StringNormalizer)
- [ ] Empty string handling
- [ ] Multiple metaphone candidates (picks best via limited fuzzy)
- [ ] Index building and clearing
- [ ] Match confidence scores correct for each type

**Error handling test cases:**
- [ ] Invalid artist key throws exception
- [ ] Null track name returns null gracefully
- [ ] Empty indexes handled correctly
- [ ] buildIndexes() called automatically on first match

---

### Task 6.3: Test ArtistConfigValidator
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/ArtistConfigValidatorTest.php`

**Status:** 🔧 Tests planned implementation (interface exists, implementation planned for Phase 2)
**Target:** `ArchiveDotOrg\Core\Model\ArtistConfigValidator` (to be created)
**Interface:** `ArchiveDotOrg\Core\Api\ArtistConfigValidatorInterface` (exists)

**Test cases:**
- [ ] Valid YAML config passes validation
- [ ] Missing required field fails (`artist.name`, `artist.collection_id`)
- [ ] Invalid URL key format fails (must be lowercase alphanumeric + hyphens)
- [ ] Duplicate track keys fail
- [ ] Duplicate track names in same album fail
- [ ] Invalid fuzzy_threshold fails (must be 0-100)
- [ ] Empty aliases array triggers warning
- [ ] Album context required for tracks (canonical_album must exist in albums array)
- [ ] Fuzzy matching disabled by default (fuzzy_enabled should be false)

**Error handling test cases:**
- [ ] Null config returns validation errors
- [ ] Empty config returns validation errors
- [ ] Invalid YAML structure handled gracefully
- [ ] Circular album references detected
- [ ] Non-existent canonical_album references fail

---

### Task 6.4: Test StringNormalizer
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/StringNormalizerTest.php`

**Status:** 🔧 Tests planned implementation (to be created in Phase 0)
**Target:** `ArchiveDotOrg\Core\Model\StringNormalizer` (to be created)
**Interface:** `ArchiveDotOrg\Core\Api\StringNormalizerInterface` (to be created in Phase -1)

**Alternative (Immediate):** Test existing `TrackPopulatorService.normalizeTitle()` method at `Model/TrackPopulatorService.php:277` until StringNormalizer is extracted.

**Test cases:**
- [ ] NFD decomposition + accent removal: "Tweezér" → "tweezer"
- [ ] Unicode dash conversion: "Free—form" → "free-form", "Free–Time" → "free-time"
- [ ] Whitespace normalization: "  The   Flu  " → "the flu"
- [ ] Lowercase conversion: "PHISH" → "phish"
- [ ] Combined transformations work correctly
- [ ] Empty string handling (returns empty string)
- [ ] Null handling (returns empty string or throws exception per interface contract)
- [ ] Numeric strings handled correctly
- [ ] Special characters handled: "Song > Jam" → "song > jam" or "song jam" (per implementation)

**Error handling test cases:**
- [ ] Very long strings (>1000 chars) handled without memory issues
- [ ] Unicode edge cases (emoji, non-Latin scripts)
- [ ] Malformed UTF-8 sequences handled gracefully

---

## 🟩 P3 - Integration Tests

### Task 6.5: Test Full Download → Populate Flow
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Integration/DownloadPopulateTest.php`

**Target:** End-to-end workflow from download to product creation

**Steps:**
1. Create test YAML config for test artist
2. Run `archive:setup {test-artist}` - verify categories created
3. Mock Archive.org API (or use test fixtures)
4. Run `archive:download --limit=5` - verify JSON files created
5. Run `archive:populate` - verify products created
6. Verify products created with correct attributes
7. Verify unmatched tracks logged to database/file

**Success criteria:**
- [ ] Create test with fixtures/mocks for Archive.org API
- [ ] Verify end-to-end flow works
- [ ] Test with matching and non-matching tracks
- [ ] Verify progress tracking works
- [ ] Verify cleanup on completion

**Error handling test cases:**
- [ ] Download fails mid-process - can resume from progress file
- [ ] Populate fails mid-process - can resume without re-downloading
- [ ] Invalid YAML config detected before download starts
- [ ] API timeout handled gracefully with retries
- [ ] Disk full scenario during download
- [ ] Category creation fails - entire process rolls back

---

### Task 6.6: Test Concurrent Download Protection
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Integration/ConcurrencyTest.php`

**Target:** LockService integration with CLI commands

**Steps:**
1. Start download process A in background for artist "test-artist-1"
2. Try to start download process B for same artist (should fail immediately)
3. Verify B fails with LockException containing correct message
4. Wait for A to complete successfully
5. Start download C for same artist - should succeed
6. Test different artists in parallel - should both succeed

**Success criteria:**
- [ ] Create test using process forking or parallel execution
- [ ] Verify lock behavior prevents concurrent access
- [ ] Verify lock is released on successful completion
- [ ] Verify lock is released on error/exception

**Error handling test cases:**
- [ ] Process A crashes - lock becomes stale and is detected by process B
- [ ] Lock file deleted mid-process - handled gracefully
- [ ] Lock acquired, process killed (SIGKILL) - next process detects stale lock via PID check
- [ ] Two processes attempt to acquire lock simultaneously (race condition)
- [ ] Lock directory permissions changed mid-process
- [ ] Network-mounted lock directory (NFS) - flock behavior validated

---

## 🟩 P3 - Performance Tests

### Task 6.7: Benchmark Matching Algorithms
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Performance/MatchingBenchmark.php`

**Target:** `ArchiveDotOrg\Core\Model\TrackMatcherService` (planned for Phase 0)

**Tests:**

| Method | Tracks | Target Time | Target Memory | Notes |
|--------|--------|-------------|---------------|-------|
| Exact match (hash) | 10,000 | <100ms | <10MB | O(1) lookup |
| Alias match | 10,000 | <100ms | <10MB | O(1) lookup with pre-built index |
| Metaphone match | 10,000 | <500ms | <50MB | O(1) with pre-built phonetic index |
| Limited fuzzy (top 5) | 10,000 | <2s | <100MB | Only on metaphone candidates |
| Full Levenshtein (❌ DON'T USE) | 10,000 | ~43 hours | 6.3GB | Prohibitively slow |

**Performance targets:**
- Index building time: <5 seconds for 10,000 tracks
- Memory usage should remain stable (no leaks)
- Concurrent matching operations should not degrade performance

- [ ] Create benchmark script
- [ ] Run with different data sizes (1k, 10k, 100k tracks)
- [ ] Document results
- [ ] Compare metaphone vs. soundex performance
- [ ] Measure index build time

**Usage:**
```bash
bin/magento archive:benchmark-matching --tracks=10000
bin/magento archive:benchmark-matching --tracks=1000 --algorithm=exact
bin/magento archive:benchmark-matching --tracks=50000 --iterations=10
```

---

### Task 6.8: Benchmark BulkProductImporter
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkImportCommand.php`

**Target:** Compare TrackImporter vs. BulkProductImporter performance

**Tests:**
- Import 1,000 products via TrackImporter (ORM) - baseline
- Import 1,000 products via BulkProductImporter (direct SQL) - optimized
- Measure time, memory, and database queries for each

**Expected results:**
- BulkProductImporter ~10x faster
- BulkProductImporter uses ~50% less memory
- BulkProductImporter uses fewer database queries

**Metrics to track:**
- [ ] Total execution time
- [ ] Peak memory usage
- [ ] Number of DB queries
- [ ] Products created per second
- [ ] Indexer reindex time after import

**Error handling test cases:**
- [ ] Duplicate SKU handling in bulk import
- [ ] Invalid attribute values in bulk data
- [ ] Database connection lost mid-import
- [ ] Transaction rollback on error

**Usage:**
```bash
bin/magento archive:benchmark-import --products=1000
bin/magento archive:benchmark-import --products=5000 --method=bulk
bin/magento archive:benchmark-import --products=500 --method=orm
```

---

### Task 6.9: Benchmark Dashboard Queries
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Performance/DashboardBenchmark.php`

**Target:** Admin dashboard query performance (Phase 5a/5b)

**Tests:**

| Query | Without Indexes | With Indexes | Target |
|-------|----------------|--------------|--------|
| Artist grid (35 artists) | Baseline | <100ms | <100ms |
| Import history (1000 runs) | Baseline | <150ms | <100ms |
| Unmatched tracks (500 tracks) | Baseline | <200ms | <100ms |
| Imports per day chart (30 days) | Baseline | <100ms | <50ms |
| Daily metrics aggregation | Baseline | <500ms | <200ms |

**Verification steps:**
- [ ] Run EXPLAIN on each query
- [ ] Verify indexes used (check for "Using index" in Extra column)
- [ ] Document query plans before/after optimization
- [ ] Test with production-scale data (186k products, 12k shows)
- [ ] Measure query time with cold vs. warm cache

**Index verification checklist:**
- [ ] `idx_created_at` on `catalog_product_entity` used
- [ ] `idx_artist_status_started` on `archivedotorg_import_run` used
- [ ] `idx_correlation_id` on `archivedotorg_import_run` used
- [ ] Foreign key indexes used for joins

**Error handling test cases:**
- [ ] Query timeout handling (if query takes >30s)
- [ ] Missing index graceful degradation
- [ ] Empty result set handling
- [ ] Very large result set pagination

---

## 🟩 P3 - Documentation

### Task 6.10: Update Main Plan Document
**Modify:** `IMPORT_REARCHITECTURE_PLAN.md`

- [ ] Incorporate all fixes from agent findings
- [ ] Add implementation notes
- [ ] Update status

---

### Task 6.11: Create Developer Guide
**Create:** `docs/DEVELOPER_GUIDE.md`

**Sections:**
1. Architecture overview
   - System components diagram
   - Data flow (API → JSON → Products)
   - File structure

2. Adding a new artist
   - Create YAML config
   - Run `archive:setup`
   - Run `archive:download`
   - Run `archive:populate`
   - Resolve unmatched tracks

3. Extending matching logic
   - TrackMatcherService interface
   - Adding new match strategies
   - Custom normalizers

4. Troubleshooting
   - Common errors and solutions
   - Log locations
   - Debug commands

- [ ] Write each section
- [ ] Include code examples
- [ ] Review for accuracy

---

### Task 6.12: Create Admin User Guide
**Create:** `docs/ADMIN_GUIDE.md`

**Sections:**
1. Dashboard overview
   - Stats cards explained
   - Charts interpretation

2. Managing artists
   - Viewing artist status
   - Triggering imports
   - Monitoring progress

3. Resolving unmatched tracks
   - Finding unmatched tracks
   - Adding aliases to YAML
   - Re-running populate

4. Performance tuning
   - Batch size recommendations
   - Cron scheduling
   - Cache management

- [ ] Write each section
- [ ] Include screenshots
- [ ] Review with test user

---

### Task 6.13: Document API Endpoints (if created)
**Create:** `docs/API.md`

**Document:**
- REST endpoints for triggering imports programmatically
- Request/response formats
- Authentication requirements
- Rate limiting

---

## Running Tests

```bash
# Unit tests
bin/magento dev:tests:run unit --filter=ArchiveDotOrg

# Or with phpunit directly
cd src
../vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  --filter="ArchiveDotOrg" --testdox

# Integration tests (requires test DB)
bin/magento dev:tests:run integration --filter=ArchiveDotOrg

# Performance benchmarks
bin/magento archive:benchmark-matching --tracks=10000
bin/magento archive:benchmark-import --products=1000
```

---

## Verification Checklist

Before moving to Phase 7:

```bash
# 1. All unit tests pass
bin/magento dev:tests:run unit --filter=ArchiveDotOrg
# Should report: 0 failures, 40+ assertions

# 2. Integration tests pass
bin/magento dev:tests:run integration --filter=ArchiveDotOrg
# Should report: 0 failures

# 3. Performance targets met
# - Dashboard <100ms (artist grid, history, unmatched tracks queries)
# - Metaphone matching <500ms for 10k tracks
# - Exact match <100ms for 10k tracks
# - Bulk import 10x faster than ORM
# - Index building <5s for 10k tracks

# 4. Documentation complete
ls docs/
# Should show: DEVELOPER_GUIDE.md, ADMIN_GUIDE.md, (API.md optional)

# 5. Test coverage meets targets
# - LockService: 100%
# - TrackMatcherService: 100%
# - ArtistConfigValidator: 95%+
# - StringNormalizer: 100%
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 7: Rollout](./08-PHASE-7-ROLLOUT.md)

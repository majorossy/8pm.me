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

## 🟩 P3 - Unit Tests

### Task 6.1: Test LockService
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/LockServiceTest.php`

**Test cases:**
- [ ] Acquire lock succeeds when no lock exists
- [ ] Acquire lock fails when lock already held
- [ ] Release lock succeeds
- [ ] Release lock is idempotent
- [ ] Stale lock detection (lock from dead process)
- [ ] Lock directory creation

**Target:** 100% coverage

---

### Task 6.2: Test TrackMatcherService
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/TrackMatcherServiceTest.php`

**Test cases:**
- [ ] Exact match found
- [ ] Alias match found
- [ ] Soundex match found
- [ ] No match returns null
- [ ] Case insensitive matching
- [ ] Unicode normalization applied
- [ ] Empty string handling
- [ ] Multiple Soundex candidates (picks best)

---

### Task 6.3: Test ArtistConfigValidator
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/ArtistConfigValidatorTest.php`

**Test cases:**
- [ ] Valid YAML passes
- [ ] Missing required field fails
- [ ] Invalid URL key format fails
- [ ] Duplicate track names in same album fails
- [ ] Invalid fuzzy_threshold fails
- [ ] Empty aliases array triggers warning
- [ ] Album context required for tracks

---

### Task 6.4: Test StringNormalizer
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/StringNormalizerTest.php`

**Test cases:**
- [ ] Accent removal: "Tweezér" → "tweezer"
- [ ] Unicode dash conversion: "Free—form" → "free-form"
- [ ] Whitespace normalization: "  The   Flu  " → "the flu"
- [ ] Lowercase conversion
- [ ] Combined transformations
- [ ] Empty string handling
- [ ] Numeric strings unchanged

---

## 🟩 P3 - Integration Tests

### Task 6.5: Test Full Download → Populate Flow
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Integration/DownloadPopulateTest.php`

**Steps:**
1. Create test YAML config
2. Run `archive:setup {test-artist}`
3. Mock Archive.org API (or use test fixtures)
4. Run `archive:download --limit=5`
5. Run `archive:populate`
6. Verify products created with correct attributes
7. Verify unmatched tracks logged

- [ ] Create test
- [ ] Use fixtures or mocks for Archive.org
- [ ] Verify end-to-end flow

---

### Task 6.6: Test Concurrent Download Protection
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Integration/ConcurrencyTest.php`

**Steps:**
1. Start download process A in background
2. Try to start download process B (same artist)
3. Verify B fails with lock error
4. Wait for A to complete
5. Start download C - should succeed

- [ ] Create test
- [ ] Use process forking or parallel execution
- [ ] Verify lock behavior

---

## 🟩 P3 - Performance Tests

### Task 6.7: Benchmark Matching Algorithms
**Create:** `src/app/code/ArchiveDotOrg/Core/Test/Performance/MatchingBenchmark.php`

**Tests:**

| Method | Tracks | Target Time | Target Memory |
|--------|--------|-------------|---------------|
| Exact match | 10,000 | <100ms | <10MB |
| Soundex match | 10,000 | <500ms | <50MB |
| Levenshtein (DON'T USE) | 10,000 | ~43 hours | 6.3GB |

- [ ] Create benchmark script
- [ ] Run with different data sizes
- [ ] Document results

**Usage:**
```bash
bin/magento archive:benchmark-matching --tracks=10000
```

---

### Task 6.8: Benchmark BulkProductImporter
**Create:** Performance comparison script

**Tests:**
- Import 1,000 products via TrackImporter (ORM)
- Import 1,000 products via BulkProductImporter (direct SQL)

**Expected:** BulkProductImporter ~10x faster

- [ ] Create benchmark
- [ ] Document speedup factor

---

### Task 6.9: Benchmark Dashboard Queries
**Create:** Query performance tests

**Tests:**
- Artist grid query (no indexes) - baseline
- Artist grid query (with indexes) - target <100ms
- Imports per day chart query - target <50ms
- Unmatched tracks query - target <100ms

- [ ] Run EXPLAIN on each query
- [ ] Verify indexes used
- [ ] Document query plans

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
# Should report: 0 failures

# 2. Integration tests pass
bin/magento dev:tests:run integration --filter=ArchiveDotOrg
# Should report: 0 failures

# 3. Performance targets met
# - Dashboard <100ms
# - Soundex matching <500ms for 10k tracks
# - Bulk import 10x faster than ORM

# 4. Documentation complete
ls docs/
# Should show: DEVELOPER_GUIDE.md, ADMIN_GUIDE.md, (API.md optional)
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 7: Rollout](./08-PHASE-7-ROLLOUT.md)

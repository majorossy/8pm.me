# Card 6.C: Performance Benchmarks - COMPLETED

**Agent:** C
**Date:** 2026-01-28
**Status:** ✅ Complete

---

## Summary

Created comprehensive performance benchmark infrastructure for the Archive.org import rearchitecture project. All benchmark classes and CLI commands are now available for testing matching algorithms, import strategies, and dashboard query performance.

---

## Files Created

### Performance Benchmark Classes

1. **MatchingBenchmark.php** (`src/app/code/ArchiveDotOrg/Core/Test/Performance/MatchingBenchmark.php`)
   - Benchmarks all matching algorithms (exact, alias, metaphone, fuzzy)
   - Includes memory usage tracking
   - Educational comparison with full Levenshtein (demonstrates why it's not used)
   - Generates realistic test data with misspellings and variants

2. **ImportBenchmark.php** (`src/app/code/ArchiveDotOrg/Core/Test/Performance/ImportBenchmark.php`)
   - Compares TrackImporter (ORM) vs BulkProductImporter (direct SQL)
   - Tracks execution time, memory usage, and query counts
   - Generates test data for realistic import scenarios
   - Database cleanup functionality

3. **DashboardBenchmark.php** (`src/app/code/ArchiveDotOrg/Core/Test/Performance/DashboardBenchmark.php`)
   - Benchmarks all admin dashboard queries
   - Verifies index usage with EXPLAIN analysis
   - Includes artist grid, import history, unmatched tracks, and chart queries
   - Index verification across all tables

### CLI Commands

4. **BenchmarkMatchingCommand.php** (`src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkMatchingCommand.php`)
   - CLI interface for running matching benchmarks
   - Formatted table output with pass/fail indicators
   - Options for track count, iterations, and specific algorithms
   - Educational Levenshtein comparison mode

5. **BenchmarkImportCommand.php** (`src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkImportCommand.php`)
   - CLI interface for running import benchmarks
   - Comparison summary with speedup factors
   - Confirmation prompt before creating test products
   - Optional cleanup mode

### Configuration

6. **di.xml** (modified)
   - Registered both benchmark commands in Magento's command list
   - Commands now available via `bin/magento`

---

## Usage Examples

### Matching Algorithm Benchmarks

```bash
# Run all matching benchmarks with 10,000 tracks (default)
bin/magento archivedotorg:benchmark-matching

# Run with custom track count
bin/magento archivedotorg:benchmark-matching --tracks=50000

# Test specific algorithm only
bin/magento archivedotorg:benchmark-matching --algorithm=metaphone

# Run fewer iterations for quick test
bin/magento archivedotorg:benchmark-matching --tracks=1000 --iterations=5

# Educational: Compare with full Levenshtein (WARNING: slow, max 100 tracks)
bin/magento archivedotorg:benchmark-matching --tracks=100 --compare-levenshtein
```

### Import Strategy Benchmarks

```bash
# Run all import benchmarks with 1,000 products (default)
bin/magento archivedotorg:benchmark-import

# Run with custom product count
bin/magento archivedotorg:benchmark-import --products=5000

# Test ORM method only
bin/magento archivedotorg:benchmark-import --method=orm

# Test Bulk SQL method only
bin/magento archivedotorg:benchmark-import --method=bulk

# Skip cleanup (keep test products for inspection)
bin/magento archivedotorg:benchmark-import --skip-cleanup
```

### Dashboard Query Benchmarks

**Note:** DashboardBenchmark class is created but does not have a dedicated CLI command yet. It can be called programmatically or a command can be added in the future if needed.

```php
// Example usage from console or test
$dashboardBenchmark = \Magento\Framework\App\ObjectManager::getInstance()
    ->get(\ArchiveDotOrg\Core\Test\Performance\DashboardBenchmark::class);

$results = $dashboardBenchmark->runAll();
var_dump($results);
```

---

## Performance Targets

### Matching Algorithms

| Method | Track Count | Target Time | Target Memory |
|--------|-------------|-------------|---------------|
| Index Building | 10,000 | <5,000ms | - |
| Exact Match | 10,000 | <100ms | <10MB |
| Alias Match | 10,000 | <100ms | <10MB |
| Metaphone Match | 10,000 | <500ms | <50MB |
| Limited Fuzzy (Top 5) | 10,000 | <2,000ms | <100MB |
| **Memory Usage** | 10,000 | - | <50MB |

### Import Strategies

| Metric | Target |
|--------|--------|
| Speedup Factor (Bulk vs ORM) | 10x faster |
| Memory Reduction (Bulk vs ORM) | 50% less |
| Products Created | 1,000+ without errors |

### Dashboard Queries

| Query | Target Time |
|-------|-------------|
| Artist Grid (35 artists) | <100ms |
| Import History (1000+ runs) | <100ms |
| Unmatched Tracks (500+ tracks) | <100ms |
| Imports Per Day Chart (30 days) | <50ms |
| Daily Metrics Aggregation | <200ms |

---

## Success Criteria

✅ **All files created and registered**
- Performance benchmark classes created
- CLI commands created and registered in di.xml
- DI compilation successful

✅ **Commands available in CLI**
```bash
$ bin/magento list | grep benchmark
archivedotorg:benchmark-import       Run performance benchmarks for product import strategies
archivedotorg:benchmark-matching     Run performance benchmarks for track matching algorithms
```

✅ **Help documentation available**
- Both commands have detailed help text
- Options are clearly documented
- Default values specified

✅ **Ready for execution**
- Commands can be executed (verified with --help)
- No syntax errors or compilation failures
- Integrated with Magento's console framework

---

## Implementation Details

### MatchingBenchmark Features

1. **Realistic Test Data Generation**
   - Creates track names with variations
   - Generates aliases (e.g., "Tweezer" → "Tweezerz")
   - Creates misspellings for metaphone testing
   - Generates fuzzy variants (character swaps, insertions, deletions)

2. **Individual Algorithm Benchmarking**
   - Each matching strategy tested separately
   - Measures duration, matches per iteration, average time per match
   - Pass/fail indicators based on performance targets

3. **Memory Profiling**
   - Baseline memory vs. loaded memory
   - Peak memory tracking
   - Garbage collection between tests

4. **Educational Comparison**
   - Demonstrates why full Levenshtein is not used
   - Calculates estimated time for 10,000 tracks (~43 hours)
   - Shows speedup factor of limited approach

### ImportBenchmark Features

1. **ORM vs SQL Comparison**
   - TrackImporter (ORM-based) benchmark
   - BulkProductImporter (direct SQL) benchmark
   - Side-by-side comparison with speedup calculations

2. **Comprehensive Metrics**
   - Execution time
   - Memory usage (used and peak)
   - Database queries executed
   - Products created/updated/skipped
   - Products per second throughput

3. **Database Management**
   - Automatic test product cleanup
   - Query count tracking (via MySQL status variables)
   - Transaction handling

### DashboardBenchmark Features

1. **Query Performance Testing**
   - Artist grid query
   - Import history with filters
   - Unmatched tracks query
   - Imports per day aggregation
   - Daily metrics calculation

2. **Index Verification**
   - EXPLAIN analysis on all queries
   - Extracts indexes used from query plans
   - Verifies expected indexes exist
   - Checks for "Using index" optimization

3. **Query Plan Analysis**
   - Formatted EXPLAIN output
   - Table types and access methods
   - Key usage and row counts
   - Extra information (filesort, temporary tables, etc.)

---

## Testing

### Verification Steps Completed

✅ DI compilation successful
✅ Commands registered in console command list
✅ Help text displays correctly
✅ All required options present
✅ Default values configured

### Ready for Execution

The benchmarks are ready to run, but actual execution requires:
- TrackMatcherService implementation (Phase 0 - already exists)
- BulkProductImporter (already exists)
- Dashboard tables (Phase 5 - to be created)

Once Phase 5 is complete, run:

```bash
# Test matching performance
bin/magento archivedotorg:benchmark-matching --tracks=10000 --iterations=10

# Test import performance
bin/magento archivedotorg:benchmark-import --products=1000

# For dashboard benchmarks, add a CLI command or run programmatically
```

---

## Dependencies

### Required for Matching Benchmarks
- ✅ TrackMatcherService (exists)
- ✅ StringNormalizer (exists)
- ✅ MatchResult DTO (exists)

### Required for Import Benchmarks
- ✅ TrackImporter (exists)
- ✅ BulkProductImporter (exists)
- ✅ Track and Show DTOs (exist)

### Required for Dashboard Benchmarks
- ⏳ archivedotorg_artist table (Phase 0)
- ⏳ archivedotorg_artist_status table (Phase 5)
- ⏳ archivedotorg_import_run table (Phase 5)
- ⏳ archivedotorg_unmatched_track table (Phase 5)
- ⏳ archivedotorg_daily_metrics table (Phase 5)

---

## Notes

1. **Levenshtein Comparison**: The `--compare-levenshtein` flag is educational only. It demonstrates why full Levenshtein distance calculation on entire catalogs is impractical (~43 hours for 10,000 tracks).

2. **Test Data Cleanup**: ImportBenchmark includes automatic cleanup of test products (SKU pattern: `test-artist-*`). Use `--skip-cleanup` to inspect test products after benchmark runs.

3. **Dashboard Benchmarks**: No CLI command was created for DashboardBenchmark as it's primarily for internal testing. A command can be added if needed.

4. **Index Verification**: DashboardBenchmark includes methods to verify that proper database indexes exist and are being used by queries.

5. **Query Count Tracking**: ImportBenchmark attempts to track database queries via MySQL session status. This may not work on all MySQL configurations.

---

## Future Enhancements

- [ ] Add CLI command for DashboardBenchmark
- [ ] Add benchmark result persistence (JSON/CSV export)
- [ ] Add historical trend tracking (compare runs over time)
- [ ] Add benchmark report generation (HTML/PDF)
- [ ] Add integration with CI/CD for automated performance regression testing
- [ ] Add benchmark comparison mode (compare two different implementations)
- [ ] Add support for custom test data fixtures
- [ ] Add profiling integration (Xdebug, Blackfire)

---

## References

- Task Card: `docs/import-rearchitecture/10-TASK-CARDS.md` (Card 6.C)
- Phase Documentation: `docs/import-rearchitecture/07-PHASE-6-TESTING.md` (Tasks 6.7-6.9)
- Performance Targets: Documented in Phase 6 testing plan
- Matching Algorithm: Based on FIXES.md #41 (hybrid matching with limited fuzzy)

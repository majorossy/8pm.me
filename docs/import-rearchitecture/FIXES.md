# Import Rearchitecture - Critical Fixes & Updates

**Date:** 2026-01-28
**Status:** Updated after comprehensive 8-agent validation
**Source:** Database, Architecture, Performance, Concurrency, Migration, Testing, YAML, and Codebase agents

---

## Overview

This document contains **48 critical fixes** identified during the architecture review. These must be addressed during implementation.

**Priority levels:**
- 🔴 **Critical (16)** - Must fix before starting Phase 0
- 🟧 **High (19)** - Must fix before MVP
- 🟨 **Medium (13)** - Fix before production

**⚠️ IMPORTANT DISCOVERY:** The codebase already has **162 files (7,600+ lines)** implemented including LockService (378 lines), AlbumArtworkService (443 lines), 11 CLI commands, queue infrastructure, and GraphQL resolvers. Many items in the plan are already complete.

---

## Decision Status Legend

| Symbol | Meaning |
|--------|---------|
| ✅ DECIDED | Architecture decision locked - do not revisit |
| 🔓 OPEN | Needs decision before implementation |
| ⏳ DEFERRED | Will decide during implementation based on findings |

---

## Key Architecture Decisions (Summary)

| # | Decision | Status | Choice |
|---|----------|--------|--------|
| D1 | YAML track structure | ✅ DECIDED | Multi-album with stable keys (Fix #47) |
| D2 | Matching algorithm | ✅ DECIDED | Hybrid: exact→metaphone→limited fuzzy (Fix #41) |
| D3 | Unmappable files | ✅ DECIDED | Quarantine to `/unmapped/` (Fix #13) |
| D4 | Locking mechanism | ✅ DECIDED | Redis primary + flock fallback (Fix #10, #39) |
| D5 | Dashboard MVP scope | ✅ DECIDED | Split Phase 5a (grids) / 5b (charts) |
| D6 | Test approach | ✅ DECIDED | TDD against interfaces (Fix #8, #11) |
| D7 | ImportShowsCommand | ✅ DECIDED | Deprecate with warning, remove in v2.0 (Fix #32) |
| D8 | --resume vs --incremental | ✅ DECIDED | Unify to single --incremental flag (Fix #33) |

---

## Fix Implementation Schedule

| Fix | Phase | Task |
|-----|-------|------|
| #2 Performance docs | ✅ Done | - |
| #6 SKU format | **-1** | Task -1.1 |
| #8 Service interfaces | **-1** | Task -1.2 |
| #11 Align tests | **-1** | Task -1.5 |
| #12 Feature flags | **-1** | Task -1.4 |
| #14 Exception hierarchy | **-1** | Task -1.3 |
| #1, #7 Artist table, FKs | 0 | Task 0.1 |
| #4 Atomic writes | 0 | Task 0.6 |
| #5 DB transactions | 0 | Task 0.4 |
| #10 Lock race condition | 0 | Task 0.5 |
| #3 CLI uses locks | 3 | Task 3.4, 3.9 |
| #9 Memory cleanup | 3 | Task 3.6 |
| #41 Hybrid matching | 3 | Task 3.6 |
| #15 Cron uses locks | 3 | (with new commands) |
| #16 Admin checks locks | 5a | (with dashboard) |

---

## 🔴 CRITICAL FIXES (Original 6 + New 10)

### Fix 1: Add Artist Normalization Table

**Problem:** `artist_name` VARCHAR(255) duplicated across 4 tables, violating 3NF

**Solution:** Create `archivedotorg_artist` table as single source of truth

```sql
CREATE TABLE archivedotorg_artist (
    artist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL UNIQUE,
    collection_id VARCHAR(255) NOT NULL UNIQUE,
    url_key VARCHAR(255) NOT NULL,
    yaml_file_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collection (collection_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Migration:** Add `artist_id` foreign keys to all related tables

**SQL:** `migrations/001_create_artist_table.sql`

---

### Fix 2: Correct Performance Claims (CRITICAL - Documentation Wrong)

**Problem:** Plan claims "43 hours" and "6.3GB memory" for fuzzy matching, but actual analysis shows:
- **43 hours claim → Actually 2-10 minutes** (860x overstated)
- **6.3GB memory → Actually 50-100MB** (63x overstated)
- **Soundex "100,000x faster" → Actually ~1,000x faster** (100x overstated)

**Real Bottleneck:** API rate limiting (750ms delay × 10,000 shows = **3.5 hours minimum**)

**Solution:** Update all documentation to reflect accurate numbers:
```yaml
# OLD (WRONG)
matching:
  fuzzy_runtime: "43 hours"  # INCORRECT
  memory: "6.3GB"            # INCORRECT

# NEW (CORRECT)
matching:
  fuzzy_runtime: "2-10 minutes"  # Verified
  memory: "50-100MB"             # Verified
  soundex_speedup: "~1000x"      # Not 100,000x

# Add this warning:
performance:
  real_bottleneck: "API rate limiting (3.5 hours for 10k shows)"
```

**Source:** Performance validation agent

---

### Fix 3: Add File Locking for Concurrent Downloads

**Problem:** No protection against two simultaneous downloads corrupting data

**Solution:** LockService already exists (378 lines) but **CLI commands don't use it**

```php
// In DownloadMetadataCommand::execute()
try {
    $lockToken = $this->lockService->acquire('download', $artistName);
} catch (LockException $e) {
    $output->writeln('<error>' . $e->getMessage() . '</error>');
    return Command::FAILURE;
}

try {
    // Download logic
} finally {
    $this->lockService->release($lockToken);
}
```

**Files to modify:**
- `Console/Command/DownloadMetadataCommand.php` - Add lock acquisition
- `Console/Command/PopulateTracksCommand.php` - Add lock acquisition
- `Cron/ImportShows.php` - Add lock acquisition per artist

**Status:** LockService exists at `src/app/code/ArchiveDotOrg/Core/Model/LockService.php` (378 lines, fully functional)

---

### Fix 4: Add Atomic Progress File Writes

**Problem:** `ProgressTracker.php:302-307` uses direct `file_put_contents()` - crash during write corrupts file

**Location:** `/src/app/code/ArchiveDotOrg/Core/Model/ProgressTracker.php`

**Solution:**
```php
// WRONG: Direct write (corrupts on crash)
file_put_contents($progressFile, json_encode($progress));

// RIGHT: Atomic write via temp file + fsync
$tmpFile = $progressFile . '.tmp.' . getmypid();
file_put_contents($tmpFile, json_encode($progress, JSON_PRETTY_PRINT));

// Sync to disk (important on VirtioFS)
$fp = fopen($tmpFile, 'r');
if ($fp) {
    fsync($fp);
    fclose($fp);
}

// Atomic rename (POSIX guarantee)
if (!rename($tmpFile, $progressFile)) {
    @unlink($tmpFile);
    throw new \RuntimeException("Failed to rename temp file");
}
```

**Also fix in:** `Model/Queue/JobStatusManager.php:83`

---

### Fix 5: Add Database Transactions in BulkProductImporter

**Problem:** Multiple INSERT statements without transaction wrapper - crash leaves orphaned products

**Location:** `Model/BulkProductImporter.php:354-395`

**Solution:**
```php
private function createProduct(...): void
{
    $connection = $this->resourceConnection->getConnection();

    $connection->beginTransaction();
    try {
        $connection->insert('catalog_product_entity', [...]);
        $entityId = (int) $connection->lastInsertId();

        $this->setProductAttributes($entityId, ...);
        $connection->insert('catalog_product_website', [...]);
        $connection->insert('cataloginventory_stock_item', [...]);

        $connection->commit();
    } catch (\Exception $e) {
        $connection->rollBack();
        throw $e;
    }
}
```

---

### Fix 6: Document SKU Generation Format

**Problem:** No documented format = risk of collisions

**Solution:** Add docblock to TrackImporter:

```php
/**
 * Generate product SKU
 *
 * Format: {artist_code}-{show_identifier}-{track_num}
 * Example: phish-phish2023-07-14-01
 *
 * Uniqueness:
 * - artist_code: Lowercase collection ID
 * - show_identifier: Full Archive.org identifier
 * - track_num: 2-digit zero-padded
 */
```

---

### Fix 7: Add Missing FK Cascade Actions

**Problem:** All foreign keys lack ON DELETE/ON UPDATE behavior

**Solution:**
```sql
-- Audit tables: Preserve history
ALTER TABLE archivedotorg_import_run
ADD CONSTRAINT fk_import_run_artist
FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- Operational tables: Cascade delete
ALTER TABLE archivedotorg_artist_status
ADD CONSTRAINT fk_artist_status_artist
FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE archivedotorg_daily_metrics
ADD CONSTRAINT fk_daily_metrics_artist
FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id)
ON DELETE CASCADE ON UPDATE CASCADE;
```

---

### Fix 8: Create Missing Service Interfaces

**Problem:** Services lack interfaces - can't use Magento's DI preference system

**Solution:** Create interfaces for all 8 planned services:

```php
// Api/TrackMatcherServiceInterface.php
// Api/ArtistConfigValidatorInterface.php
// Api/ArtistConfigLoaderInterface.php
// Api/StringNormalizerInterface.php
// Api/Data/MatchResultInterface.php
```

Register in `etc/di.xml`:
```xml
<preference for="ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface"
            type="ArchiveDotOrg\Core\Model\TrackMatcherService"/>
```

---

### Fix 9: Add Memory Cleanup in Batch Processing

**Problem:** TrackMatcherService indexes accumulate across artists - OOM kills

**Solution:**
```php
foreach ($artists as $artist) {
    try {
        $this->matcher->buildIndexes($artist);
        $this->processArtist($artist);
    } finally {
        // ALWAYS clear, even on exception
        $this->matcher->clearIndexes($artist);
        gc_collect_cycles();
    }
}
```

Add to `TrackMatcherService`:
```php
public function clearIndexes(?string $artistUrlKey = null): void
{
    if ($artistUrlKey === null) {
        $this->exactIndex = [];
        $this->aliasIndex = [];
        $this->soundexIndex = [];
        $this->indexedArtists = [];
    } else {
        unset(
            $this->exactIndex[$artistUrlKey],
            $this->aliasIndex[$artistUrlKey],
            $this->soundexIndex[$artistUrlKey],
            $this->indexedArtists[$artistUrlKey]
        );
    }
}
```

---

### Fix 10: Fix File Lock Race Condition ✅ DECIDED

**Decision:** Use Redis as primary locking mechanism, flock as fallback for same-host scenarios.

**Problem:** Check-then-write pattern allows two processes to acquire same lock. Additionally, flock() may not work across Docker/VirtioFS boundary.

**Current (Race Condition):**
```php
if (file_exists($lockFile)) { /* check */ }
file_put_contents($lockFile, $data); /* write */
```

**Solution (Primary - Redis):**
```php
// RedisLockService.php - preferred for Docker environments
$key = "lock:{$type}:{$resource}";
$acquired = $this->redis->set($key, $metadata, ['NX', 'EX' => $ttl]);
```

**Solution (Fallback - flock):** Use `flock()` when Redis unavailable:
```php
$handle = fopen($lockFile, 'c+');
if (!flock($handle, LOCK_EX | LOCK_NB)) {
    fclose($handle);
    throw LockException::alreadyLocked(...);
}
// Keep handle open - lock released when closed
```

**Location:** `Model/LockService.php` (already has flock, verify it's used correctly)

**Test Required:** Phase 0 Task 0.5 includes Docker boundary test to validate approach.

---

### Fix 11: Align Tests with Actual Codebase

**Problem:** 3 of 4 proposed unit tests target non-existent classes:
- `TrackMatcherService` doesn't exist (logic in `TrackPopulatorService.normalizeTitle()`)
- `ArtistConfigValidator` doesn't exist (handled by `Config.getArtistMappings()`)
- `StringNormalizer` doesn't exist (inline in `TrackPopulatorService`)

**Solution:**
1. Either create these classes as planned, OR
2. Redirect tests to actual implementations:
   - `TrackPopulatorServiceTest` (not TrackMatcherServiceTest)
   - Test `Config` class validation (not ArtistConfigValidator)
   - Add `normalizeTitle()` tests to TrackPopulatorServiceTest

---

### Fix 12: Add Feature Flags for Gradual Rollout

**Problem:** No mechanism for gradual rollout - all-or-nothing migration

**Solution:** Add config flags:
```xml
<!-- config.xml -->
<default>
    <archivedotorg>
        <migration>
            <use_organized_folders>0</use_organized_folders>
            <use_yaml_config>0</use_yaml_config>
            <dashboard_enabled>0</dashboard_enabled>
        </migration>
    </archivedotorg>
</default>
```

```php
// Config.php
public function useOrganizedFolders(): bool
{
    return $this->scopeConfig->isSetFlag(
        'archivedotorg/migration/use_organized_folders'
    );
}
```

---

### Fix 13: Create Pre-Migration File Audit Command ✅ DECIDED

**Decision:** Quarantine unmappable files to `/unmapped/` directory. Manual mapping deferred to post-MVP.

**Problem:** ~47 files have inconsistent naming that can't be mapped to artists

**Examples:**
```
04Track042_201602.json              # No artist prefix
2015-10-14-athens-ga.json           # Date-only format
billydbd2023-08-25.json             # Ambiguous abbreviation
```

**Solution:**
```bash
bin/magento archive:migrate:audit-files --report-unmappable

# Output:
# Unmappable files: 47
# - 04Track042_201602.json (no pattern match)
# - 2015-10-14-athens-ga.json (date-only format)
#
# Action: Moving to var/archivedotorg/metadata/unmapped/
# Manual mapping file created: var/archivedotorg/unmapped_manifest.json
```

**Quarantine manifest format:**
```json
{
  "quarantined_at": "2026-01-28T10:00:00Z",
  "files": [
    {
      "filename": "04Track042_201602.json",
      "reason": "no_artist_prefix",
      "suggested_artist": null
    },
    {
      "filename": "billydbd2023-08-25.json",
      "reason": "ambiguous_abbreviation",
      "suggested_artist": "BillyStrings"
    }
  ]
}
```

---

### Fix 14: Add Exception Hierarchy

**Problem:** Generic exceptions make error handling inconsistent

**Solution:**
```php
// Exception/ArchiveDotOrgException.php (base)
class ArchiveDotOrgException extends LocalizedException {}

// Exception/LockException.php
class LockException extends ArchiveDotOrgException
{
    public static function alreadyLocked(string $type, string $artist): self
    {
        return new self(__('Cannot acquire %1 lock for %2', $type, $artist));
    }
}

// Exception/ConfigurationException.php
// Exception/ImportException.php
```

---

### Fix 15: Cron Jobs Don't Use Locks

**Problem:** Cron job iterates artists without checking for concurrent CLI imports

**Location:** `Cron/ImportShows.php`

**Solution:**
```php
foreach ($mappings as $mapping) {
    try {
        $lockToken = $this->lockService->acquire('import', $artistName, timeout: 0);
    } catch (LockException $e) {
        $this->logger->info('Skipping - already processing', ['artist' => $artistName]);
        continue;
    }

    try {
        $result = $this->showImporter->importByCollection(...);
    } finally {
        $this->lockService->release($lockToken);
    }
}
```

---

### Fix 16: Admin Dashboard Doesn't Check Locks

**Problem:** "Start Import" button doesn't check if CLI import is running

**Location:** `Controller/Adminhtml/Dashboard/StartImport.php`

**Solution:**
```php
if ($this->lockService->isLocked('import', $artistName)) {
    $lockInfo = $this->lockService->getLockInfo('import', $artistName);
    return $result->setData([
        'success' => false,
        'error' => sprintf(
            'Import already in progress for %s (started by PID %d)',
            $artistName,
            $lockInfo['pid'] ?? 0
        )
    ]);
}
```

---

## 🟧 HIGH PRIORITY FIXES (Original 6 + New 13)

### Fix 17: Fix YAML Structure (Add Album Context)

**Problem:** Flat structure loses album context needed for matching

**Solution:**
```yaml
tracks:
  - name: "Phyllis"
    album: "Outta Here"        # NEW: Explicit album context
    url_key: "phyllis"
    aliases: ["phillis", "phylis"]
```

---

### Fix 18: Add Dashboard Indexes

**Problem:** Dashboard queries will take 10-30 seconds without indexes

**Solution:**
```sql
-- Product filtering
CREATE INDEX idx_created_at ON catalog_product_entity (created_at);

-- Import history
CREATE INDEX idx_artist_status_started ON archivedotorg_import_run
  (artist_id, status, started_at DESC);

-- Unmatched tracks
CREATE INDEX idx_artist_status_confidence ON archivedotorg_unmatched_track
  (artist_id, status, match_confidence DESC);

-- Additional missing indexes
CREATE INDEX idx_correlation_id ON archivedotorg_import_run (correlation_id);
CREATE INDEX idx_artist_date ON archivedotorg_daily_metrics (artist_id, date DESC);
CREATE INDEX idx_url_key ON archivedotorg_artist (url_key);
```

**SQL:** `migrations/002_add_dashboard_indexes.sql`

---

### Fix 19: Extract Large JSON from EAV

**Problem:** `show_reviews_json` and `show_workable_servers` are 5KB+ each, slow EAV joins

**Solution:** Move to separate table `archivedotorg_show_metadata`

**Keep in EAV:**
- `track_file_size`, `track_md5`, `track_acoustid`, `track_bitrate`
- `show_files_count`, `show_total_size`, `show_uploader`, etc.

**Move to separate table:**
- `show_reviews_json`
- `show_workable_servers`

**SQL:** `migrations/004_create_show_metadata_table.sql`

---

### Fix 20: Add Unicode Normalization

**Problem:** No normalization = "Tweezér" won't match "Tweezer"

**Solution:** New `StringNormalizer.php`

```php
public function normalize(string $str): string
{
    // 1. NFD decomposition
    $str = \Normalizer::normalize($str, \Normalizer::NFD);

    // 2. Remove accent marks
    $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str);

    // 3. Convert Unicode dashes to ASCII
    $str = str_replace(['—', '–', '→'], ['-', '-', '>'], $str);

    // 4. Normalize whitespace
    $str = preg_replace('/\s+/', ' ', trim($str));

    // 5. Lowercase
    return mb_strtolower($str, 'UTF-8');
}
```

---

### Fix 21: Add Ambiguous Match Logging

**Problem:** If track exists in 2+ albums, which gets matched?

**Solution:**
```php
if (count($potentialMatches) > 1) {
    $this->logAmbiguousMatch($trackTitle, $albumName, $potentialMatches);
    return null;  // Require admin resolution
}
```

**Admin Dashboard:** Shows ambiguous matches in "Unmatched Tracks" grid for manual resolution

---

### Fix 22: Add Cache Cleanup Strategy

**Problem:** 10k+ files × 42 artists = 4-20GB cache with no retention

**Solution:**
```bash
bin/magento archive:cleanup --older-than=90 --dry-run
bin/magento archive:cleanup --keep-latest=1000 --artist=Phish
```

---

### Fix 23: Add Signal Handlers (SIGTERM/SIGINT)

**Problem:** No `pcntl_signal()` handlers - Docker stop doesn't clean up locks/progress

**Solution:**
```php
// In CLI command
declare(ticks=1);

protected function setupSignalHandlers(): void
{
    if (!function_exists('pcntl_signal')) return;

    $handler = function (int $signal) {
        $this->shouldStop = true;
        $this->output->writeln("<comment>Stopping gracefully...</comment>");
    };

    pcntl_signal(SIGTERM, $handler);
    pcntl_signal(SIGINT, $handler);
}

// In import loop
foreach ($shows as $show) {
    if (!$this->shouldContinue()) {
        $this->saveProgress();
        break;
    }
    // Process show...
}
```

---

### Fix 24: Fix PID Check Across Docker Boundary

**Problem:** `posix_kill($pid, 0)` fails across container boundary - PID 12345 in container ≠ PID 12345 on host

**Location:** `Model/LockService.php:301-307`

**Solution:**
```php
$pid = (int)$info['pid'];
$hostname = $info['hostname'] ?? '';
$currentHostname = gethostname();

// Only check PID if on same host
if ($hostname === $currentHostname) {
    if (posix_kill($pid, 0)) {
        continue; // Process still alive
    }
} else {
    // Different host - only clean if very old (8+ hours)
    if ($mtime > $cutoff && $maxAgeHours < 8) {
        continue; // Skip - might be from another container
    }
}
```

---

### Fix 25: Downtime Underestimated

**Problem:** Plan estimates "<5 min" for database migrations, actual is 10-25 minutes with 186k products

**Solution:**
```bash
# Use pt-online-schema-change for zero-downtime index creation
pt-online-schema-change \
  --alter "ADD INDEX idx_created_at (created_at)" \
  D=magento,t=catalog_product_entity \
  --execute
```

**Revised estimates:**
- 001 (artist table): 30 sec
- 002 (indexes): 5-15 min
- 003 (JSON columns): 2-5 min per column
- 004-008 (new tables): 2 min
- **Total: 10-25 minutes**

---

### Fix 26: Progress File Breaks After Migration

**Problem:** `download_progress.json` assumes flat structure - breaks after folder migration

**Solution:** Add version field and migration:
```php
private function migrateProgressFile(array $progress): array
{
    if (($progress['version'] ?? 1) < 2) {
        foreach ($progress as $collection => $data) {
            if (is_array($data) && !isset($data['cache_path'])) {
                $progress[$collection]['cache_path'] = $this->getArtistPath($collection);
            }
        }
        $progress['version'] = 2;
    }
    return $progress;
}
```

---

### Fix 27: Add Magento Filesystem Usage

**Problem:** Direct `file_put_contents()` instead of Magento's Filesystem

**Solution:**
```php
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

public function __construct(Filesystem $filesystem)
{
    $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
}

// Usage
$this->varDirectory->writeFile('archivedotorg/locks/phish.lock', $data);
```

**Files to update:**
- `Model/LockService.php`
- `Model/ProgressTracker.php`
- `Model/Queue/JobStatusManager.php`

---

### Fix 28: Add Circuit Breaker for Archive.org API

**Problem:** API failures can hammer the service repeatedly

**Solution:**
```php
class ResilientHttpClient
{
    private const MAX_RETRIES = 3;
    private const CIRCUIT_BREAKER_THRESHOLD = 5;

    public function request(string $method, string $uri): ResponseInterface
    {
        if ($this->isCircuitOpen()) {
            throw new \RuntimeException('Circuit breaker open');
        }

        $attempt = 0;
        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->request($method, $uri);
                $this->failureCount = 0; // Reset on success
                return $response;
            } catch (ConnectException $e) {
                $this->failureCount++;
                if (++$attempt >= self::MAX_RETRIES) throw $e;
                usleep(self::RETRY_DELAY_MS * (2 ** $attempt) * 1000); // Exponential backoff
            }
        }
    }
}
```

---

### Fix 29: YAML Schema Needs Stable Keys

**Problem:** Track references albums by name string - if album name changes, references break

**Solution:**
```yaml
albums:
  - key: "outta-here"  # Stable identifier
    name: "Outta Here"

tracks:
  - key: "phyllis"
    name: "Phyllis"
    albums: ["outta-here"]  # Reference by key, supports multiple
    canonical_album: "outta-here"
```

---

## 🟨 MEDIUM PRIORITY FIXES (Original 7 + New 6)

### Fix 30: Reorder Implementation Phases

**Problem:** YAML depends on folder structure, but folder migration was Phase 2

**Solution:** Reordered phases:
- Phase 0: Database foundation + critical fixes
- Phase 1: Folder migration
- Phase 2: YAML configuration
- Phase 3: Commands + matching
- Phase 4: Extended attributes (parallel)
- Phase 5: Admin dashboard
- Phase 6: Testing
- Phase 7: Rollout

---

### Fix 31: Update Timeline to 9-10 Weeks

**Problem:** Original 7-8 weeks too optimistic

**Solution:** Updated to 9-10 weeks with buffer for:
- Integration debugging
- Performance tuning
- QA and bug fixes
- Production deployment

---

### Fix 32: Deprecate (Don't Delete) ImportShowsCommand ✅ DECIDED

**Decision:** Show deprecation warning, keep functionality until v2.0

**Problem:** Removing one-command import breaks user workflows

**Solution:**
```
⚠️ DEPRECATED: archive:import-shows bypasses permanent JSON storage.
Downloaded metadata is not saved to disk for future use.

Recommended workflow:
  1. bin/magento archive:download <artist>
  2. bin/magento archive:populate <artist>

This command will be REMOVED in version 2.0.
Continue anyway? [y/N]
```

**Implementation:**
- Add `--yes` flag to skip confirmation
- Log deprecation warning to file
- Plan removal for v2.0 release

---

### Fix 33: Unify --resume and --incremental Flags ✅ DECIDED

**Decision:** Remove `--resume`, keep only `--incremental` with combined behavior

**Problem:** Two flags doing the same thing is confusing

**Solution:**
```bash
# Single flag for incremental mode
bin/magento archive:download phish --incremental

# Behavior (in order):
# 1. Check progress file (if exists and valid JSON)
# 2. If corrupted, log warning and scan filesystem
# 3. Skip identifiers found in either source
# 4. Continue from where left off
```

**Remove:** `--resume` flag (redundant)

**Migration:** If anyone uses `--resume`, show:
```
⚠️ The --resume flag is deprecated. Use --incremental instead.
(Continuing with incremental mode...)
```

---

### Fix 34: Update TEXT → JSON Column Type

**Problem:** TEXT columns don't validate JSON

**Solution:**
```sql
options_json JSON NULL COMMENT 'Command options (validated)',
command_args JSON NULL COMMENT 'Arguments (validated)',
```

**Benefits:**
- 20-40% storage savings
- JSON validation at insert time
- Native JSONPath queries

**SQL:** `migrations/003_convert_json_columns.sql`

---

### Fix 35: Change TIMESTAMP(3) to TIMESTAMP(6)

**Problem:** Millisecond precision insufficient for sub-second operations

**Solution:**
```sql
started_at TIMESTAMP(6) NOT NULL,
completed_at TIMESTAMP(6) NULL,
```

---

### Fix 36: Fix INT → BIGINT Oversizing

**Problem:** Plan uses BIGINT unnecessarily - largest collection has ~15K recordings, INT handles 4.2B

**Solution:** Use INT UNSIGNED instead (saves 4 bytes/row):
```sql
-- WRONG
archive_total_recordings BIGINT UNSIGNED

-- RIGHT
archive_total_recordings INT UNSIGNED  -- Max 4.2B (sufficient)
imported_tracks INT UNSIGNED
```

---

### Fix 37: Define show_metadata Table Schema

**Problem:** Referenced but not defined

**Solution:**
```sql
CREATE TABLE archivedotorg_show_metadata (
    metadata_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    show_identifier VARCHAR(255) NOT NULL UNIQUE,
    artist_id INT UNSIGNED NOT NULL,
    reviews_json JSON,
    workable_servers JSON,
    FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id) ON DELETE CASCADE
);
```

---

### Fix 38: Add Stuck Job Cleanup

**Problem:** Jobs stuck in "running" status after crashes - never cleaned up

**Location:** `Model/Queue/JobStatusManager.php`

**Solution:**
```php
public function cleanupStuckJobs(int $stuckAfterHours = 6): int
{
    $cutoffTime = time() - ($stuckAfterHours * 3600);
    $cleaned = 0;

    foreach ($files as $file) {
        if ($data['status'] === 'running') {
            $startedAt = strtotime($data['started_at'] ?? '');

            if ($startedAt && $startedAt < $cutoffTime) {
                $data['status'] = 'failed';
                $data['error'] = 'Job stuck - marked as failed by cleanup';
                $data['completed_at'] = date('Y-m-d H:i:s');

                $directory->writeFile($file, $this->json->serialize($data));
                $cleaned++;
            }
        }
    }

    return $cleaned;
}
```

---

### Fix 39: VirtioFS File Locking Semantics ⏳ DEFERRED

**Status:** Test during Phase 0. May not be an issue in current setup.

**Problem:** Advisory locks via `flock()` may behave differently on macOS VirtioFS mounts

**Current Setup (2026-01):**
- Frontend: Runs on **host** (Mac) - not relevant for locking
- Magento/PHP: Runs in **Docker** container only
- All `bin/magento` commands exec into Docker

**Risk Assessment:**
- **LOW risk** if all PHP runs inside same container (flock works within container)
- **MEDIUM risk** if cron runs in separate container
- **HIGH risk** if PHP runs both on host AND in container (VirtioFS boundary)

**Test anyway during Phase 0:**
```bash
# Test from host (only if you ever run PHP on host)
php -r "
  \$fp = fopen('var/archivedotorg/locks/test.lock', 'w');
  flock(\$fp, LOCK_EX | LOCK_NB);
  echo 'Lock acquired on host';
  sleep(30);
"

# Simultaneously from container
docker exec -it 8pm-phpfpm-1 php -r "
  \$fp = fopen('/var/www/html/var/archivedotorg/locks/test.lock', 'w');
  if (flock(\$fp, LOCK_EX | LOCK_NB)) {
    echo 'WARNING: Lock not blocked across boundary';
  } else {
    echo 'OK: Lock correctly blocked';
  }
"
```

**Decision:** If all PHP runs in Docker containers only, use flock (simpler). If cross-boundary locking needed, use Redis.

---

### Fix 40: Extend Redis TTL for Long Imports

**Problem:** 1hr TTL insufficient - large imports take 10-50 hours

**Solution:**
```php
private const TTL_RUNNING = 86400;    // 24 hours for active jobs
private const TTL_COMPLETED = 604800; // 7 days for completed
private const TTL_FAILED = 259200;    // 3 days for failed
```

---

### Fix 41: Address Soundex False Positives ✅ DECIDED

**Decision:** Use hybrid matching algorithm. API rate limiting (3.5 hours for 10k shows) is the real bottleneck, not matching speed.

**Problem:** Soundex has 15-30% false positive rate for track names

**Examples:**
```
soundex("Scarlet Begonias") = "S643"
soundex("Scarlet Fire")     = "S643"  // COLLISION
soundex("Scarlet Fever")    = "S643"  // COLLISION
```

**Solution (FINAL):** Hybrid approach:
```php
public function match(string $unknown, array $index): ?MatchResult {
    // 1. Exact normalized match - O(1)
    $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $unknown));
    if (isset($index['exact'][$normalized])) {
        return new MatchResult($index['exact'][$normalized], 'exact', 100);
    }

    // 2. Alias match from YAML - O(n) where n = aliases
    if (isset($index['alias'][$normalized])) {
        return new MatchResult($index['alias'][$normalized], 'alias', 95);
    }

    // 3. Metaphone (better than soundex) - O(1)
    $metaphone = metaphone($unknown);
    if (isset($index['metaphone'][$metaphone])) {
        return new MatchResult($index['metaphone'][$metaphone], 'metaphone', 85);
    }

    // 4. Limited Levenshtein on top 5 candidates - O(5)
    $candidate = $this->fuzzyMatchTopCandidates($unknown, $index['all'], 5);
    if ($candidate && $candidate['score'] >= 80) {
        return new MatchResult($candidate['track'], 'fuzzy', $candidate['score']);
    }

    // 5. No match - log for admin resolution
    return null;
}
```

**Performance:** ~1-5ms per track (acceptable since API is the bottleneck)

---

### Fix 42: Add Error Handling Tests

**Problem:** No tests for API timeout, partial failure, corrupt data

**Solution:** Add test categories:
```php
public function testApiTimeoutHandling(): void
public function testPartialFailureRecovery(): void
public function testCorruptDataHandling(): void
public function testRateLimitingResponse(): void
public function testNetworkInterruptionRecovery(): void
public function testDiskSpaceExhaustion(): void
```

---

### Fix 43: Add Idempotency Tests

**Problem:** No tests ensuring re-runs produce same result

**Solution:**
```php
public function testImportIsIdempotent(): void
{
    $result1 = $this->importer->import('phish', ['limit' => 10]);
    $result2 = $this->importer->import('phish', ['limit' => 10]);

    $this->assertEquals($result1->getProductCount(), $result2->getProductCount());
    $this->assertNoDuplicateProducts();
}

public function testPartialReimportNoDuplicates(): void
public function testInterruptedImportCanResume(): void
```

---

### Fix 44: Add API Contract Tests

**Problem:** No validation that mocks match real API

**Solution:**
```php
interface ArchiveOrgApiInterface
{
    public function searchItems(string $query, int $limit): SearchResult;
    public function getItemMetadata(string $identifier): ItemMetadata;
}

// Contract test runs against BOTH real and mock
abstract class ArchiveOrgApiContractTest extends TestCase
{
    abstract protected function getClient(): ArchiveOrgApiInterface;

    public function testSearchReturnsExpectedStructure(): void
    {
        $result = $this->getClient()->searchItems('phish', 1);
        $this->assertInstanceOf(SearchResult::class, $result);
    }
}
```

Run nightly against real API to catch drift.

---

### Fix 45: YAML Live-Only Tracks Unhandled ✅ DECIDED

**Decision:** Use virtual "live-only" album. See Phase 2 Task 2.3 for full template.

**Problem:** Many tracks never appear on studio albums

**Solution (incorporated into YAML template):**
```yaml
albums:
  - key: "live-only"
    name: "Live Repertoire"
    url_key: "live-repertoire"
    type: "virtual"  # Not a real album, just a container

tracks:
  - key: "bowzers-jungle-romp"
    name: "Bowzer's Jungle Romp"
    url_key: "bowzers-jungle-romp"
    albums: ["live-only"]
    canonical_album: "live-only"
    type: "original"
```

---

### Fix 46: YAML Medleys/Segues Not Addressed ✅ DECIDED

**Decision:** Add optional `medleys` section to YAML. See Phase 2 Task 2.3 for full template.

**Problem:** Archive.org often lists "Phyllis > Sam Huff > Bowzer"

**Solution (incorporated into YAML template):**
```yaml
# Optional section - only needed for artists with common medleys
medleys:
  - pattern: "Phyllis > Sam Huff"     # Regex-like pattern to match
    tracks: ["phyllis", "sam-huff"]   # Component track keys
    separator: ">"                     # What separates track names
  - pattern: "Funk Medley"
    tracks: ["phyllis", "sam-huff", "the-flu"]
    type: "named_medley"              # Has a name, not just segue notation
```

**Matching behavior:**
- When "Phyllis > Sam Huff" is found in Archive.org metadata
- Split by separator and match each component
- Create product for each matched track
- Link them with `medley_group_id` attribute for display

---

### Fix 47: YAML No Multi-Album Support ✅ DECIDED

**Decision:** Use array for albums with canonical_album for display. Stable keys for all entities.

**Problem:** Single `album` field can't represent tracks on multiple albums

**Solution (FINAL - see Phase 2 Task 2.3 for full template):**
```yaml
tracks:
  - key: "phyllis"              # Stable identifier
    name: "Phyllis"
    url_key: "phyllis"
    albums: ["outta-here", "live-at-bonnaroo"]  # Array, not single
    canonical_album: "outta-here"  # For display/default
    aliases: ["phillis", "philis"]
    type: "original"  # or "cover"
```

**Key requirements:**
- `key` must be unique within artist, stable (never change)
- `albums` is always an array, even for single album
- `canonical_album` is required, must be in `albums` array
- `type` is optional, defaults to "original"

---

### Fix 48: Add Temp File Cleanup to Cron

**Problem:** Orphaned `.tmp.*` files from atomic writes never cleaned

**Solution:** Add to `Cron/CleanupProgress.php`:
```php
// Clean orphaned temp files older than 1 hour
$files = glob($varDir->getAbsolutePath('archivedotorg/**/*.tmp.*'));
$cutoff = time() - 3600;

foreach ($files as $tmpFile) {
    if (filemtime($tmpFile) < $cutoff) {
        @unlink($tmpFile);
    }
}
```

---

## Verification Checklist

Before starting implementation, verify:

### Critical Fixes (🔴)
- [ ] Artist normalization table added (Fix #1)
- [ ] Performance claims corrected (Fix #2) **IMPORTANT**
- [ ] CLI commands acquire locks (Fix #3)
- [ ] Atomic progress writes (Fix #4)
- [ ] DB transactions in BulkProductImporter (Fix #5)
- [ ] SKU format documented (Fix #6)
- [ ] FK cascade actions added (Fix #7)
- [ ] Service interfaces created (Fix #8)
- [ ] Memory cleanup added (Fix #9)
- [ ] File lock race condition fixed (Fix #10)
- [ ] Tests aligned with codebase (Fix #11) **IMPORTANT**
- [ ] Feature flags implemented (Fix #12)
- [ ] File audit command created (Fix #13)
- [ ] Exception hierarchy added (Fix #14)
- [ ] Cron uses locks (Fix #15)
- [ ] Admin checks locks (Fix #16)

### High Priority (🟧)
- [ ] YAML album context (Fix #17)
- [ ] Dashboard indexes (Fix #18)
- [ ] Large JSON extracted (Fix #19)
- [ ] Unicode normalization (Fix #20)
- [ ] Ambiguous match logging (Fix #21)
- [ ] Cache cleanup command (Fix #22)
- [ ] Signal handlers added (Fix #23)
- [ ] PID check fixed (Fix #24)
- [ ] Downtime accurate (Fix #25)
- [ ] Progress file migration (Fix #26)
- [ ] Magento Filesystem usage (Fix #27)
- [ ] Circuit breaker added (Fix #28)
- [ ] YAML stable keys (Fix #29)

### Medium Priority (🟨)
- [ ] Phases reordered (Fix #30)
- [ ] Timeline updated (Fix #31)
- [ ] ImportShowsCommand deprecated (Fix #32)
- [ ] Flags unified (Fix #33)
- [ ] TEXT → JSON (Fix #34)
- [ ] TIMESTAMP(6) (Fix #35)
- [ ] BIGINT fixed (Fix #36)
- [ ] show_metadata defined (Fix #37)
- [ ] Stuck job cleanup (Fix #38)
- [ ] VirtioFS tested (Fix #39)
- [ ] Redis TTL extended (Fix #40)
- [ ] Soundex fallback (Fix #41)
- [ ] Error tests added (Fix #42)
- [ ] Idempotency tests (Fix #43)
- [ ] Contract tests (Fix #44)
- [ ] YAML live-only (Fix #45)
- [ ] YAML medleys (Fix #46)
- [ ] YAML multi-album (Fix #47)
- [ ] Temp file cleanup (Fix #48)

---

## Related Files

- **Migrations:** `migrations/*.sql` (8 files)
- **Lock Service:** `src/app/code/ArchiveDotOrg/Core/Model/LockService.php` (378 lines, EXISTS)
- **Album Artwork:** `src/app/code/ArchiveDotOrg/Core/Model/AlbumArtworkService.php` (443 lines, EXISTS)
- **Phase docs:** This directory
- **Existing code:** 162 files, 7,600+ lines already implemented

---

## Agent Validation Sources

1. **Database Schema Agent** - FK cascades, indexes, table definitions
2. **PHP Architecture Agent** - Memory leaks, interfaces, anti-patterns
3. **Performance Agent** - Corrected claims (43hr → 2-10min), API bottleneck
4. **Concurrency Agent** - Atomic writes, transactions, locks, signals
5. **Migration Agent** - Feature flags, file audit, downtime estimates
6. **Codebase Agent** - Discovered 162 files already exist
7. **YAML Agent** - Schema improvements, stable keys, multi-album
8. **Testing Agent** - Test alignment, contract tests, error handling

---

**Next:** Start with [Phase 0: Critical Fixes](./01-PHASE-0-CRITICAL.md)

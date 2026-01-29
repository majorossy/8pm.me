# Phase 0: Critical Fixes

**Timeline:** Week 1-2
**Status:** ⏸️ Blocked by Phase -1
**Blockers:** Complete [Phase -1: Standalone Fixes](./00a-PHASE-MINUS-1-STANDALONE.md) first

---

## Architecture Decisions (Locked)

These decisions are FINAL. Do not revisit during implementation.

| Decision | Choice | Rationale |
|----------|--------|-----------|
| YAML track structure | Multi-album with stable keys | Handles live-only tracks, covers, medleys. See Fix #47 |
| Matching algorithm | Hybrid (exact→metaphone→limited fuzzy) | API rate limiting is real bottleneck (3.5hrs), not matching speed |
| Unmappable files | Quarantine to `/unmapped/` | Clean migration, defer manual mapping to post-MVP |
| Locking mechanism | Redis primary + flock fallback | VirtioFS may not honor flock across Docker boundary |
| Dashboard MVP scope | Split into Phase 5a (grids) / 5b (charts) | Ship value sooner, defer polish |
| Test approach | TDD against interfaces | Create interfaces first, then tests, then implementations |

---

## Configuration Defaults

| Setting | Default Value | CLI Override |
|---------|---------------|--------------|
| Download batch size | 100 shows | `--limit=N` |
| Populate batch size | 500 tracks | `--batch=N` |
| API rate limit delay | 750ms | `--delay=N` (ms) |
| Lock timeout | 0 (non-blocking) | `--wait=N` (seconds) |
| Progress save interval | Every 10 items | `--save-every=N` |

---

## Log Levels

| Level | When to Use | Example |
|-------|-------------|---------|
| DEBUG | Detailed trace info | "Processing track 45/523: Tweezer" |
| INFO | Normal operations | "Downloaded 100 shows for Phish" |
| WARNING | Recoverable issues | "Progress file corrupted, scanning filesystem" |
| ERROR | Operation failures | "Failed to acquire lock for Lettuce" |
| CRITICAL | System failures | "Database connection lost" |

---

## Standardized CLI Messages

```php
// Success messages
'<info>✓ Downloaded %d shows for %s</info>'
'<info>✓ Created %d products, skipped %d existing</info>'
'<info>✓ Lock acquired for %s</info>'

// Warning messages
'<comment>⚠ Skipping %s - already exists</comment>'
'<comment>⚠ Progress file corrupted, rebuilding from filesystem</comment>'
'<comment>⚠ DEPRECATED: Use archive:download instead</comment>'

// Error messages
'<error>✗ Cannot acquire lock - %s is already being processed (PID %d)</error>'
'<error>✗ API rate limited - waiting %d seconds</error>'
'<error>✗ Import failed: %s</error>'
```

---

## Overview

This phase addresses critical blockers that must be fixed before any other work. These are P0 items - production will fail without them.

**Completion Criteria:**
- [ ] All 4 P0 database migrations run successfully
- [ ] Dashboard loads in <100ms with test data
- [ ] Lock service prevents concurrent downloads
- [ ] Progress file survives `kill -9` (atomic writes)
- [ ] No memory leaks during bulk import
- [ ] Zero data loss during folder migration

---

## 🔴 P0 - Database Foundation

### Task 0.1: Create Artist Normalization Table
**File:** `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/CreateArtistTable.php`
**SQL:** `migrations/001_create_artist_table.sql`

- [ ] Create schema patch class
- [ ] Run migration in dev
- [ ] Add foreign keys to existing tables
- [ ] Test: Verify no duplicate artist names possible

**Why:** Without normalization, artist names can have inconsistent casing/spelling across tables, causing data integrity issues.

---

### Task 0.2: Add Dashboard Indexes
**File:** `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/AddDashboardIndexes.php`
**SQL:** `migrations/002_add_dashboard_indexes.sql`

- [ ] Create schema patch class
- [ ] Run migration in dev
- [ ] Verify with EXPLAIN queries
- [ ] Test: Query performance <100ms on 186k products

**Verification:**
```sql
EXPLAIN SELECT * FROM catalog_product_entity
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
-- Should show "Using index" in Extra column
```

---

### Task 0.3: Change TEXT to JSON Type
**File:** `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/ConvertJsonColumns.php`
**SQL:** `migrations/003_convert_json_columns.sql`

**Affected columns:**
- `import_run.options_json`
- `import_run.command_args`

- [ ] Create schema patch class
- [ ] Run migration in dev
- [ ] Test: Insert/query JSON data
- [ ] Verify: Invalid JSON rejected by DB

**Why:** JSON type provides validation + 20% storage savings over TEXT.

---

### Task 0.4: Extract Large JSON from EAV
**File:** `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/CreateShowMetadataTable.php`
**SQL:** `migrations/004_create_show_metadata_table.sql`

**Move these attributes to separate table:**
- `show_reviews_json`
- `show_workable_servers`

- [ ] Create new table `archivedotorg_show_metadata`
- [ ] Create schema patch class
- [ ] Migrate existing data
- [ ] Remove from EAV attributes
- [ ] Test: Join performance improvement

**Why:** Large JSON blobs in EAV hurt query performance for all products.

---

## 🔴 P0 - Concurrency & Safety

### Task 0.5: Implement File Locking Service
**Files (already created):**
- `src/app/code/ArchiveDotOrg/Core/Model/LockService.php`
- `src/app/code/ArchiveDotOrg/Core/Api/LockServiceInterface.php`
- `src/app/code/ArchiveDotOrg/Core/Model/LockException.php`

- [ ] Register in `etc/di.xml`:
```xml
<preference for="ArchiveDotOrg\Core\Api\LockServiceInterface"
            type="ArchiveDotOrg\Core\Model\LockService"/>
```
- [ ] Clear DI cache: `bin/magento setup:di:compile`
- [ ] Test: Two simultaneous downloads fail gracefully
- [ ] **CRITICAL:** Test flock across Docker boundary (see below)
- [ ] If flock fails across boundary, implement Redis fallback

**Test procedure (same host):**
```bash
bin/magento archive:download phish &  # Start in background
bin/magento archive:download phish    # Should fail with lock error
```

**Test procedure (Docker boundary - MUST PASS):**
```bash
# Terminal 1: Start lock from HOST
php -r "
  \$fp = fopen('var/archivedotorg/locks/test.lock', 'c+');
  if (flock(\$fp, LOCK_EX | LOCK_NB)) {
    echo 'Lock acquired on host, sleeping 30s...';
    sleep(30);
  }
"

# Terminal 2: Try lock from CONTAINER (should FAIL)
docker exec -it 8pm-phpfpm-1 php -r "
  \$fp = fopen('/var/www/html/var/archivedotorg/locks/test.lock', 'c+');
  if (flock(\$fp, LOCK_EX | LOCK_NB)) {
    echo 'BUG: Lock acquired - flock does NOT work across Docker!';
    exit(1);
  } else {
    echo 'PASS: Lock correctly blocked';
  }
"
```

**If Docker boundary test FAILS:** Implement Redis-based locking:
```php
// Model/RedisLockService.php
public function acquire(string $type, string $resource, int $ttl = 3600): string
{
    $key = "lock:{$type}:{$resource}";
    $token = bin2hex(random_bytes(16));

    // SET NX with TTL - atomic acquire
    $acquired = $this->redis->set($key, json_encode([
        'token' => $token,
        'pid' => getmypid(),
        'hostname' => gethostname(),
        'acquired_at' => date('c'),
    ]), ['NX', 'EX' => $ttl]);

    if (!$acquired) {
        throw LockException::alreadyLocked($type, $resource);
    }

    return $token;
}
```

---

### Task 0.6: Add Atomic Progress File Writes
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php`
**Lines:** ~150-170 (progress file writing)

- [ ] Change write pattern to: temp file → rename
- [ ] Example implementation:
```php
$tempFile = $progressFile . '.tmp.' . getmypid();
file_put_contents($tempFile, json_encode($progress));
rename($tempFile, $progressFile);  // Atomic on POSIX
```
- [ ] Test: Kill process during write, verify file integrity

**Why:** Direct writes can leave corrupted files if process crashes mid-write.

---

### Task 0.7: Add Progress File Validation
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php`

- [ ] Add JSON validation before trusting resume data
- [ ] Add fallback: Filesystem scan if progress corrupted
- [ ] Test: Corrupt progress file manually, verify fallback works

**Validation logic:**
```php
$progress = @json_decode(file_get_contents($progressFile), true);
if ($progress === null || !isset($progress['completed'])) {
    $this->logger->warning('Progress file corrupted, scanning filesystem');
    $progress = $this->scanFilesystemForProgress();
}
```

---

## 🔴 P0 - Data Integrity

### Task 0.8: Document SKU Generation Format
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php`

- [ ] Add docblock explaining format: `{artist_code}-{identifier}-{track_num}`
- [ ] Add unit test for SKU uniqueness
- [ ] Test: Import two artists with same date shows - SKUs must differ

**Why:** Undocumented SKU format leads to confusion and potential collisions.

---

### Task 0.9: Add Category Duplication Check
**Modify/Create:** `src/app/code/ArchiveDotOrg/Core/Model/CategoryService.php`

- [ ] Add `findByUrlKeyAndParent()` method
- [ ] Check before creating category
- [ ] Make `archive:setup` idempotent
- [ ] Test: Run setup command twice, verify no duplicate categories

---

### Task 0.10: Enforce Fuzzy Matching Disabled
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigValidator.php`

- [ ] Add validation error if `fuzzy_enabled: true` in YAML
- [ ] Add `--enable-fuzzy` CLI flag with prominent warning
- [ ] Test: YAML with fuzzy enabled fails validation by default

**Warning text:**
```
WARNING: Fuzzy matching is enabled. This will take ~43 hours for large artists
and use 6.3GB+ memory. Are you sure? Use --enable-fuzzy to confirm.
```

---

## 🔴 P0 - Performance

### Task 0.11: Add Soundex Phonetic Matching
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/TrackMatcherService.php`

- [ ] Implement matching algorithm:
  1. Exact match (hash lookup) - O(1)
  2. Alias match (from YAML) - O(n) where n = aliases
  3. Soundex phonetic match - O(1) lookup
  4. Log unmatched - never use fuzzy
- [ ] Benchmark: Compare to Levenshtein (should be 100,000x faster)
- [ ] Test: Match "Twezer" → "Tweezer" via Soundex

**Implementation hint:**
```php
// Pre-build Soundex index
$soundexIndex = [];
foreach ($knownTracks as $track) {
    $soundexIndex[soundex($track)] = $track;
}

// Lookup is O(1)
$soundex = soundex($unknownTrack);
$match = $soundexIndex[$soundex] ?? null;
```

---

## Database Migration Commands

Run in order:
```bash
# P0 migrations (critical)
mysql magento < migrations/001_create_artist_table.sql
mysql magento < migrations/002_add_dashboard_indexes.sql
mysql magento < migrations/003_convert_json_columns.sql
mysql magento < migrations/004_create_show_metadata_table.sql

# Verify
mysql magento -e "SHOW TABLES LIKE 'archivedotorg_%';"
mysql magento -e "SHOW INDEX FROM catalog_product_entity;"
```

## Database Rollback Scripts

**IMPORTANT:** Create these BEFORE running migrations.

**File:** `migrations/rollback/001_drop_artist_table.sql`
```sql
-- Rollback 001: Drop artist normalization table
-- WARNING: Run ONLY if 002-008 haven't been applied (FK dependencies)

ALTER TABLE archivedotorg_import_run DROP FOREIGN KEY fk_import_run_artist;
ALTER TABLE archivedotorg_artist_status DROP FOREIGN KEY fk_artist_status_artist;
-- ... other FKs

DROP TABLE IF EXISTS archivedotorg_artist;
```

**File:** `migrations/rollback/002_drop_dashboard_indexes.sql`
```sql
-- Rollback 002: Remove dashboard indexes
DROP INDEX idx_created_at ON catalog_product_entity;
DROP INDEX idx_artist_status_started ON archivedotorg_import_run;
DROP INDEX idx_artist_status_confidence ON archivedotorg_unmatched_track;
DROP INDEX idx_correlation_id ON archivedotorg_import_run;
DROP INDEX idx_artist_date ON archivedotorg_daily_metrics;
DROP INDEX idx_url_key ON archivedotorg_artist;
```

**File:** `migrations/rollback/003_revert_json_columns.sql`
```sql
-- Rollback 003: Revert JSON to TEXT
ALTER TABLE archivedotorg_import_run
  MODIFY options_json TEXT NULL,
  MODIFY command_args TEXT NULL;
```

**File:** `migrations/rollback/004_drop_show_metadata_table.sql`
```sql
-- Rollback 004: Drop show metadata table
-- NOTE: Data will be lost - ensure backup exists
DROP TABLE IF EXISTS archivedotorg_show_metadata;
```

---

## Verification Checklist

Before moving to Phase 1, verify ALL items:

```sql
-- 1. Artist normalization working?
SELECT COUNT(DISTINCT artist_name) FROM archivedotorg_artist;

-- 2. Indexes created?
SELECT COUNT(*) FROM information_schema.statistics
WHERE table_name = 'catalog_product_entity'
AND index_name = 'idx_created_at';
-- Should return 1

-- 3. JSON columns converted?
SELECT DATA_TYPE FROM information_schema.columns
WHERE table_name = 'archivedotorg_import_run'
AND column_name = 'options_json';
-- Should return 'json'
```

**Lock service test:**
```bash
# Terminal 1
bin/magento archive:download phish &

# Terminal 2 (should fail)
bin/magento archive:download phish
# Expected: "Cannot acquire lock - another download in progress"
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 1: Folder Migration](./02-PHASE-1-FOLDERS.md)

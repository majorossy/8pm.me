# Import History Tracking - Test Plan

## Overview
This test plan verifies that ALL CLI import commands now log to the `archivedotorg_import_run` table with complete metrics and auto-update artist statistics.

## Pre-Test Setup

1. **Verify Database Tables Exist:**
```bash
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"
```

Expected output:
- `archivedotorg_activity_log`
- `archivedotorg_artist_status`
- `archivedotorg_import_run`
- `archivedotorg_studio_albums`
- `archivedotorg_unmatched_track`

2. **Check Import Run Table is Empty or Has Baseline:**
```bash
bin/mysql -e "SELECT COUNT(*) as baseline_count FROM archivedotorg_import_run;"
```

3. **Check Artist Status Baseline:**
```bash
bin/mysql -e "SELECT artist_name, downloaded_shows, imported_tracks FROM archivedotorg_artist_status WHERE artist_name='Cabinet';"
```

## Test Case 1: Download Command Logging

### Objective
Verify `archive:download` command logs with UUID, user attribution, duration, and memory metrics.

### Steps
```bash
# 1. Run download command
bin/magento archive:download "Cabinet" --limit=3

# 2. Check latest import run record
bin/mysql -e "SELECT
  run_id,
  uuid,
  correlation_id,
  command_name,
  started_by,
  status,
  duration_seconds,
  memory_peak_mb,
  items_successful,
  artist_name,
  started_at,
  completed_at
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 1\G"
```

### Expected Results
- ✅ `uuid`: 36-character UUID (e.g., `a1b2c3d4-e5f6-7890-1234-567890abcdef`)
- ✅ `correlation_id`: 36-character UUID (different from uuid)
- ✅ `command_name`: `archive:download`
- ✅ `started_by`: `cli:chris.majorossy` (or your system username)
- ✅ `status`: `completed`
- ✅ `duration_seconds`: Non-null integer (e.g., 5-30 seconds)
- ✅ `memory_peak_mb`: Non-null integer (e.g., 50-200 MB)
- ✅ `items_successful`: 3 (matching --limit=3)
- ✅ `artist_name`: `Cabinet`
- ✅ `started_at` and `completed_at` both populated

### Artist Stats Verification
```bash
# Check that downloaded_shows incremented
bin/mysql -e "SELECT artist_name, downloaded_shows, imported_tracks FROM archivedotorg_artist_status WHERE artist_name='Cabinet';"
```

Expected: `downloaded_shows` increased by 3

## Test Case 2: Populate Command Logging

### Objective
Verify `archive:populate` command logs and auto-updates artist statistics.

### Steps
```bash
# 1. Run populate command
bin/magento archive:populate "Cabinet"

# 2. Check latest import run record
bin/mysql -e "SELECT
  run_id,
  uuid,
  correlation_id,
  command_name,
  started_by,
  status,
  duration_seconds,
  memory_peak_mb,
  items_successful,
  artist_name,
  started_at,
  completed_at
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 1\G"
```

### Expected Results
- ✅ `uuid`: Unique UUID (different from download command)
- ✅ `correlation_id`: Unique correlation ID
- ✅ `command_name`: `archive:populate`
- ✅ `started_by`: `cli:chris.majorossy`
- ✅ `status`: `completed`
- ✅ `duration_seconds`: Non-null (likely longer than download)
- ✅ `memory_peak_mb`: Non-null
- ✅ `items_successful`: Number of products created
- ✅ `artist_name`: `Cabinet`

### Artist Stats Verification
```bash
# Check that imported_tracks incremented and last_populate_at updated
bin/mysql -e "SELECT artist_name, downloaded_shows, imported_tracks, last_populate_at FROM archivedotorg_artist_status WHERE artist_name='Cabinet';"
```

Expected:
- `imported_tracks` increased by number of products created
- `last_populate_at` shows recent timestamp

## Test Case 3: Import Shows Command Logging (Deprecated)

### Objective
Verify the deprecated `archive:import-shows` command also logs properly.

### Steps
```bash
# 1. Run import-shows command (will show deprecation warning)
bin/magento archive:import-shows "STS9" --collection=STS9 --limit=2 --yes

# 2. Check latest import run record
bin/mysql -e "SELECT
  run_id,
  command_name,
  started_by,
  status,
  items_successful,
  duration_seconds,
  memory_peak_mb
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 1\G"
```

### Expected Results
- ✅ `command_name`: `archive:import-shows`
- ✅ `started_by`: `cli:chris.majorossy`
- ✅ `status`: `completed`
- ✅ All metrics populated

## Test Case 4: Multiple Command Sequence

### Objective
Verify each command creates a separate import run record with unique identifiers.

### Steps
```bash
# 1. Run sequence of commands
bin/magento archive:download "Phish" --limit=5
bin/magento archive:populate "Phish"
bin/magento archive:download "Goose" --limit=3

# 2. Check last 3 records
bin/mysql -e "SELECT
  run_id,
  uuid,
  command_name,
  artist_name,
  items_successful
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 3;"
```

### Expected Results
- ✅ Three separate records
- ✅ Each has unique `uuid`
- ✅ Commands: archive:download (Goose), archive:populate (Phish), archive:download (Phish)
- ✅ Artist names match commands

## Test Case 5: Import History Admin Grid

### Objective
Verify Import History grid displays new records correctly.

### Steps
1. Navigate to: Admin > Catalog > Archive.org > Import History
2. Should see grid with all recent imports

### Expected Results
- ✅ Grid shows download, populate, and import-shows commands
- ✅ "Started By" column shows `cli:chris.majorossy`
- ✅ "Duration" column shows calculated seconds
- ✅ "Throughput" column shows items/second (if applicable)
- ✅ "Status" column shows "Completed"
- ✅ Grid is sortable by all columns
- ✅ Grid is filterable by artist, command, status

## Test Case 6: Artist Status Grid

### Objective
Verify Artist Status grid shows auto-updated statistics.

### Steps
1. Navigate to: Admin > Catalog > Archive.org > Artists
2. Find Cabinet, Phish, Goose rows

### Expected Results
- ✅ "Downloaded Shows" reflects cumulative downloads
- ✅ "Imported Tracks" reflects cumulative products created
- ✅ "Last Populate" shows recent timestamp for Phish
- ✅ Match rate percentages calculated correctly

## Test Case 7: Failed Command Logging

### Objective
Verify failed commands log with error details.

### Steps
```bash
# 1. Run command that will fail (invalid artist)
bin/magento archive:download "NonExistentArtist12345" 2>&1 || true

# 2. Check import run record
bin/mysql -e "SELECT
  run_id,
  command_name,
  status,
  error_message,
  duration_seconds
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 1\G"
```

### Expected Results
- ✅ `status`: `failed`
- ✅ `error_message`: Contains error description
- ✅ `duration_seconds`: Still calculated

## Test Case 8: Dry Run Logging

### Objective
Verify dry runs log but don't update artist stats.

### Steps
```bash
# 1. Check baseline stats
bin/mysql -e "SELECT downloaded_shows FROM archivedotorg_artist_status WHERE artist_name='Widespread Panic';"

# 2. Run dry run
bin/magento archive:download "Widespread Panic" --limit=5 --dry-run

# 3. Check if logged
bin/mysql -e "SELECT run_id, command_name, status FROM archivedotorg_import_run ORDER BY run_id DESC LIMIT 1;"

# 4. Verify stats unchanged
bin/mysql -e "SELECT downloaded_shows FROM archivedotorg_artist_status WHERE artist_name='Widespread Panic';"
```

### Expected Results
- ✅ Dry run creates import_run record
- ✅ Artist stats remain unchanged (dry run doesn't increment)

## Test Case 9: Concurrent Command Protection

### Objective
Verify multiple commands for same artist use different correlation IDs.

### Steps
```bash
# 1. Run two commands in quick succession
bin/magento archive:download "STS9" --limit=2 &
bin/magento archive:populate "STS9" &
wait

# 2. Check correlation IDs are unique
bin/mysql -e "SELECT
  run_id,
  correlation_id,
  command_name
FROM archivedotorg_import_run
WHERE artist_name='STS9'
ORDER BY run_id DESC
LIMIT 2;"
```

### Expected Results
- ✅ Each command has unique correlation_id
- ✅ Lock protection prevents overlapping operations (one may fail with lock error)

## Test Case 10: Performance Metrics Accuracy

### Objective
Verify duration and memory metrics are accurate.

### Steps
```bash
# 1. Run command and note start/end times
date '+%Y-%m-%d %H:%M:%S' && \
bin/magento archive:download "Lettuce" --limit=10 && \
date '+%Y-%m-%d %H:%M:%S'

# 2. Compare with database duration
bin/mysql -e "SELECT
  started_at,
  completed_at,
  duration_seconds,
  TIMESTAMPDIFF(SECOND, started_at, completed_at) as actual_duration
FROM archivedotorg_import_run
ORDER BY run_id DESC
LIMIT 1;"
```

### Expected Results
- ✅ `duration_seconds` ≈ `actual_duration` (within 1 second)
- ✅ `memory_peak_mb` > 0 and reasonable (50-500 MB typically)

## Regression Tests

### Existing Functionality Still Works
```bash
# Verify commands still complete successfully
bin/magento archive:download "Phish" --limit=3
bin/magento archive:populate "Phish"
bin/magento archive:status --test-collection=Phish
```

Expected: All commands complete without errors

## Cleanup After Testing

```bash
# Optional: Clean up test data
bin/mysql -e "DELETE FROM archivedotorg_import_run WHERE artist_name IN ('Cabinet', 'Phish', 'Goose', 'STS9', 'Lettuce', 'Widespread Panic');"
```

## Test Summary Template

After completing all tests, document results:

```
IMPORT HISTORY TRACKING TEST RESULTS
Date: YYYY-MM-DD
Tester: [Your Name]

Test Case 1 (Download Logging): ✅ PASS / ❌ FAIL
Test Case 2 (Populate Logging): ✅ PASS / ❌ FAIL
Test Case 3 (Import Shows Logging): ✅ PASS / ❌ FAIL
Test Case 4 (Multiple Commands): ✅ PASS / ❌ FAIL
Test Case 5 (Admin Grid): ✅ PASS / ❌ FAIL
Test Case 6 (Artist Stats): ✅ PASS / ❌ FAIL
Test Case 7 (Failed Commands): ✅ PASS / ❌ FAIL
Test Case 8 (Dry Run): ✅ PASS / ❌ FAIL
Test Case 9 (Concurrent Protection): ✅ PASS / ❌ FAIL
Test Case 10 (Performance Metrics): ✅ PASS / ❌ FAIL
Regression Tests: ✅ PASS / ❌ FAIL

Overall: ✅ ALL TESTS PASS / ❌ SOME FAILURES

Notes:
[Any issues, observations, or recommendations]
```

## Troubleshooting

### Problem: No records appearing in import_run table
**Solution:** Check if commands are actually running by looking at output. Verify table exists with `bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_import_run';"`

### Problem: started_by shows 'cli:unknown'
**Solution:** POSIX functions may not be available. This is expected on Windows or when POSIX extension is disabled.

### Problem: Artist stats not updating
**Solution:** Check that artist exists in artist_status table first. Run `bin/magento archive:status` to initialize artists.

### Problem: duration_seconds is NULL
**Solution:** Likely an error occurred before logEnd() was called. Check status and error_message fields.

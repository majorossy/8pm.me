# Fix Artist Status Table Sync Issues

## Problem Summary

The `archivedotorg_artist_status` table is always wrong because of **3 critical bugs**:

1. **Column name mismatches** - Dashboard/Cron query wrong column names
2. **Missing row creation** - No code creates rows, so UPDATEs silently fail
3. **Incomplete sync script** - `bin/sync-artist-status` only syncs `downloaded_shows`

---

## Bug #1: Column Name Mismatch (CRITICAL)

**Schema has:** `matched_tracks`, `unmatched_tracks`, `match_rate_percent`

**Code queries:** `tracks_matched`, `tracks_unmatched`, `match_rate`

### Affected Files

| File | Line | Wrong Column | Correct Column |
|------|------|--------------|----------------|
| `Admin/Block/Adminhtml/Dashboard.php` | 75 | `tracks_matched` | `matched_tracks` |
| `Admin/Block/Adminhtml/Dashboard.php` | 79 | `tracks_unmatched` | `unmatched_tracks` |
| `Admin/Block/Adminhtml/Dashboard.php` | 98 | `tracks_matched` | `matched_tracks` |
| `Admin/Block/Adminhtml/Dashboard.php` | 102 | `tracks_unmatched` | `unmatched_tracks` |
| `Admin/Cron/AggregateDailyMetrics.php` | 132 | `tracks_matched` | `matched_tracks` |
| `Admin/Cron/AggregateDailyMetrics.php` | 133 | `tracks_unmatched` | `unmatched_tracks` |
| `Admin/Cron/AggregateDailyMetrics.php` | 134 | `match_rate` | `match_rate_percent` |

---

## Bug #2: Missing Row Creation (CRITICAL)

**Problem:** No code creates rows in `archivedotorg_artist_status`. All UPDATE statements silently fail because WHERE clause doesn't match any rows.

**Current behavior:**
- `SetupArtistCommand` → Creates categories only (NOT artist_status rows)
- `DownloadCommand` → Calls `BaseLoggedCommand.updateArtistStats()` which only UPDATEs
- `PopulateCommand` → Same - only UPDATEs
- `ArtistEnrichmentService` → Does INSERT but only when running `archive:artist:enrich`

**Result:** Unless you run `archive:artist:enrich` first, no artist_status row exists, so:
- `bin/magento archive:download "Artist"` → UPDATE fails silently (0 rows affected)
- `bin/magento archive:populate "Artist"` → UPDATE fails silently (0 rows affected)

### Fix

Modify `BaseLoggedCommand.updateArtistStats()` to use `INSERT ... ON DUPLICATE KEY UPDATE` or check-then-insert logic:

```php
// In BaseLoggedCommand.php, replace UPDATE with insertOnDuplicate:
$connection->insertOnDuplicate(
    $artistTable,
    [
        'artist_name' => $artistName,
        'collection_id' => $collectionId,
        'downloaded_shows' => $itemsSuccessful,
        'created_at' => new \Zend_Db_Expr('NOW()'),
        'updated_at' => new \Zend_Db_Expr('NOW()')
    ],
    ['downloaded_shows' => new \Zend_Db_Expr('downloaded_shows + VALUES(downloaded_shows)')]
);
```

---

## Bug #3: Incomplete Sync Script

**Problem:** `bin/sync-artist-status` only syncs `downloaded_shows` from filesystem, but:
- Doesn't update `imported_tracks` (should count products)
- Doesn't update `matched_tracks`/`unmatched_tracks`
- Doesn't create missing rows (only updates existing)

---

## Implementation Plan

### Step 1: Fix Column Name Mismatches

**Files to modify:**
- `src/app/code/ArchiveDotOrg/Admin/Block/Adminhtml/Dashboard.php`
- `src/app/code/ArchiveDotOrg/Admin/Cron/AggregateDailyMetrics.php`

**Changes:**
- Replace `tracks_matched` → `matched_tracks`
- Replace `tracks_unmatched` → `unmatched_tracks`
- Replace `match_rate` → `match_rate_percent`

### Step 2: Add Row Creation to BaseLoggedCommand

**File:** `src/app/code/ArchiveDotOrg/Core/Console/Command/BaseLoggedCommand.php`

**Location:** `updateArtistStats()` method (lines 466-519)

**Current code (lines 489-491):**
```php
$artistName = $importData['artist_name'];
$commandName = $importData['command_name'] ?? '';
$itemsSuccessful = (int)($importData['items_successful'] ?? 0);
```

**Add after line 491:**
```php
$collectionId = $importData['collection_id'] ?? '';
```

**Replace download UPDATE (lines 494-500) with:**
```php
if (str_contains($commandName, 'download')) {
    $connection->insertOnDuplicate(
        $artistTable,
        [
            'artist_name' => $artistName,
            'collection_id' => $collectionId,
            'downloaded_shows' => $itemsSuccessful,
            'last_download_at' => new \Zend_Db_Expr('NOW()'),
            'created_at' => new \Zend_Db_Expr('NOW()'),
            'updated_at' => new \Zend_Db_Expr('NOW()')
        ],
        [
            'downloaded_shows' => new \Zend_Db_Expr('downloaded_shows + VALUES(downloaded_shows)'),
            'last_download_at' => new \Zend_Db_Expr('NOW()'),
            'updated_at' => new \Zend_Db_Expr('NOW()')
        ]
    );
}
```

**Replace populate UPDATE (lines 501-510) with:**
```php
elseif (str_contains($commandName, 'populate')) {
    $connection->insertOnDuplicate(
        $artistTable,
        [
            'artist_name' => $artistName,
            'collection_id' => $collectionId,
            'imported_tracks' => $itemsSuccessful,
            'last_populate_at' => new \Zend_Db_Expr('NOW()'),
            'created_at' => new \Zend_Db_Expr('NOW()'),
            'updated_at' => new \Zend_Db_Expr('NOW()')
        ],
        [
            'imported_tracks' => new \Zend_Db_Expr('imported_tracks + VALUES(imported_tracks)'),
            'last_populate_at' => new \Zend_Db_Expr('NOW()'),
            'updated_at' => new \Zend_Db_Expr('NOW()')
        ]
    );
}
```

### Step 3: Enhance bin/sync-artist-status Script

**File:** `bin/sync-artist-status`

**Changes:**
1. Use `INSERT ... ON DUPLICATE KEY UPDATE` to create missing rows
2. Also sync `imported_tracks` by counting products
3. Report what was actually synced

### Step 4: Create Full Rebuild Script (Optional)

Create `bin/rebuild-artist-status` that:
1. Truncates `archivedotorg_artist_status`
2. Queries filesystem for `downloaded_shows`
3. Queries product table for `imported_tracks`
4. Inserts fresh rows for all artists

---

## Files to Modify

| File | Changes |
|------|---------|
| `Admin/Block/Adminhtml/Dashboard.php` | Fix column names (4 places) |
| `Admin/Cron/AggregateDailyMetrics.php` | Fix column names (3 places) |
| `Core/Console/Command/BaseLoggedCommand.php` | Use insertOnDuplicate in updateArtistStats() |
| `bin/sync-artist-status` | Enhance to create rows and sync more fields |

---

## Verification

After fixes, run:

```bash
# 1. Clear and rebuild status table
bin/sync-artist-status

# 2. Verify counts match filesystem
docker exec 8pm-phpfpm-1 bash -c 'for dir in /var/www/html/var/archivedotorg/metadata/*/; do
    name=$(basename "$dir");
    count=$(ls -1 "$dir"*.json 2>/dev/null | wc -l);
    echo "$name: $count shows";
done' | sort

# 3. Compare to database
bin/mysql -e "SELECT artist_name, collection_id, downloaded_shows, imported_tracks FROM archivedotorg_artist_status ORDER BY artist_name;"

# 4. Test download/populate updates correctly
bin/magento archive:download "Railroad Earth"
bin/mysql -e "SELECT * FROM archivedotorg_artist_status WHERE artist_name = 'Railroad Earth';"

# 5. Check admin dashboard loads without errors
# Navigate to Catalog > Archive.org > Dashboard
```

---

## Risk Assessment

- **Low risk** - Column name fixes are straightforward string replacements
- **Medium risk** - Changing UPDATE to insertOnDuplicate requires careful testing
- **No data loss** - All changes are additive or corrective

# Archive.org Import Rearchitecture Plan

## Problem Summary

Current system has:
- **35 hardcoded artists** across multiple data patches:
  - `CreateCategoryStructure.php` - 7 artists
  - `AddAdditionalArtists.php` - 28 artists
  - `AddTracksGroup1-5.php` - additional tracks for existing artists
- **3 confusing commands** with unclear data flow
- **Global track matching** - no album context, matches by title only
- **Adding new artists requires code changes** to data patches
- **Silent failures** - Unmatched tracks skipped with no visibility
- **Data loss risk** - Some commands bypass permanent storage, using only Redis cache (24hr TTL)

---

## ⚠️ CRITICAL REQUIREMENT: Permanent JSON Storage

**All Archive.org API responses MUST be saved permanently before any processing.**

### Why This Matters
- Archive.org API calls are slow (rate limited, 750ms+ per request)
- Downloading 500 shows takes ~6-8 minutes
- If only stored in Redis (24hr TTL), data is LOST when cache expires
- Re-processing requires re-downloading everything
- **We discovered Matisyahu, God Street Wine, Rusted Root data was lost** because it bypassed permanent storage

### Storage Rules
1. **NEVER create products without first saving JSON to disk**
2. **NEVER rely solely on Redis/Magento cache for Archive.org data**
3. **ALL commands must use the permanent JSON storage layer**

### Folder Structure (Organized by Artist)

**Old (flat, unorganized):**
```
var/archivedotorg/metadata/
├── phish2023-07-14.json
├── billystrings2024-01-01.json
├── jrad2023-08-05.json
└── ... (2000+ files in one folder)
```

**New (organized by collection):**
```
var/archivedotorg/metadata/
├── Phish/
│   ├── phish2023-07-14.sbd.miller.flac16.json
│   ├── phish2023-07-15.json
│   └── ...
├── BillyStrings/
│   ├── billystrings2024-01-01.json
│   └── ...
├── JoeRussosAlmostDead/
│   └── ...
└── download_progress.json
```

**Benefits:**
- Easy to see what's downloaded per artist
- Filesystem performs better (not 10k+ files in one folder)
- Can delete/re-download single artist without affecting others
- Clear ownership of data

---

## Architecture Review Findings

### What's Already Good (Keep)
- Batching & memory management in `BulkProductImporter`
- Concurrent API fetching in `ConcurrentApiClient`
- Two-phase approach (download → populate) allows resumability
- Incremental updates (`--incremental` flag) for new shows only

### What to Remove/Deprecate
- `archive:import:shows` - **DELETE** - bypasses permanent storage
- `ApiResponseCache` for Archive.org data - **DEPRECATE** - only use JSON files
- Any command that creates products without saving JSON first

### Performance Warning
- **Fuzzy matching (Levenshtein) is expensive** - O(n*m) per comparison
- 262,500 products × 3,000 categories = 787M comparisons
- Would add **5+ minutes per artist** (vs current <1ms exact match)
- **Recommendation:** Keep exact match primary, fuzzy as OPTIONAL fallback only

---

## Complete Data Capture

**Goal:** Save ALL available data from Archive.org JSON to Magento product attributes.

### What Archive.org JSON Contains

```
{
  // TOP-LEVEL (show metadata)
  "d1": "ia800107.us.archive.org",        // Primary streaming server
  "d2": "ia600107.us.archive.org",        // Backup server
  "dir": "/3/items/phish2023-07-14...",   // Directory path
  "files_count": 68,                       // Total files in show
  "item_size": 974260032,                  // Total bytes
  "item_last_updated": 1743231671,         // Unix timestamp
  "created": 1769588214,                   // Unix timestamp
  "workable_servers": ["ia800107...", ...], // All available servers

  // METADATA OBJECT
  "metadata": {
    "identifier": "phish2023-07-14...",
    "title": "Phish Live at...",
    "date": "2023-07-14",
    "year": "2023",
    "venue": "MSG",
    "coverage": "New York, NY",
    "creator": "Phish",
    "taper": "John Smith",
    "transferer": "Jane Doe",
    "source": "SBD > DAT > ...",
    "lineage": "DAT > WAV > FLAC",
    "notes": "Great show...",
    "description": "<div>Set 1:...</div>",
    "uploader": "user@email.com",          // ❌ NOT CAPTURED
    "publicdate": "2023-07-15 10:00:00",
    "addeddate": "2023-07-15 10:00:00"
  },

  // FILES ARRAY (per track)
  "files": [{
    "name": "phish2023-07-14t01.flac",
    "title": "Tweezer",
    "track": "01",
    "length": "1065.09",                   // Seconds
    "format": "Flac",
    "size": "100623269",                   // Bytes ❌ NOT CAPTURED
    "md5": "16955d8547085d687...",          // ❌ NOT CAPTURED
    "sha1": "d91ff81acf9fe5e53...",
    "external-identifier": "urn:acoustid:06d64cba-...",  // ❌ NOT CAPTURED
    "bitrate": "186"                       // For MP3s ❌ NOT CAPTURED
  }],

  // REVIEWS ARRAY
  "reviews": [{                            // ❌ NOT CAPTURED (only count/avg)
    "reviewbody": "Amazing show!",
    "reviewtitle": "Best Tweezer ever",
    "reviewer": "username",
    "stars": "5",
    "reviewdate": "2023-07-16"
  }]
}
```

### Current vs Required Attributes

#### Track-Level Attributes (per product)

| Attribute | Status | JSON Source | Type |
|-----------|--------|-------------|------|
| `title` | ✅ Exists | `files[].title` | varchar |
| `length` | ✅ Exists | `files[].length` | varchar |
| `album_track` | ✅ Exists | `files[].track` | int |
| `song_url` | ✅ Exists | Built from server+dir+name | varchar |
| `track_file_size` | ❌ **NEW** | `files[].size` | int |
| `track_md5` | ❌ **NEW** | `files[].md5` | varchar |
| `track_acoustid` | ❌ **NEW** | `files[].external-identifier` | varchar |
| `track_bitrate` | ❌ **NEW** | `files[].bitrate` | varchar |

#### Show-Level Attributes (same for all tracks in a show)

| Attribute | Status | JSON Source | Type |
|-----------|--------|-------------|------|
| `identifier` | ✅ Exists | `metadata.identifier` | varchar |
| `show_name` | ✅ Exists | `metadata.title` | varchar |
| `show_date` | ✅ Exists | `metadata.date` | varchar |
| `show_year` | ✅ Exists | `metadata.year` | select |
| `show_venue` | ✅ Exists | `metadata.venue` | select |
| `show_location` | ✅ Exists | `metadata.coverage` | select |
| `show_taper` | ✅ Exists | `metadata.taper` | select |
| `show_transferer` | ✅ Exists | `metadata.transferer` | select |
| `show_source` | ✅ Exists | `metadata.source` | varchar |
| `lineage` | ✅ Exists | `metadata.lineage` | text |
| `notes` | ✅ Exists | `metadata.notes` | text |
| `dir` | ✅ Exists | `dir` | varchar |
| `server_one` | ✅ Exists | `d1` | varchar |
| `server_two` | ✅ Exists | `d2` | varchar |
| `pub_date` | ✅ Exists | `metadata.publicdate` | varchar |
| `guid` | ✅ Exists | GUID | varchar |
| `archive_collection` | ✅ Exists | Artist name | select |
| `archive_avg_rating` | ✅ Exists | Calculated from reviews | decimal |
| `archive_num_reviews` | ✅ Exists | `reviews.length` | int |
| `archive_downloads` | ✅ Exists | From batch API | int |
| `archive_downloads_week` | ✅ Exists | From batch API | int |
| `archive_downloads_month` | ✅ Exists | From batch API | int |
| `show_files_count` | ❌ **NEW** | `files_count` | int |
| `show_total_size` | ❌ **NEW** | `item_size` | int |
| `show_uploader` | ❌ **NEW** | `metadata.uploader` | varchar |
| `show_created_date` | ❌ **NEW** | `created` (formatted) | varchar |
| `show_last_updated` | ❌ **NEW** | `item_last_updated` (formatted) | varchar |
| `show_workable_servers` | ❌ **NEW** | `workable_servers` (JSON) | text |
| `show_reviews_json` | ❌ **NEW** | `reviews` (full JSON) | text |

### Implementation Tasks

#### 1. Create New EAV Attributes

**New Data Patch:** `Setup/Patch/Data/AddExtendedArchiveAttributes.php`

```php
// Track-level attributes
$this->createAttribute('track_file_size', 'Track File Size (bytes)', 'int');
$this->createAttribute('track_md5', 'Track MD5 Hash', 'varchar');
$this->createAttribute('track_acoustid', 'Track Acoustid', 'varchar');
$this->createAttribute('track_bitrate', 'Track Bitrate', 'varchar');

// Show-level attributes
$this->createAttribute('show_files_count', 'Show File Count', 'int');
$this->createAttribute('show_total_size', 'Show Total Size (bytes)', 'int');
$this->createAttribute('show_uploader', 'Show Uploader', 'varchar');
$this->createAttribute('show_created_date', 'Archive.org Created Date', 'varchar');
$this->createAttribute('show_last_updated', 'Archive.org Last Updated', 'varchar');
$this->createAttribute('show_workable_servers', 'Workable Servers (JSON)', 'text');
$this->createAttribute('show_reviews_json', 'Reviews (JSON)', 'text');
```

#### 2. Update Data Transfer Objects

**File:** `Model/Data/Track.php` - Add:
```php
private ?string $md5 = null;
private ?string $acoustid = null;
private ?string $bitrate = null;
// + getters/setters
```

**File:** `Model/Data/Show.php` - Add:
```php
private ?int $filesCount = null;
private ?int $itemSize = null;
private ?string $uploader = null;
private ?int $createdTimestamp = null;
private ?int $lastUpdatedTimestamp = null;
private ?array $workableServers = null;
private ?array $reviews = null;
// + getters/setters
```

#### 3. Update JSON Parser

**File:** `Model/TrackPopulatorService.php` (or new `MetadataParser.php`)

Extract all fields from JSON when parsing:
```php
// Track fields
$track->setFileSize((int)($fileData['size'] ?? 0));
$track->setMd5($fileData['md5'] ?? null);
$track->setAcoustid($fileData['external-identifier'] ?? null);
$track->setBitrate($fileData['bitrate'] ?? null);

// Show fields
$show->setFilesCount((int)($data['files_count'] ?? 0));
$show->setItemSize((int)($data['item_size'] ?? 0));
$show->setUploader($metadata['uploader'] ?? null);
$show->setCreatedTimestamp((int)($data['created'] ?? 0));
$show->setLastUpdatedTimestamp((int)($data['item_last_updated'] ?? 0));
$show->setWorkableServers($data['workable_servers'] ?? []);
$show->setReviews($data['reviews'] ?? []);
```

#### 4. Update Product Importer

**File:** `Model/TrackImporter.php` - Add to `setProductData()`:
```php
// Track attributes
$product->setData('track_file_size', $track->getFileSize());
$product->setData('track_md5', $track->getMd5());
$product->setData('track_acoustid', $track->getAcoustid());
$product->setData('track_bitrate', $track->getBitrate());

// Show attributes
$product->setData('show_files_count', $show->getFilesCount());
$product->setData('show_total_size', $show->getItemSize());
$product->setData('show_uploader', $show->getUploader());
$product->setData('show_created_date', date('Y-m-d H:i:s', $show->getCreatedTimestamp()));
$product->setData('show_last_updated', date('Y-m-d H:i:s', $show->getLastUpdatedTimestamp()));
$product->setData('show_workable_servers', json_encode($show->getWorkableServers()));
$product->setData('show_reviews_json', json_encode($show->getReviews()));
```

### Frontend Use Cases

| Attribute | Frontend Use |
|-----------|--------------|
| `track_file_size` | "3.2 MB" next to track |
| `track_bitrate` | "320 kbps" quality indicator |
| `show_files_count` | "12 tracks" badge |
| `show_total_size` | "Total: 850 MB" for download info |
| `show_reviews_json` | Display actual review text |
| `show_workable_servers` | Better streaming failover |
| `track_acoustid` | Future: music fingerprint matching |

---

## New Architecture

### Terminology (Clarified)
| Term | Meaning | Example |
|------|---------|---------|
| **Version** | Audio file from Archive.org (a live performance of a track) | Tweezer from 2023-07-14 Phish show |
| **Track** | Track category under an album (the canonical song) | Tweezer (category) |
| **Show** | A live concert/show on Archive.org | phish2023-07-14.sbd.miller.flac16 |

**Naming Convention:** Use "Track" and "Show" in all code/class names. "Version" is conceptual only.

### Data Flow (Two-Phase, Always Permanent)
```
PHASE 1: Download (can be interrupted/resumed)
──────────────────────────────────────────────
Archive.org API
       ↓
bin/magento archive:download <artist>
       ↓
var/archivedotorg/metadata/{Artist}/*.json  ← PERMANENT
       ↓
download_progress.json (tracks status)


PHASE 2: Process (reads from disk, never hits API)
──────────────────────────────────────────────────
var/archivedotorg/metadata/{Artist}/*.json
       ↓
bin/magento archive:populate <artist>
       ↓
Magento Categories + Products
```

### Commands (Simplified)

```bash
# 0. Validate: Check YAML config before processing (optional)
bin/magento archive:validate lettuce
# Output: ✓ Valid configuration for Lettuce (5 albums, 48 tracks)

# 1. Setup: Create artist categories from YAML config
bin/magento archive:setup lettuce

# 2. Download: Fetch metadata from Archive.org → save to JSON (REQUIRED FIRST)
bin/magento archive:download lettuce
bin/magento archive:download lettuce --incremental  # Only new shows
bin/magento archive:download lettuce --resume       # Resume from last failed show

# 3. Populate: Read JSON → match tracks → create products
bin/magento archive:populate lettuce --limit=10
bin/magento archive:populate lettuce --dry-run  # Preview without creating

# 4. Status: View what's downloaded and processed
bin/magento archive:status lettuce
```

### Command Migration Path

**Phase 1: Create new commands (Week 1)**
- Create `archive:setup` (new)
- Create `archive:download` (replaces `download:metadata`)
- Create `archive:populate` (replaces `populate:tracks`)

**Phase 2: Add deprecation warnings (Week 2)**
- `archive:download:metadata` → Shows warning: "Deprecated. Use 'archive:download' instead."
- `archive:populate:tracks` → Shows warning: "Deprecated. Use 'archive:populate' instead."
- Both still work (call new commands internally)

**Phase 3: Delete old commands (Week 4)**
- After confirming new commands work in production
- Remove `download:metadata`, `populate:tracks` from `di.xml`
- Delete command files

**Phase 4: Delete bypass command (Week 4)**
- Remove `archive:import:shows` completely (no replacement - it bypassed storage)

### Commands to DELETE
| Command | Reason | Migration Path |
|---------|--------|----------------|
| `archive:import:shows` | Bypasses permanent storage, uses only Redis cache | Delete completely (no migration) |
| `archive:download:metadata` | Replaced by `archive:download` | Deprecate → Alias → Delete |
| `archive:populate:tracks` | Replaced by `archive:populate` | Deprecate → Alias → Delete |

---

## Implementation

### Phase 1: YAML Configuration System

**New Files:**
- `app/code/ArchiveDotOrg/Core/config/artists/*.yaml` - Artist configs (albums, tracks, aliases)
- `Model/ArtistConfigLoader.php` - Loads and validates YAML
- `Model/ArtistConfigValidator.php` - Validates YAML schema
- `Console/Command/SetupArtistCommand.php` - `archive:setup` command

**YAML Location:** `app/code/ArchiveDotOrg/Core/config/artists/`

**Why not `var/` or `etc/`:**
- `var/` is for generated/temporary files (might get cleared)
- `etc/` is for XML system configuration
- `app/code/.../config/` is for module data configs (version controlled, permanent)

**Example YAML** (`app/code/ArchiveDotOrg/Core/config/artists/lettuce.yaml`):
```yaml
artist:
  name: "Lettuce"
  collection_id: "Lettuce"
  url_key: "lettuce"

albums:
  - name: "Outta Here"
    url_key: "outtahere"
    tracks:
      - name: "Phyllis"
        url_key: "phyllis"
        aliases: ["phillis", "phylis"]  # Handle typos
      - name: "Sam Huff"
        url_key: "samhuff"

matching:
  fuzzy_threshold: 85
  strip_segue_markers: true
```

### Phase 2: Update MetadataDownloader for Folder Structure

**Changes to `Model/MetadataDownloader.php`:**
```php
// OLD: var/archivedotorg/metadata/{identifier}.json
// NEW: var/archivedotorg/metadata/{collectionId}/{identifier}.json

private function getCacheFilePath(string $collectionId, string $identifier): string
{
    return $this->varDir . '/' . self::CACHE_DIR . '/' . $collectionId . '/' . $identifier . '.json';
}
```

**Migration script** to move existing files:
```bash
bin/magento archive:migrate:organize-folders
# Moves 2000+ files into artist subfolders based on identifier patterns
```

### Phase 3: Unified Populate Command + Visibility

**Critical New Feature: Unmatched Track Logging**

Currently unmatched tracks are silently skipped. Add:
```bash
# Export unmatched tracks for YAML refinement
bin/magento archive:populate lettuce --export-unmatched=unmatched.txt

# View what was skipped
bin/magento archive:show-unmatched lettuce
```

Output format:
```
[UNMATCHED] "Free-form Jam" from show gd1977-05-08 (no matching track category)
[UNMATCHED] "Estimate Prophet" from show gd1978-01-22 (typo? closest: "Estimated Prophet")
```

**Key Improvement - Album-Context Matching:**
```
Before: "Phyllis" matches ANY "Phyllis" category globally
After:  "Phyllis" matches Lettuce → Outta Here → Phyllis specifically
```

**Matching Algorithm:**
1. **Exact match** (O(1) hash lookup - PRIMARY, fast)
2. Alias match (from YAML config)
3. **Fuzzy match OPTIONAL** (only if enabled, expensive)
4. Global fallback (existing behavior)

**Critical:** Fuzzy matching disabled by default. Only enable via flag when needed.

### Phase 4: Migration & Patch Removal

**Extract existing data to YAML:**
```bash
bin/magento archive:migrate:export
# Creates 35 YAML files from the 6 data patch files
```

**Delete Data Patches (site not live - no backwards compat needed):**
```bash
# Remove patch files
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup{1,2,3,4,5}.php

# Clear patch history from database
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%CreateCategoryStructure%'"
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddAdditionalArtists%'"
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddTracksGroup%'"
```

**Delete bypass command:**
```bash
rm src/app/code/ArchiveDotOrg/Core/Console/Command/ImportShowsCommand.php
# Update etc/di.xml to remove registration
```

**Result:**
- YAML is the single source of truth
- No legacy code to maintain
- Clean architecture from the start

---

## Files to Modify

| File | Change |
|------|--------|
| `etc/di.xml` | Register new services, remove ImportShowsCommand |
| `Model/MetadataDownloader.php` | Add collectionId to file paths (subfolder structure) |
| `Model/TrackPopulatorService.php` | Add album-context matching, use new TrackMatcherService, parse extended attributes |
| `Model/CategoryAssignmentService.php` | Add album-context assignment |
| `Model/Data/Track.php` | Add md5, acoustid, bitrate, fileSize fields + getters/setters |
| `Model/Data/Show.php` | Add filesCount, itemSize, uploader, timestamps, workableServers, reviews |
| `Api/Data/TrackInterface.php` | Add interface methods for new Track fields |
| `Api/Data/ShowInterface.php` | Add interface methods for new Show fields |
| `Model/TrackImporter.php` | Save all new attributes to products |
| `Model/BulkProductImporter.php` | Save all new attributes (direct SQL version) |

**Data Patches to DELETE:**
| File | Current State |
|------|---------------|
| `Setup/Patch/Data/CreateCategoryStructure.php` | 7 artists, ~950 lines |
| `Setup/Patch/Data/AddAdditionalArtists.php` | 28 artists |
| `Setup/Patch/Data/AddTracksGroup1.php` | Additional tracks |
| `Setup/Patch/Data/AddTracksGroup2.php` | Additional tracks |
| `Setup/Patch/Data/AddTracksGroup3.php` | Additional tracks |
| `Setup/Patch/Data/AddTracksGroup4.php` | Additional tracks (includes Lettuce) |
| `Setup/Patch/Data/AddTracksGroup5.php` | Additional tracks |

**Commands to DELETE:**
| File | Reason |
|------|--------|
| `Console/Command/ImportShowsCommand.php` | Bypasses permanent JSON storage |

## Files to Create

| File | Purpose |
|------|---------|
| `Setup/Patch/Data/AddExtendedArchiveAttributes.php` | Create 11 new EAV attributes |
| `Model/ArtistConfigLoader.php` | Load YAML configs from `config/artists/` |
| `Model/ArtistConfigValidator.php` | Validate YAML schema (required fields, format) |
| `Model/TrackMatcherService.php` | Album-context + optional fuzzy matching |
| `Console/Command/BaseLoggedCommand.php` | Abstract base with correlation IDs, auto-logging |
| `Console/Command/ValidateArtistCommand.php` | `archive:validate` - Validate YAML schema |
| `Console/Command/SetupArtistCommand.php` | `archive:setup` (extends BaseLoggedCommand) |
| `Console/Command/DownloadCommand.php` | `archive:download` (extends BaseLoggedCommand) |
| `Console/Command/PopulateCommand.php` | `archive:populate` (extends BaseLoggedCommand) |
| `Console/Command/MigrateExportCommand.php` | Export hardcoded data to YAML |
| `Console/Command/MigrateOrganizeFoldersCommand.php` | Move flat files to subfolders |
| `Model/Redis/ProgressTracker.php` | Redis-based real-time progress tracking |

**Note:** JSON parsing stays in `TrackPopulatorService` (no separate MetadataParser needed - avoids duplication).

---

## Adding a New Artist (Final Workflow)

```bash
# 1. Create YAML config file
cp app/code/ArchiveDotOrg/Core/config/artists/template.yaml \
   app/code/ArchiveDotOrg/Core/config/artists/lettuce.yaml

# Edit lettuce.yaml:
# - Add artist name and Archive.org collection ID
# - List all studio albums with tracks
# - Add track aliases for common typos
# - Configure fuzzy matching (default: disabled)

# 2. Validate YAML (optional but recommended)
bin/magento archive:validate lettuce
# Output: ✓ Valid YAML configuration for Lettuce

# 3. Create categories in Magento
bin/magento archive:setup lettuce
# Output: Created 1 artist, 5 albums, 48 track categories

# 4. Download metadata from Archive.org (PERMANENT STORAGE)
bin/magento archive:download lettuce --limit=10
# Files saved to: var/archivedotorg/metadata/Lettuce/*.json
# Progress tracked in: var/archivedotorg/download_progress.json

# 5. Preview matching before creating products
bin/magento archive:populate lettuce --dry-run --limit=10
# Output: Would create 120 products, 5 unmatched tracks

# 6. Create products from downloaded data
bin/magento archive:populate lettuce
# Output: Created 120 products, logged 5 unmatched tracks

# 7. Review unmatched tracks in admin
# Navigate to: Content > Archive.org > Unmatched Tracks
# Fix: Add aliases to lettuce.yaml, re-run populate

# 8. Later: Get new shows only (incremental updates)
bin/magento archive:download lettuce --incremental
bin/magento archive:populate lettuce

# 9. Or resume from crash:
bin/magento archive:download lettuce --resume
```

**Key Points:**
- ✅ YAML is version controlled in `app/code/` (not `var/`)
- ✅ Validation catches errors before processing
- ✅ Two-phase approach allows previewing before committing
- ✅ Unmatched tracks are logged (not silently skipped)
- ✅ Resume support for crash recovery

---

## Verification

### Phase 0: New Attributes
1. Run `bin/magento setup:upgrade` to create new EAV attributes
2. Verify 11 new attributes exist in admin: **Stores > Attributes > Product**
   - Track: `track_file_size`, `track_md5`, `track_acoustid`, `track_bitrate`
   - Show: `show_files_count`, `show_total_size`, `show_uploader`, `show_created_date`, `show_last_updated`, `show_workable_servers`, `show_reviews_json`

### Phase 1: Folder Organization
3. Run `archive:migrate:organize-folders` and verify files moved to artist subfolders
4. Verify structure: `var/archivedotorg/metadata/{Artist}/*.json`

### Phase 2: YAML Migration
5. Run `archive:migrate:export` and verify 35 YAML files created in `app/code/ArchiveDotOrg/Core/config/artists/`
6. Validate one YAML file: `bin/magento archive:validate phish` (should pass)
7. Test invalid YAML (missing required field) - should fail with clear error message
8. Delete data patches and clear `patch_list` entries
9. Delete `ImportShowsCommand.php`

### Phase 3: New Commands
8. Run `archive:setup lettuce` and verify categories created in admin
9. Run `archive:download lettuce --limit=5` and verify JSON files in `var/archivedotorg/metadata/Lettuce/`
10. Run `archive:populate lettuce --dry-run --limit=5` and check matching output
11. Run `archive:populate lettuce --limit=5` and verify products created

### Phase 4: Data Verification
12. Check products are assigned to correct track categories (Artist → Album → Track)
13. **Verify ALL attributes populated on products:**
    ```sql
    SELECT sku,
           track_file_size, track_md5, track_acoustid,
           show_files_count, show_total_size, show_uploader,
           show_reviews_json
    FROM catalog_product_entity cpe
    JOIN catalog_product_entity_int file_size ON cpe.entity_id = file_size.entity_id
    -- ... (check a sample product has all fields)
    ```
14. Test `--incremental` flag fetches only new shows
15. Run `bin/magento setup:upgrade` - should complete with no patch errors

### Phase 5: Frontend Verification
16. Verify track file size displays correctly (e.g., "3.2 MB")
17. Verify reviews JSON can be parsed and displayed
18. Test streaming failover uses `show_workable_servers`

---

## Resume Logic & Progress Tracking

### Problem: Crash Recovery

If a download crashes at show 500/2847:
- Without resume: Starts over from show 1 (wasted 6 minutes)
- With resume: Continues from show 500 (saves time)

### Implementation

**Progress File:** `var/archivedotorg/download_progress.json`

```json
{
  "Phish": {
    "collection_id": "Phish",
    "status": "running",
    "started_at": "2026-01-28T14:30:00Z",
    "total_recordings": 2847,
    "downloaded": 500,
    "last_identifier": "phish2023-07-14.sbd.miller.flac16",
    "failed_identifiers": ["phish2023-01-05.bad"],
    "completed_identifiers": ["phish2023-07-14...", "phish2023-07-13..."]
  }
}
```

**Download Command Logic:**

```php
// Normal mode: Download all
bin/magento archive:download phish

// Incremental: Skip existing JSON files on disk
bin/magento archive:download phish --incremental

// Resume: Skip identifiers in completed_identifiers array
bin/magento archive:download phish --resume
```

**Difference:**
- `--incremental`: Checks filesystem (`metadata/Phish/*.json` exists?)
- `--resume`: Checks progress file (`completed_identifiers` array)
- Resume is faster (no disk I/O), incremental is safer (source of truth is filesystem)

### Redis Schema for Real-Time Dashboard

**Purpose:** Fast reads for live dashboard without hitting database.

**Key Patterns:**

```redis
# Current progress (updated every show)
SET archivedotorg:progress:{artist}:current "{\"show_id\": \"phish2023-07-14\", \"tracks\": 24}"
EXPIRE archivedotorg:progress:{artist}:current 3600

# Total counts
SET archivedotorg:progress:{artist}:total 2847
SET archivedotorg:progress:{artist}:processed 500
EXPIRE archivedotorg:progress:{artist}:total 3600

# ETA calculation
SET archivedotorg:progress:{artist}:eta "11m 23s"
SET archivedotorg:progress:{artist}:throughput 3.1
EXPIRE archivedotorg:progress:{artist}:eta 3600

# Status
SET archivedotorg:progress:{artist}:status "running"  # queued, running, completed, failed
EXPIRE archivedotorg:progress:{artist}:status 3600

# Correlation ID (for log tracing)
SET archivedotorg:progress:{artist}:correlation_id "550e8400-e29b-41d4-a716-446655440000"
EXPIRE archivedotorg:progress:{artist}:correlation_id 3600
```

**TTL Strategy:**
- All keys: 1 hour TTL (refreshed on each update)
- Keys auto-expire if process crashes
- Dashboard shows "No active imports" if keys missing

**Cleanup:**
- On command completion: Delete all progress keys for that artist
- Cron job (daily 4 AM): Scan for expired keys older than 24 hours

**Redis vs Database:**
| Aspect | Redis | Database |
|--------|-------|----------|
| Read Speed | <1ms | ~10-50ms |
| Purpose | Real-time dashboard | Audit trail |
| Retention | 1 hour (ephemeral) | Permanent |
| Data | Current progress only | Full history |

---

## YAML Configuration Validation

### Schema Rules

```php
class ArtistConfigValidator
{
    public function validate(array $yaml): ValidationResult
    {
        $errors = [];

        // 1. Required top-level keys
        if (!isset($yaml['artist']['name'])) {
            $errors[] = "Missing required field: artist.name";
        }
        if (!isset($yaml['artist']['collection_id'])) {
            $errors[] = "Missing required field: artist.collection_id";
        }

        // 2. Collection ID format (alphanumeric, underscore, hyphen only)
        if (isset($yaml['artist']['collection_id'])) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $yaml['artist']['collection_id'])) {
                $errors[] = "Invalid collection_id format. Must be alphanumeric with _ or -";
            }
        }

        // 3. URL keys must be URL-safe
        if (isset($yaml['artist']['url_key'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $yaml['artist']['url_key'])) {
                $errors[] = "Invalid url_key format. Must be lowercase alphanumeric with hyphens";
            }
        }

        // 4. Albums array
        if (!isset($yaml['albums']) || !is_array($yaml['albums'])) {
            $errors[] = "Missing or invalid 'albums' array";
        } else {
            foreach ($yaml['albums'] as $idx => $album) {
                // Required album fields
                if (!isset($album['name'])) {
                    $errors[] = "Album[$idx]: Missing 'name'";
                }
                if (!isset($album['tracks']) || !is_array($album['tracks'])) {
                    $errors[] = "Album[$idx]: Missing or invalid 'tracks' array";
                }

                // Check for duplicate track names within same album
                $trackNames = array_column($album['tracks'] ?? [], 'name');
                $duplicates = array_diff_assoc($trackNames, array_unique($trackNames));
                if (!empty($duplicates)) {
                    $errors[] = "Album[$idx]: Duplicate track names: " . implode(', ', $duplicates);
                }
            }
        }

        // 5. Fuzzy matching config (optional but validated if present)
        if (isset($yaml['matching']['fuzzy_threshold'])) {
            $threshold = $yaml['matching']['fuzzy_threshold'];
            if (!is_int($threshold) || $threshold < 0 || $threshold > 100) {
                $errors[] = "fuzzy_threshold must be an integer between 0-100";
            }
        }

        return new ValidationResult($errors);
    }
}
```

**Enhanced YAML with Validation:**

```yaml
artist:
  name: "Lettuce"
  collection_id: "Lettuce"          # Validated: alphanumeric + _ or -
  url_key: "lettuce"                # Validated: lowercase + hyphens only

albums:
  - name: "Outta Here"
    url_key: "outtahere"
    tracks:
      - name: "Phyllis"             # Validated: no duplicates in this album
        url_key: "phyllis"
        aliases: ["phillis", "phylis"]
      - name: "Sam Huff"
        url_key: "samhuff"

matching:
  fuzzy_enabled: false              # Default: false (IMPORTANT for performance)
  fuzzy_threshold: 85               # Validated: 0-100 range
  fuzzy_fallback: false             # Try fuzzy only if exact fails
  strip_segue_markers: true         # Remove ">" from titles
```

**Validation Integration:**

```php
// In ArtistConfigLoader
public function load(string $artistName): array
{
    $yamlPath = $this->getYamlPath($artistName);
    $yaml = Yaml::parseFile($yamlPath);

    // Validate before returning
    $validationResult = $this->validator->validate($yaml);
    if (!$validationResult->isValid()) {
        throw new ConfigurationException(
            "Invalid YAML for artist '$artistName': " .
            implode(', ', $validationResult->getErrors())
        );
    }

    return $yaml;
}
```

---

## Admin Dashboard & Import Monitoring

### Overview

A rich admin interface for monitoring, managing, and troubleshooting Archive.org imports.

**Menu Location:** `Content > Archive.org Imports`

### Dashboard Features

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ARCHIVE.ORG IMPORT DASHBOARD                                    [Refresh] │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │     42       │  │    2,847     │  │   186,302    │  │    12.4 GB   │    │
│  │   Artists    │  │    Shows     │  │    Tracks    │  │  JSON Cache  │    │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘    │
│                                                                             │
│  ┌─────────────────────────────────────┐  ┌─────────────────────────────┐  │
│  │  IMPORTS THIS WEEK          📊     │  │  STORAGE BY ARTIST    🥧   │  │
│  │                                     │  │                             │  │
│  │  ████████████░░░░  Mon (847)       │  │  Phish ████████ 34%        │  │
│  │  ███████░░░░░░░░░  Tue (412)       │  │  GD    █████    22%        │  │
│  │  ██████████████░░  Wed (1,203)     │  │  Billy █████    18%        │  │
│  │  █████████░░░░░░░  Thu (623)       │  │  JRAD   ███      12%        │  │
│  │  ░░░░░░░░░░░░░░░░  Fri (0)         │  │  Other  ███      14%        │  │
│  │                                     │  │                             │  │
│  └─────────────────────────────────────┘  └─────────────────────────────┘  │
│                                                                             │
│  RECENT ACTIVITY                                                           │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ✅ 10:42 AM  Populated 362 tracks for GracePotter (completed)             │
│  ✅ 10:35 AM  Downloaded 362 shows for GracePotter (completed)             │
│  ⚠️  09:15 AM  Populated BillyStrings - 12 unmatched tracks                │
│  ✅ 08:30 AM  Downloaded 519 shows for BillyStrings (completed)            │
│  ❌ Yesterday  Download failed for Matisyahu (API timeout)                 │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Admin Grids

#### 1. Artist Status Grid

**Menu:** `Content > Archive.org > Artist Status`

| Artist | Collection ID | Config | Downloaded | Shows | Tracks | Last Download | Last Populate | Actions |
|--------|--------------|--------|------------|-------|--------|---------------|---------------|---------|
| Phish | Phish | ✅ | ✅ | 2,847 | 68,432 | Jan 28, 2026 | Jan 28, 2026 | [Download] [Populate] [View] |
| Billy Strings | BillyStrings | ✅ | ✅ | 519 | 12,456 | Jan 28, 2026 | Jan 28, 2026 | [Download] [Populate] [View] |
| Lettuce | Lettuce | ✅ | ❌ | 0 | 0 | Never | Never | [Download] [Populate] [View] |
| Matisyahu | Matisyahu | ❌ | ❌ | 0 | 0 | Never | Never | [Create Config] |

**Features:**
- Filter by status (configured, downloaded, populated)
- Mass actions (download all, populate all)
- Click artist to see detailed view
- Visual indicators for missing configs

#### 2. Import History Grid

**Menu:** `Content > Archive.org > Import History`

| ID | Date/Time | Artist | Command | Status | Shows | Tracks | Duration | Errors | Details |
|----|-----------|--------|---------|--------|-------|--------|----------|--------|---------|
| 847 | Jan 28, 10:42 | GracePotter | populate | ✅ Success | 362 | 3,420 | 4m 32s | 0 | [View Log] |
| 846 | Jan 28, 10:35 | GracePotter | download | ✅ Success | 362 | - | 6m 12s | 0 | [View Log] |
| 845 | Jan 28, 09:15 | BillyStrings | populate | ⚠️ Partial | 519 | 11,234 | 8m 45s | 12 | [View Log] |
| 844 | Jan 28, 08:30 | BillyStrings | download | ✅ Success | 519 | - | 7m 03s | 0 | [View Log] |
| 843 | Jan 27, 16:22 | Matisyahu | download | ❌ Failed | 0 | - | 0m 45s | 1 | [View Log] |

**Features:**
- Filter by date range, artist, command type, status
- Export to CSV
- Click to view full log output
- Re-run failed imports

#### 3. Unmatched Tracks Grid

**Menu:** `Content > Archive.org > Unmatched Tracks`

| ID | Artist | Show | Track Title | Suggested Match | Confidence | Date Found | Actions |
|----|--------|------|-------------|-----------------|------------|------------|---------|
| 1 | Grateful Dead | gd1977-05-08 | "Free-form Jam" | (none) | - | Jan 28 | [Create Track] [Ignore] |
| 2 | Grateful Dead | gd1978-01-22 | "Estimate Prophet" | "Estimated Prophet" | 94% | Jan 28 | [Map to Match] [Ignore] |
| 3 | Phish | phish2023-07-14 | "Twezer" | "Tweezer" | 97% | Jan 28 | [Map to Match] [Ignore] |

**Features:**
- Fuzzy match suggestions with confidence scores
- One-click "Map to Match" to add alias to YAML
- "Create Track" to add new track category
- Bulk ignore for jam segments, tuning, etc.
- Filter by artist, confidence level
- Export for manual review

#### 4. Download Progress (Real-Time)

**Shows during active download:**

```
┌─────────────────────────────────────────────────────────────────┐
│  DOWNLOADING: Phish                                    [Cancel] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Progress: ████████████████████░░░░░░░░░░  847 / 2,847 (30%)   │
│                                                                 │
│  ⏱️  Elapsed: 4m 32s     📊 Rate: 3.1 shows/sec                 │
│  📁 Current: phish2023-07-14.sbd.miller.flac16                  │
│  💾 Cache size: 847 files (234 MB)                              │
│                                                                 │
│  Recent:                                                        │
│  ✅ phish2023-07-13.sbd.miller.flac16 (24 tracks)              │
│  ✅ phish2023-07-12.sbd.miller.flac16 (18 tracks)              │
│  ✅ phish2023-07-11.sbd.miller.flac16 (21 tracks)              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Database Tables

#### `archivedotorg_import_run`

Logs every command execution.

```sql
CREATE TABLE archivedotorg_import_run (
    run_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL,
    collection_id VARCHAR(255) NOT NULL,
    command VARCHAR(50) NOT NULL,           -- 'download', 'populate', 'setup'
    status VARCHAR(20) NOT NULL,            -- 'running', 'completed', 'failed', 'partial'
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    shows_processed INT DEFAULT 0,
    tracks_created INT DEFAULT 0,
    tracks_updated INT DEFAULT 0,
    tracks_skipped INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    options_json TEXT NULL,                 -- {"incremental": true, "limit": 10}
    log_output LONGTEXT NULL,               -- Full CLI output
    error_message TEXT NULL,
    INDEX idx_artist (artist_name),
    INDEX idx_status (status),
    INDEX idx_started (started_at)
);
```

#### `archivedotorg_artist_status`

Summary per artist (updated after each run).

```sql
CREATE TABLE archivedotorg_artist_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL UNIQUE,
    collection_id VARCHAR(255) NOT NULL,
    has_yaml_config BOOLEAN DEFAULT FALSE,
    total_shows_available INT DEFAULT 0,    -- From Archive.org API
    total_shows_downloaded INT DEFAULT 0,   -- JSON files on disk
    total_tracks_created INT DEFAULT 0,     -- Magento products
    total_unmatched INT DEFAULT 0,
    cache_size_bytes BIGINT DEFAULT 0,      -- Total JSON cache size
    last_download_at DATETIME NULL,
    last_populate_at DATETIME NULL,
    last_full_sync_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_collection (collection_id)
);
```

#### `archivedotorg_unmatched_track`

Tracks that couldn't be matched to categories.

```sql
CREATE TABLE archivedotorg_unmatched_track (
    unmatched_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL,
    show_identifier VARCHAR(255) NOT NULL,
    show_date DATE NULL,
    track_title VARCHAR(500) NOT NULL,
    track_number INT NULL,
    suggested_match VARCHAR(500) NULL,      -- Fuzzy match suggestion
    match_confidence DECIMAL(5,2) NULL,     -- 0.00-100.00
    status VARCHAR(20) DEFAULT 'pending',   -- 'pending', 'mapped', 'ignored', 'created'
    mapped_to_category_id INT NULL,         -- If manually mapped
    run_id INT NULL,                        -- Which import found this
    created_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    INDEX idx_artist (artist_name),
    INDEX idx_status (status),
    INDEX idx_confidence (match_confidence),
    FOREIGN KEY (run_id) REFERENCES archivedotorg_import_run(run_id)
);
```

### Admin Module Files

```
src/app/code/ArchiveDotOrg/Admin/
├── registration.php
├── etc/
│   ├── module.xml
│   ├── adminhtml/
│   │   ├── menu.xml                    # Admin menu entries
│   │   ├── routes.xml                  # Controller routes
│   │   └── di.xml                      # Admin-specific DI
│   └── db_schema.xml                   # Database tables
│
├── Controller/Adminhtml/
│   ├── Dashboard/
│   │   └── Index.php                   # Dashboard page
│   ├── Artist/
│   │   ├── Index.php                   # Artist grid
│   │   ├── View.php                    # Single artist detail
│   │   ├── Download.php                # Trigger download
│   │   └── Populate.php                # Trigger populate
│   ├── History/
│   │   ├── Index.php                   # History grid
│   │   └── View.php                    # Single run detail
│   ├── Unmatched/
│   │   ├── Index.php                   # Unmatched grid
│   │   ├── Map.php                     # Map to existing
│   │   ├── Ignore.php                  # Mark as ignored
│   │   └── MassIgnore.php              # Bulk ignore
│   └── Progress/
│       └── Status.php                  # AJAX endpoint for live progress
│
├── Model/
│   ├── ImportRun.php                   # Import run entity
│   ├── ArtistStatus.php                # Artist status entity
│   ├── UnmatchedTrack.php              # Unmatched track entity
│   ├── ResourceModel/
│   │   ├── ImportRun.php
│   │   ├── ImportRun/Collection.php
│   │   ├── ArtistStatus.php
│   │   ├── ArtistStatus/Collection.php
│   │   ├── UnmatchedTrack.php
│   │   └── UnmatchedTrack/Collection.php
│   └── Dashboard/
│       └── DataProvider.php            # Aggregates dashboard stats
│
├── Ui/
│   └── Component/
│       ├── Listing/
│       │   ├── ArtistDataProvider.php
│       │   ├── HistoryDataProvider.php
│       │   └── UnmatchedDataProvider.php
│       └── Column/
│           ├── Status.php              # Status badges
│           ├── Actions.php             # Row actions
│           └── Duration.php            # Format duration
│
├── view/adminhtml/
│   ├── layout/
│   │   ├── archivedotorg_dashboard_index.xml
│   │   ├── archivedotorg_artist_index.xml
│   │   ├── archivedotorg_history_index.xml
│   │   └── archivedotorg_unmatched_index.xml
│   ├── ui_component/
│   │   ├── archivedotorg_artist_listing.xml
│   │   ├── archivedotorg_history_listing.xml
│   │   └── archivedotorg_unmatched_listing.xml
│   ├── templates/
│   │   ├── dashboard.phtml             # Main dashboard template
│   │   └── progress.phtml              # Live progress widget
│   └── web/
│       ├── js/
│       │   ├── dashboard-charts.js     # Chart.js integration
│       │   └── progress-poller.js      # AJAX progress updates
│       └── css/
│           └── dashboard.css           # Dashboard styles
│
└── Block/Adminhtml/
    ├── Dashboard.php                   # Dashboard block
    └── Progress.php                    # Progress widget block
```

### Chart.js Integration

Using Chart.js (MIT license) for rich visualizations.

**Include via CDN in layout XML:**
```xml
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"/>
</head>
```

**Charts to implement:**
1. **Bar Chart** - Imports per day/week (tracks created)
2. **Pie Chart** - Storage by artist (JSON cache size)
3. **Line Chart** - Import trends over time
4. **Donut Chart** - Match rate (matched vs unmatched)

### CLI Integration

Commands should log to database automatically:

```php
// In DownloadCommand.php execute()
$run = $this->importRunFactory->create();
$run->setArtistName($artistName)
    ->setCollectionId($collectionId)
    ->setCommand('download')
    ->setStatus('running')
    ->setStartedAt(new \DateTime())
    ->setOptionsJson(json_encode($options));
$this->importRunRepository->save($run);

try {
    // ... do download ...
    $run->setStatus('completed')
        ->setShowsProcessed($count)
        ->setCompletedAt(new \DateTime());
} catch (\Exception $e) {
    $run->setStatus('failed')
        ->setErrorMessage($e->getMessage());
}
$this->importRunRepository->save($run);

// Update artist status
$this->artistStatusService->updateAfterDownload($artistName, $stats);
```

### Live Progress via AJAX

**Progress Controller (returns JSON):**
```php
// Controller/Adminhtml/Progress/Status.php
public function execute()
{
    $progressFile = $this->varDir . '/archivedotorg/download_progress.json';
    $progress = json_decode(file_get_contents($progressFile), true);

    return $this->resultJsonFactory->create()->setData([
        'status' => $progress[$artistName]['status'] ?? 'unknown',
        'downloaded' => $progress[$artistName]['downloaded'] ?? 0,
        'total' => $progress[$artistName]['total_recordings'] ?? 0,
        'last_identifier' => $progress[$artistName]['last_identifier'] ?? null,
        'percent' => round(($downloaded / $total) * 100, 1)
    ]);
}
```

**JavaScript Poller:**
```javascript
// progress-poller.js
const pollProgress = (artistName, callback) => {
    const poll = () => {
        fetch(`/admin/archivedotorg/progress/status?artist=${artistName}`)
            .then(r => r.json())
            .then(data => {
                callback(data);
                if (data.status === 'running') {
                    setTimeout(poll, 2000); // Poll every 2 seconds
                }
            });
    };
    poll();
};
```

---

## Enterprise-Grade Technology Stack

Based on comprehensive research of industry best practices (Datadog, Grafana, Stripe, AWS CloudWatch), here's the recommended technology stack:

### Visualization Library: ApexCharts (MIT License)

**Why ApexCharts over alternatives:**
- Beautiful animations out-of-the-box
- Excellent real-time/streaming data support
- 100+ chart types (line, bar, area, pie, radial, heatmap, treemap)
- MIT license (no commercial concerns)
- Works with RequireJS (Magento admin compatible)
- ~60KB bundle size (smaller than ECharts)

**RequireJS Configuration:**
```javascript
// view/adminhtml/requirejs-config.js
var config = {
    paths: {
        'apexcharts': 'ArchiveDotOrg_Admin/js/lib/apexcharts.min'
    }
};
```

### Dashboard Layout: Gridstack.js (MIT License)

- Drag-and-drop widget repositioning
- Resizable panels
- Responsive breakpoints
- Save/restore layout preferences
- ~25KB bundle size

### Data Tables: Magento UI Components + DataTables.js

- Native Magento grids for consistency
- DataTables.js for advanced features (export CSV/Excel/PDF)
- Server-side pagination for large datasets

### Real-Time Progress: AJAX Polling + Redis

**Approach (pragmatic for Magento):**
```
CLI Process                    Admin Dashboard
    │                              │
    ├─── Write to Redis ──────────►│ Poll every 2 seconds
    │    (progress JSON)           │
    ├─── Write to DB ─────────────►│ (on completion)
    │    (final results)           │
    ▼                              ▼
```

**Why not WebSocket/SSE:**
- Magento admin doesn't support persistent connections well
- AJAX polling at 2-second intervals is sufficient for import progress
- Simpler to implement and maintain
- Redis provides fast progress reads without DB load

### Progress Tracking Libraries

- **ProgressBar.js** - Animated linear/circular progress
- **Spin.js** - Lightweight spinners (no images/CSS)

---

## Enhanced Database Schema

### `archivedotorg_import_run` (Audit Trail)

```sql
CREATE TABLE archivedotorg_import_run (
    run_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    correlation_id CHAR(36) NOT NULL,

    -- Command Details
    command_name VARCHAR(100) NOT NULL,           -- 'archive:download', 'archive:populate'
    artist_name VARCHAR(255) NOT NULL,
    collection_id VARCHAR(255) NOT NULL,
    command_args JSON NULL,                       -- {"limit": 10, "incremental": true}

    -- Execution Context
    started_by VARCHAR(100) NOT NULL DEFAULT 'cli',  -- 'cli', 'cron', 'admin:john@example.com'
    started_at TIMESTAMP(3) NOT NULL,
    completed_at TIMESTAMP(3) NULL,

    -- Status
    status ENUM('queued', 'running', 'completed', 'partial', 'failed', 'cancelled') NOT NULL,
    exit_code TINYINT NULL,

    -- Metrics
    total_items INT UNSIGNED NULL,
    items_processed INT UNSIGNED NOT NULL DEFAULT 0,
    items_successful INT UNSIGNED NOT NULL DEFAULT 0,
    items_failed INT UNSIGNED NOT NULL DEFAULT 0,
    items_skipped INT UNSIGNED NOT NULL DEFAULT 0,

    -- Performance
    duration_seconds INT UNSIGNED NULL,
    throughput_per_sec DECIMAL(10,2) NULL,
    avg_item_time_ms INT UNSIGNED NULL,
    memory_peak_mb INT UNSIGNED NULL,

    -- Logs
    log_output LONGTEXT NULL,                     -- Full CLI output (truncated)
    error_message TEXT NULL,
    error_stacktrace TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_artist (artist_name),
    INDEX idx_status (status),
    INDEX idx_started (started_at),
    INDEX idx_correlation (correlation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `archivedotorg_artist_status` (Summary View)

```sql
CREATE TABLE archivedotorg_artist_status (
    status_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL UNIQUE,
    collection_id VARCHAR(255) NOT NULL,

    -- Configuration
    has_yaml_config BOOLEAN DEFAULT FALSE,
    yaml_album_count INT UNSIGNED DEFAULT 0,
    yaml_track_count INT UNSIGNED DEFAULT 0,

    -- Archive.org Totals (from API)
    archive_total_shows INT UNSIGNED DEFAULT 0,
    archive_total_recordings INT UNSIGNED DEFAULT 0,

    -- Downloaded (JSON on disk)
    downloaded_shows INT UNSIGNED DEFAULT 0,
    cache_size_bytes BIGINT UNSIGNED DEFAULT 0,

    -- Imported (Magento products)
    imported_tracks INT UNSIGNED DEFAULT 0,
    matched_tracks INT UNSIGNED DEFAULT 0,
    unmatched_tracks INT UNSIGNED DEFAULT 0,

    -- Quality Metrics
    match_rate_percent DECIMAL(5,2) DEFAULT 0.00,
    artwork_coverage_percent DECIMAL(5,2) DEFAULT 0.00,

    -- Timestamps
    last_download_at TIMESTAMP NULL,
    last_populate_at TIMESTAMP NULL,
    last_full_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_collection (collection_id),
    INDEX idx_match_rate (match_rate_percent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `archivedotorg_unmatched_track` (Quality Tracking)

```sql
CREATE TABLE archivedotorg_unmatched_track (
    unmatched_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Context
    artist_name VARCHAR(255) NOT NULL,
    show_identifier VARCHAR(255) NOT NULL,
    show_date DATE NULL,

    -- Track Details
    track_title VARCHAR(500) NOT NULL,
    track_number INT UNSIGNED NULL,
    track_file VARCHAR(500) NULL,

    -- Matching Suggestions
    suggested_match VARCHAR(500) NULL,            -- Fuzzy match suggestion
    match_confidence DECIMAL(5,2) NULL,           -- 0.00-100.00

    -- Resolution
    status ENUM('pending', 'mapped', 'ignored', 'new_track') DEFAULT 'pending',
    mapped_to_category_id INT UNSIGNED NULL,
    resolution_notes TEXT NULL,

    -- Tracking
    run_id INT UNSIGNED NULL,
    occurrence_count INT UNSIGNED DEFAULT 1,
    first_seen_at TIMESTAMP NOT NULL,
    last_seen_at TIMESTAMP NOT NULL,
    resolved_at TIMESTAMP NULL,
    resolved_by VARCHAR(100) NULL,

    INDEX idx_artist (artist_name),
    INDEX idx_status (status),
    INDEX idx_confidence (match_confidence DESC),
    INDEX idx_run (run_id)
    -- NO FOREIGN KEY: Allows cleanup of old import runs without affecting unmatched track history
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: run_id is nullable and not enforced by FK.
-- This allows deleting old import_run records (retention policy: 90 days)
-- without cascading deletes or updates to thousands of unmatched_track records.
```

### `archivedotorg_daily_metrics` (Time-Series Aggregates)

```sql
CREATE TABLE archivedotorg_daily_metrics (
    metric_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_date DATE NOT NULL,
    artist_name VARCHAR(255) NULL,                -- NULL = all artists

    -- Volume Metrics
    shows_downloaded INT UNSIGNED DEFAULT 0,
    tracks_imported INT UNSIGNED DEFAULT 0,
    tracks_matched INT UNSIGNED DEFAULT 0,
    tracks_failed INT UNSIGNED DEFAULT 0,

    -- Performance Metrics
    avg_throughput_per_sec DECIMAL(10,2) NULL,
    total_processing_time_sec INT UNSIGNED DEFAULT 0,

    -- Quality Metrics
    match_rate_percent DECIMAL(5,2) NULL,
    error_rate_percent DECIMAL(5,2) NULL,

    -- API Metrics
    api_calls_count INT UNSIGNED DEFAULT 0,
    api_avg_latency_ms INT UNSIGNED NULL,
    api_error_count INT UNSIGNED DEFAULT 0,

    UNIQUE KEY idx_date_artist (metric_date, artist_name),
    INDEX idx_date (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Dashboard UI Patterns

### Color System (Enterprise Standard)

| Status | Color | Hex | Use Case |
|--------|-------|-----|----------|
| Success | Green | #1DB954 | ✅ Completed imports, healthy status |
| Processing | Blue | #1E90FF | ⏳ Running jobs, in-progress |
| Warning | Orange | #FFA500 | ⚠️ Partial success, needs review |
| Error | Red | #FF6B6B | ❌ Failed imports, errors |
| Neutral | Gray | #666666 | ⊘ Not started, disabled |

### Status Badges

```html
<!-- Success -->
<span class="badge badge--success">✓ Completed</span>

<!-- Processing (with spinner) -->
<span class="badge badge--processing">
    <span class="spinner"></span> Processing...
</span>

<!-- Warning -->
<span class="badge badge--warning">⚠ 12 Unmatched</span>

<!-- Error -->
<span class="badge badge--error">✗ Failed</span>
```

### Progress Indicators

**Determinate (known total):**
```
Downloading Phish shows...
████████████████░░░░░░░░░░░░░░  847 / 2,847 (30%)
⏱ Elapsed: 4m 32s  |  📊 3.1 shows/sec  |  ETA: ~11 min
```

**Indeterminate (unknown total):**
```
Fetching show metadata...
[Animated spinner]
Processing: phish2023-07-14.sbd.miller.flac16
```

### Estimated Time Remaining Algorithm

```php
// Use exponential moving average for smooth estimates
class EtaCalculator
{
    private float $alpha = 0.3;  // Smoothing factor
    private ?float $smoothedRate = null;

    public function update(int $processed, int $total, float $elapsedSeconds): ?string
    {
        if ($processed === 0 || $elapsedSeconds === 0) {
            return null;
        }

        $currentRate = $processed / $elapsedSeconds;

        // Exponential moving average
        $this->smoothedRate = $this->smoothedRate === null
            ? $currentRate
            : ($this->alpha * $currentRate) + ((1 - $this->alpha) * $this->smoothedRate);

        $remaining = $total - $processed;
        $etaSeconds = $remaining / $this->smoothedRate;

        return $this->formatDuration($etaSeconds);
    }
}
```

### Empty States

```
┌─────────────────────────────────────────────────┐
│                                                 │
│           📭 No Import History Yet              │
│                                                 │
│     Run your first import to see results        │
│         and track progress here.                │
│                                                 │
│         [ Start First Import ]                  │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## Metrics & KPIs to Track

### Throughput Metrics

| Metric | Formula | Visualization | Alert Threshold |
|--------|---------|---------------|-----------------|
| Shows/Hour | Shows ÷ Hours | Gauge + trend | < 2 shows/hr |
| Tracks/Minute | Tracks ÷ Minutes | Line chart | < 50 tracks/min |
| Avg Time/Show | Total time ÷ Shows | Histogram P50/P95 | > 30s/show |

### Quality Metrics

| Metric | Formula | Visualization | Alert Threshold |
|--------|---------|---------------|-----------------|
| Match Rate | Matched ÷ Total × 100% | Gauge | < 85% |
| Error Rate | Errors ÷ Total × 100% | Line chart | > 5% |
| Artwork Coverage | With artwork ÷ Total × 100% | Progress bar | < 70% |

### Coverage Metrics

| Metric | Formula | Visualization | Alert Threshold |
|--------|---------|---------------|-----------------|
| Archive Coverage | Downloaded ÷ Available × 100% | Progress bar | < 95% goal |
| Artist Coverage | Configured ÷ Target × 100% | Gauge | N/A |

### Trend Metrics (Week-over-Week)

```sql
-- WoW Growth calculation
SELECT
    this_week.shows_downloaded as current,
    last_week.shows_downloaded as previous,
    ROUND(((this_week.shows_downloaded - last_week.shows_downloaded)
           / last_week.shows_downloaded) * 100, 1) as wow_growth_percent
FROM (
    SELECT SUM(shows_downloaded) as shows_downloaded
    FROM archivedotorg_daily_metrics
    WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
) this_week,
(
    SELECT SUM(shows_downloaded) as shows_downloaded
    FROM archivedotorg_daily_metrics
    WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
      AND metric_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
) last_week;
```

---

## Structured Logging (Monolog Integration)

### Log Format (JSON)

```json
{
  "timestamp": "2026-01-28T14:32:15.456Z",
  "level": "INFO",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "category": "import:populate",
  "action": "track_matched",
  "message": "Matched track 'Tweezer' to category",
  "context": {
    "artist": "Phish",
    "show_id": "phish2023-07-14",
    "track_title": "Tweezer",
    "category_id": 1234,
    "confidence": 100
  },
  "metrics": {
    "duration_ms": 45,
    "memory_mb": 128
  }
}
```

### CLI Command Logging Pattern

```php
abstract class BaseLoggedCommand extends Command
{
    protected string $correlationId;
    protected ImportRun $importRun;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->correlationId = Uuid::uuid4()->toString();
        $this->importRun = $this->startImportRun($input);

        try {
            $result = $this->doExecute($input, $output);
            $this->completeImportRun('completed', $result);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->completeImportRun('failed', null, $e);
            throw $e;
        }
    }

    protected function logProgress(string $message, array $context = []): void
    {
        $this->logger->info($message, array_merge($context, [
            'correlation_id' => $this->correlationId,
            'run_id' => $this->importRun->getRunId(),
        ]));

        // Also update Redis for live dashboard
        $this->updateRedisProgress();
    }

    // Child classes must implement
    abstract protected function doExecute(InputInterface $input, OutputInterface $output): array;
}
```

### Commands Using BaseLoggedCommand

| Command | Extends BaseLoggedCommand? | Reason |
|---------|---------------------------|--------|
| `archive:setup` | ✅ Yes | Creates categories (needs audit trail) |
| `archive:download` | ✅ Yes | Downloads metadata (main import operation) |
| `archive:populate` | ✅ Yes | Creates products (main import operation) |
| `archive:status` | ❌ No | Read-only query (no audit needed) |
| `archive:cleanup:products` | ✅ Yes | Destructive operation (needs logging) |
| `archive:refresh:products` | ✅ Yes | Updates products (needs audit trail) |
| `archive:migrate:export` | ❌ No | One-time migration (manual operation) |
| `archive:migrate:organize-folders` | ❌ No | One-time migration (manual operation) |
| `archive:sync:albums` | ✅ Yes | Modifies categories (needs logging) |
| `archive:download:album-art` | ❌ No | Asset operation (optional logging) |

**Rule of Thumb:**
- **Use BaseLoggedCommand** for commands that modify data or run long operations
- **Use plain Command** for read-only queries and one-time migrations

---

## ApexCharts Dashboard Examples

### Imports Per Day (Bar Chart)

```javascript
define(['jquery', 'apexcharts'], function($, ApexCharts) {
    return function(config) {
        var options = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true }
            },
            series: [{
                name: 'Tracks Imported',
                data: config.data
            }],
            xaxis: {
                categories: config.dates,
                labels: { rotate: -45 }
            },
            colors: ['#1DB954'],
            dataLabels: { enabled: false },
            tooltip: {
                y: { formatter: (val) => val.toLocaleString() + ' tracks' }
            }
        };

        var chart = new ApexCharts(document.querySelector('#imports-chart'), options);
        chart.render();
    };
});
```

### Match Rate Gauge

```javascript
var options = {
    chart: { type: 'radialBar', height: 250 },
    series: [87.5],  // Match rate percentage
    plotOptions: {
        radialBar: {
            hollow: { size: '70%' },
            dataLabels: {
                name: { show: true, fontSize: '16px' },
                value: {
                    show: true,
                    fontSize: '24px',
                    formatter: (val) => val + '%'
                }
            }
        }
    },
    labels: ['Match Rate'],
    colors: ['#1DB954']
};
```

### Real-Time Progress Polling

```javascript
define(['jquery'], function($) {
    return function(config) {
        var pollInterval = null;

        function pollProgress() {
            $.ajax({
                url: config.progressUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    updateProgressUI(data);

                    if (data.status === 'completed' || data.status === 'failed') {
                        stopPolling();
                        showCompletionMessage(data);
                    }
                }
            });
        }

        function startPolling() {
            pollProgress();  // Immediate first call
            pollInterval = setInterval(pollProgress, 2000);  // Then every 2s
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        function updateProgressUI(data) {
            var percent = (data.processed / data.total) * 100;
            $('.progress-bar').css('width', percent + '%');
            $('.progress-text').text(data.processed + ' / ' + data.total);
            $('.progress-eta').text('ETA: ' + data.eta);
            $('.current-item').text(data.current_item);
        }

        return { start: startPolling, stop: stopPolling };
    };
});
```

---

## Implementation Priority (Revised)

### Phase 0: Foundation (Week 1-2)
| Task | Effort | Priority |
|------|--------|----------|
| Create database tables (4 tables) | 1 day | P0 |
| Create PHP models + repositories | 1.5 days | P0 |
| Add correlation ID to existing commands | 0.5 day | P0 |
| CLI logging to `import_run` table | 1.5 days | P0 |
| YAML validation schema | 1 day | P0 |
| Redis progress tracker | 1 day | P0 |
| **Testing & debugging** | 1 day | P0 |

### Phase 1: Core Dashboard (Week 3-4)
| Task | Effort | Priority |
|------|--------|----------|
| Admin menu + dashboard controller | 0.5 day | P1 |
| Dashboard layout (4 stat cards) | 1.5 days | P1 |
| Artist Status grid (UI Component) | 1.5 days | P1 |
| Import History grid (UI Component) | 1.5 days | P1 |
| **RequireJS/Magento integration issues** | 1 day | P1 |
| **Testing & QA** | 1 day | P1 |

### Phase 2: Visualizations (Week 5)
| Task | Effort | Priority |
|------|--------|----------|
| ApexCharts integration + RequireJS config | 1 day | P2 |
| Imports per day bar chart | 0.5 day | P2 |
| Match rate gauge | 0.5 day | P2 |
| Real-time progress polling | 1.5 days | P2 |
| Redis progress tracking integration | 1 day | P2 |
| **Cross-browser testing** | 0.5 day | P2 |

### Phase 3: Quality Tools (Week 6)
| Task | Effort | Priority |
|------|--------|----------|
| Unmatched Tracks grid | 1.5 days | P3 |
| One-click "Map to Match" + YAML update | 1.5 days | P3 |
| Bulk ignore for jam segments | 0.5 day | P3 |
| Export unmatched to CSV | 0.5 day | P3 |
| Daily metrics aggregation cron | 1 day | P3 |

### Phase 4: Polish & Testing (Week 7-8)
| Task | Effort | Priority |
|------|--------|----------|
| WoW comparison widgets | 1 day | P4 |
| Activity feed timeline | 1.5 days | P4 |
| Admin-triggered download/populate | 1.5 days | P4 |
| Gridstack draggable layout (optional) | 1.5 days | P4 |
| Mobile responsive tweaks | 1 day | P4 |
| **End-to-end testing** | 2 days | P4 |
| **Performance optimization** | 1 day | P4 |
| **Documentation** | 1 day | P4 |

**Total: ~7-8 weeks for production-ready enterprise dashboard**

**Realistic buffer added for:**
- RequireJS/KnockoutJS/Magento UI Component integration challenges
- ApexCharts compatibility debugging
- Cross-browser testing (Safari, Firefox, Chrome)
- Performance tuning for large datasets (10k+ products)
- QA and bug fixes

---

## Plan Revisions (2026-01-28)

### Critical Fixes Applied

1. ✅ **Removed "RecordingImporter" rename** - Keeping `TrackPopulatorService` for consistency with existing terminology
2. ✅ **Added Command Migration Path** - Clear deprecation strategy for old commands (`download:metadata` → `download`)
3. ✅ **Added Redis Schema** - Key patterns, TTL strategy, cleanup mechanism documented
4. ✅ **Fixed Database FK Constraint** - Removed foreign key on `unmatched_track.run_id` to allow cleanup of old import runs
5. ✅ **Clarified Parsing Strategy** - JSON parsing stays in `TrackPopulatorService` (no separate MetadataParser)
6. ✅ **Added BaseLoggedCommand Specification** - Table showing which commands use auto-logging
7. ✅ **Added Resume Logic Section** - Crash recovery with `--resume` flag and progress file tracking
8. ✅ **Moved YAML Location** - From `var/` to `app/code/ArchiveDotOrg/Core/config/artists/` (version controlled)
9. ✅ **Added YAML Validation Schema** - `ArtistConfigValidator` with format rules and duplicate detection
10. ✅ **Updated Timeline to 7-8 weeks** - More realistic with testing, integration, and QA buffers

### Architecture Improvements

- **Progress Tracking**: Now uses Redis (real-time) + JSON files (persistence) + Database (audit trail)
- **Error Recovery**: `--resume` flag uses progress file to skip completed downloads
- **Validation Layer**: YAML configs validated before use (prevents runtime errors)
- **Command Organization**: Clear base class pattern with correlation IDs for all logged operations
- **Data Retention**: No FK constraints blocking cleanup of old audit records

### Ready for Implementation

All critical issues resolved. Plan is now production-ready with:
- ✅ Clear terminology (Track, Show, Version)
- ✅ Migration path for existing commands
- ✅ Comprehensive progress tracking (Redis + JSON + DB)
- ✅ Validation at all layers (YAML, DB, Redis)
- ✅ Realistic timeline with testing buffers

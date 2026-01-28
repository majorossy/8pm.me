# Implementation Plan: Populate Track Categories with Archive.org Audio

## Overview

Populate ~3,000 track categories with audio products from Archive.org live recordings. Each track category (e.g., "Scarlet Begonias" under "Grateful Dead") will get **every available recording** - one product per Archive.org show that played that song.

**Example:**
```
Grateful Dead (artist)
└── Cornell 5/8/77 (album category)
    └── Scarlet Begonias (track category)
        ├── Product: Scarlet Begonias - 1977-05-08 Cornell
        ├── Product: Scarlet Begonias - 1977-05-09 Buffalo
        ├── Product: Scarlet Begonias - 1989-10-09 Hampton
        └── ... (every show that played this song)
```

---

## ⚠️ API Rate Limiting Strategy

### The Problem

Fetching track data requires calling `/metadata/{identifier}` for **every show** in a collection. But each show often has **multiple recordings** (different tapers, SBD vs AUD, various formats). Calling for every recording = massive API waste.

### The Solution: Deduplicate by Show Date + Best Version Selection

Instead of fetching all ~360,000 recordings, we:
1. **Group by show date** - Most shows have 2-10 recordings
2. **Pick the BEST version** per date (see algorithm below)
3. **Fetch metadata only for winners** - Reduces to ~35,000 calls (90% reduction!)

| Artist | All Items | Unique Dates | API Calls (After Dedup) |
|--------|-----------|--------------|-------------------------|
| Billy Strings | 924 | 475 | 475 |
| Goose | 1,319 | 486 | 486 |
| STS9 | 757 | 552 | 552 |
| Furthur | 1,230 | 254 | 254 |
| JRAD | 1,135 | 358 | 358 |
| Tedeschi Trucks Band | 1,496 | 774 | 774 |
| Railroad Earth | 2,451 | 1,275 | 1,275 |
| YMSB | 2,143 | 1,378 | 1,378 |
| moe. | 5,791 | 2,239 | 2,239 |
| Widespread Panic | 4,707 | 2,715 | 2,715 |
| Keller Williams | 14,679 | 3,053 | 3,053 |
| Umphrey's McGee | 19,718 | 4,870 | 4,870 |
| Disco Biscuits | 27,325 | ~2,000 | ~2,000 |
| String Cheese Incident | 36,325 | ~2,500 | ~2,500 |
| Leftover Salmon | 43,991 | ~2,000 | ~2,000 |
| Grateful Dead | 191,982 | 7,090 | 7,090 |
| **Total** | **~360,000** | **~35,000** | **~35,000** |

Aggressive API calls **will get us banned** (429 Too Many Requests).

---

## Best Version Selection

For each show date with multiple recordings, we pick the **BEST** one using a quality ranking algorithm.

### Ranking Algorithm (Priority Order)

1. **SBD Preferred** - Identifier contains "sbd" (soundboard = highest quality)
2. **Highest Rating** - `avg_rating` field (1-5 stars from Archive.org users)
3. **Most Reviews** - `num_reviews` field (more reviews = more trusted)
4. **Most Downloads** - `downloads` field (popularity as fallback)

### Why This Works

The **search API returns quality fields** without extra metadata calls:
- `identifier` - Check for "sbd" substring
- `avg_rating` - User rating average
- `num_reviews` - Number of reviews
- `downloads` - Total download count

### Example: Grateful Dead 1977-10-01

For show date `1977-10-01` with 5 recordings:

| Identifier | SBD? | Rating | Reviews | Downloads | Pick? |
|------------|------|--------|---------|-----------|-------|
| `gd1977-10-01.sbd.cantor.flac16` | ✓ | 4.5 | 3 | 10,150 | **✓ BEST** |
| `gd1977-10-01.aud.miller.flac16` | ✗ | 4.8 | 5 | 5,200 | |
| `gd1977-10-01.aud.unknown.mp3` | ✗ | 3.2 | 1 | 1,100 | |
| `gd1977-10-01.sbd.smith.flac16` | ✓ | 4.2 | 2 | 2,300 | |
| `gd1977-10-01.mtx.matrix.flac16` | ✗ | n/a | 0 | 800 | |

**Winner:** `gd1977-10-01.sbd.cantor.flac16` - SBD recordings get priority, then highest rating breaks the tie.

---

## Progress Tracking & Resumability

Downloads can fail mid-way (network issues, rate limits, system crashes). The system tracks progress so you can resume without re-downloading.

### Progress File Structure

**File:** `var/archivedotorg/download_progress.json`

```json
{
  "GratefulDead": {
    "started_at": "2026-01-27T10:00:00Z",
    "last_updated": "2026-01-27T12:30:00Z",
    "total_recordings": 191982,
    "unique_shows": 7090,
    "downloaded": 3500,
    "failed": 2,
    "failed_identifiers": ["gd1985-03-15.broken.flac16", "gd1990-07-04.corrupt"],
    "last_identifier": "gd1977-05-08.sbd.miller.flac16",
    "status": "in_progress"
  },
  "Goose": {
    "started_at": "2026-01-26T08:00:00Z",
    "completed_at": "2026-01-26T08:04:00Z",
    "total_recordings": 1319,
    "unique_shows": 486,
    "downloaded": 486,
    "failed": 0,
    "status": "completed"
  }
}
```

### How Resume Works

```
1. Check progress file for collection status
2. If status = "completed" and not --force → skip (already done)
3. If status = "in_progress" → load list of already-downloaded identifiers
4. Compare against full identifier list → download only missing ones
5. Update progress after EACH successful download (crash-safe)
6. On completion, set status = "completed"
```

### CLI Resume Behavior

```bash
# First run - downloads all 7,090 unique shows
bin/magento archive:download:metadata --collection=GratefulDead
# Output: Downloading 7,090 shows...

# Crashes at show 3,500...

# Resume - automatically continues from 3,501
bin/magento archive:download:metadata --collection=GratefulDead
# Output: Resuming from show 3,501/7,090 (3,500 already cached)

# Force full re-download (ignores progress)
bin/magento archive:download:metadata --collection=GratefulDead --force

# Retry only failed downloads
bin/magento archive:download:metadata --collection=GratefulDead --retry-failed
```

### Cache File Detection

Even without progress file, we detect existing cache files:

```php
public function getDownloadedIdentifiers(string $collectionId): array
{
    $pattern = $this->getIdentifierPattern($collectionId); // e.g., "gd*"
    $files = glob($this->cacheDir . '/' . $pattern . '.json');

    return array_map(fn($f) => basename($f, '.json'), $files);
}
```

This means if you delete `download_progress.json`, the system still knows what's cached.

---

## Incremental Updates (Weekly Runs)

New shows get added to Archive.org regularly. Weekly updates should only fetch **new** shows, not re-download everything.

### How It Works

Archive.org search API supports date filtering:
- `publicdate:[2026-01-20 TO *]` - Items added since Jan 20, 2026
- `addeddate:[2026-01-20 TO *]` - Alternative date field

### Incremental Mode

```bash
# Full initial download (first time)
bin/magento archive:download:metadata --collection=GratefulDead

# Weekly update - only shows added since last run
bin/magento archive:download:metadata --collection=GratefulDead --incremental

# Or specify explicit date
bin/magento archive:download:metadata --collection=GratefulDead --since=2026-01-20
```

### Progress File Tracks Last Run

```json
{
  "GratefulDead": {
    "status": "completed",
    "completed_at": "2026-01-27T14:00:00Z",
    "last_full_sync": "2026-01-27T14:00:00Z",
    "last_incremental": "2026-02-03T08:00:00Z",
    "unique_shows": 7095
  }
}
```

### Incremental Algorithm

```
1. Read last_full_sync or last_incremental date from progress
2. Query Archive.org: collection:{id} AND publicdate:[{last_date} TO *]
3. Apply best version selection to new results
4. Download metadata only for NEW winners
5. If a new recording beats an existing one for same date:
   - Download new metadata
   - Update cache (replace old file)
   - Mark for re-population
6. Update last_incremental timestamp
```

### Weekly Cron Job

```bash
# Add to crontab - runs every Sunday at 2am
0 2 * * 0 cd /path/to/magento && bin/magento archive:download:metadata --all --incremental >> var/log/archive_sync.log 2>&1
0 3 * * 0 cd /path/to/magento && bin/magento archive:populate:tracks --all --incremental >> var/log/archive_sync.log 2>&1
```

### `--all` Flag for Batch Operations

```bash
# Download ALL collections (uses mapping from config)
bin/magento archive:download:metadata --all

# Incremental update for ALL collections
bin/magento archive:download:metadata --all --incremental

# Populate ALL artists from cache
bin/magento archive:populate:tracks --all
```

### Handling "Better Version" Replacements

Sometimes a new SBD recording uploads for a date that only had AUD before.

```
Existing cache: gd1977-05-08.aud.smith.flac16 (rating: 4.2)
New upload:     gd1977-05-08.sbd.cantor.flac16 (rating: 4.8, SBD!)

→ New SBD wins, replace cache file
→ Flag show for product update (or just re-run populate)
```

The populate command handles this via SKU:
- Different identifier = different SKU = new product created
- Old product remains (historical record) OR can add `--replace-existing` flag

---

### The Solution: Two-Phase Approach

**Phase 1: Download metadata to local cache (run overnight, can resume)**
```bash
bin/magento archive:download:metadata --collection=GratefulDead
```

**Phase 2: Populate tracks from local cache (zero API calls)**
```bash
bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead
```

### Rate Limiting Parameters

| Parameter | Value | Rationale |
|-----------|-------|-----------|
| Delay between requests | 500ms | Very conservative, ~2 req/sec |
| Batch size | 50 | Process in small chunks |
| Backoff on 429 | 60 seconds | Respect rate limits |
| Max retries | 3 | Don't hammer on failure |

### Estimated Download Times (at 500ms/request for ~35,000 unique shows)

| Order | Collection | Unique Shows | Time | Cumulative |
|-------|------------|--------------|------|------------|
| 1 | Furthur | ~254 | ~2 min | 2 min |
| 2 | JRAD | ~358 | ~3 min | 5 min |
| 3 | Billy Strings | ~475 | ~4 min | 9 min |
| 4 | Goose | ~486 | ~4 min | 13 min |
| 5 | STS9 | ~552 | ~5 min | 18 min |
| 6 | Tedeschi Trucks | ~774 | ~6 min | 24 min |
| 7 | Railroad Earth | ~1,275 | ~11 min | 35 min |
| 8 | YMSB | ~1,378 | ~12 min | 47 min |
| 9 | Disco Biscuits | ~2,000 | ~17 min | 1h 4m |
| 10 | Leftover Salmon | ~2,000 | ~17 min | 1h 21m |
| 11 | moe. | ~2,239 | ~19 min | 1h 40m |
| 12 | String Cheese | ~2,500 | ~21 min | 2h 1m |
| 13 | Widespread Panic | ~2,715 | ~23 min | 2h 24m |
| 14 | Keller Williams | ~3,053 | ~25 min | 2h 49m |
| 15 | Umphrey's McGee | ~4,870 | ~41 min | 3h 30m |
| 16 | Grateful Dead | ~7,090 | ~59 min | **~4h 30m** |

**Total: ~35,000 unique shows = ~4.5 hours** (can run overnight, progress saved)

**Strategy: Start small, validate, scale up.**

Each collection is independent - run one, verify it works, then continue. Progress is saved and resumable.

---

## Existing Infrastructure (Fully Reusable)

| Component | What It Does | We Reuse |
|-----------|--------------|----------|
| `ArchiveApiClient` | Fetches shows/metadata from Archive.org | ✅ As-is |
| `ConcurrentApiClient` | Batch API fetching with rate limiting | ✅ As-is |
| `TrackImporter` | Creates products with `song_url` | ✅ As-is |
| `CategoryAssignmentService` | Links products to categories | ✅ Extend slightly |
| `Config` | Builds streaming URLs, rate limits | ✅ As-is |

**New files needed:**
1. `MetadataDownloader` - Downloads & caches show metadata locally, includes best version selection (Phase 1)
2. `DownloadMetadataCommand` - CLI for Phase 1 download
3. `TrackPopulatorService` - Matches imported tracks to existing track categories (Phase 2)
4. `PopulateTracksCommand` - CLI for Phase 2 population

## Current State

- 37 artists, 285 albums, 3,014 track categories (with `is_song=1`)
- No products yet - products contain the `song_url` for playback
- Existing import command (`archive:import:shows`) creates products but assigns to show-date categories, not our track categories

## Implementation

### Phase 1: Metadata Download

#### New File 1: MetadataDownloader

**File:** `src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php`

Downloads show metadata to local JSON files with conservative rate limiting.

```php
class MetadataDownloader
{
    private const CACHE_DIR = 'var/archivedotorg/metadata';
    private const PROGRESS_FILE = 'var/archivedotorg/download_progress.json';
    private const DELAY_MS = 500;  // 500ms between requests
    private const BACKOFF_SEC = 60; // 60 second backoff on 429

    public function download(
        string $collectionId,
        ?int $limit = null,
        ?callable $progressCallback = null
    ): array;

    public function getProgress(string $collectionId): ?array;
    public function getCachedMetadata(string $identifier): ?array;
    public function isCached(string $identifier): bool;

    /**
     * Select best recording per show date from search results.
     * Reduces ~360,000 recordings → ~35,000 unique shows.
     *
     * @param array $searchResults Raw results from Archive.org search API
     * @return array List of winning identifiers (one per show date)
     */
    public function selectBestRecordings(array $searchResults): array
    {
        // Group by date (YYYY-MM-DD)
        $byDate = [];
        foreach ($searchResults as $item) {
            $date = substr($item['date'] ?? '', 0, 10);
            if ($date) {
                $byDate[$date][] = $item;
            }
        }

        // Pick best per date
        $winners = [];
        foreach ($byDate as $date => $recordings) {
            usort($recordings, [$this, 'compareRecordingQuality']);
            $winners[] = $recordings[0]['identifier'];
        }

        return $winners;
    }

    /**
     * Compare two recordings by quality.
     * Sort order: SBD first, then rating, then reviews, then downloads.
     */
    private function compareRecordingQuality(array $a, array $b): int
    {
        // SBD recordings win (soundboard = highest quality)
        $aIsSbd = stripos($a['identifier'], 'sbd') !== false;
        $bIsSbd = stripos($b['identifier'], 'sbd') !== false;
        if ($aIsSbd !== $bIsSbd) {
            return $bIsSbd <=> $aIsSbd; // true (1) > false (0)
        }

        // Then by average rating (higher is better)
        $aRating = (float)($a['avg_rating'] ?? 0);
        $bRating = (float)($b['avg_rating'] ?? 0);
        if ($aRating != $bRating) {
            return $bRating <=> $aRating;
        }

        // Then by number of reviews (more = more trusted)
        $aReviews = (int)($a['num_reviews'] ?? 0);
        $bReviews = (int)($b['num_reviews'] ?? 0);
        if ($aReviews != $bReviews) {
            return $bReviews <=> $aReviews;
        }

        // Finally by downloads (popularity fallback)
        return (int)($b['downloads'] ?? 0) <=> (int)($a['downloads'] ?? 0);
    }

    /**
     * Get identifiers already downloaded (from cache files).
     * Used for resume and to avoid re-downloading.
     */
    public function getDownloadedIdentifiers(string $collectionId): array
    {
        $pattern = $this->getIdentifierPattern($collectionId);
        $files = glob(self::CACHE_DIR . '/' . $pattern . '.json');

        return array_map(fn($f) => basename($f, '.json'), $files);
    }

    /**
     * Get progress for a collection.
     */
    public function getProgress(string $collectionId): array
    {
        $progress = $this->loadProgressFile();
        return $progress[$collectionId] ?? [
            'status' => 'not_started',
            'downloaded' => 0,
            'failed' => 0
        ];
    }

    /**
     * Check if collection needs download (not completed or has new items).
     */
    public function needsDownload(string $collectionId, bool $incremental = false): bool
    {
        $progress = $this->getProgress($collectionId);

        if ($progress['status'] === 'not_started') {
            return true;
        }

        if ($progress['status'] === 'in_progress') {
            return true; // Resume incomplete download
        }

        if ($incremental && $progress['status'] === 'completed') {
            return true; // Check for new items
        }

        return false;
    }

    /**
     * Fetch only items added since a given date (for incremental updates).
     */
    public function fetchNewItems(string $collectionId, \DateTimeInterface $since): array
    {
        $sinceStr = $since->format('Y-m-d');
        $query = "collection:{$collectionId} AND publicdate:[{$sinceStr} TO *]";

        return $this->archiveApiClient->search($query, [
            'fl' => 'identifier,date,avg_rating,num_reviews,downloads',
            'rows' => 10000
        ]);
    }

    /**
     * Update progress file after each download (crash-safe).
     */
    private function updateProgress(string $collectionId, array $data): void
    {
        $progress = $this->loadProgressFile();
        $progress[$collectionId] = array_merge(
            $progress[$collectionId] ?? [],
            $data,
            ['last_updated' => (new \DateTime())->format('c')]
        );
        $this->saveProgressFile($progress);
    }
}
```

**Features:**
- Saves to `var/archivedotorg/metadata/{identifier}.json`
- Tracks progress in `var/archivedotorg/download_progress.json`
- Resumes from last position on restart
- 500ms delay between requests (configurable)
- 60-second backoff on 429 errors
- Logs progress every 100 shows

#### New File 2: DownloadMetadataCommand

**File:** `src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php`

```bash
# Download all Grateful Dead metadata (resumable)
bin/magento archive:download:metadata --collection=GratefulDead

# Download with limit (for testing)
bin/magento archive:download:metadata --collection=BillyStrings --limit=100

# Check download progress
bin/magento archive:download:metadata --collection=GratefulDead --status

# Force re-download (ignore cache)
bin/magento archive:download:metadata --collection=GratefulDead --force

# Resume failed downloads only
bin/magento archive:download:metadata --collection=GratefulDead --retry-failed

# Incremental update (only new shows since last run)
bin/magento archive:download:metadata --collection=GratefulDead --incremental

# Incremental from specific date
bin/magento archive:download:metadata --collection=GratefulDead --since=2026-01-20

# Download ALL collections at once
bin/magento archive:download:metadata --all

# Incremental update for ALL collections (weekly cron)
bin/magento archive:download:metadata --all --incremental
```

**CLI Options:**

| Option | Description |
|--------|-------------|
| `--collection=X` | Single collection to download |
| `--all` | Process all configured collections |
| `--limit=N` | Download only N shows (testing) |
| `--status` | Show progress without downloading |
| `--force` | Re-download everything (ignore cache) |
| `--retry-failed` | Only retry previously failed downloads |
| `--incremental` | Only fetch shows added since last run |
| `--since=YYYY-MM-DD` | Only fetch shows added since date |

**Output (Fresh Download):**
```
Downloading metadata for collection: GratefulDead
Fetching show list from Archive.org...
Found 191,982 total recordings → 7,090 unique shows (best version per date)
Already cached: 0 | To download: 7,090

[=====>                    ] 2,500/7,090 (35%) - gd1977-05-08.sbd.miller...
Rate: 2.0 req/sec | ETA: 38 min

✓ Download complete: 7,090 shows cached
  Location: var/archivedotorg/metadata/
  Cache size: 709 MB
```

**Output (Resume After Crash):**
```
Downloading metadata for collection: GratefulDead
Resuming interrupted download...
Found 7,090 unique shows | Already cached: 3,500 | Remaining: 3,590

[============>             ] 3,600/7,090 (51%) - gd1985-07-13.sbd.smith...
Rate: 2.0 req/sec | ETA: 30 min
```

**Output (Incremental Weekly Update):**
```
Incremental update for collection: GratefulDead
Last sync: 2026-01-20 | Checking for new shows...
Found 12 new recordings → 8 new unique shows

[=========================] 8/8 (100%) - gd2026-01-25.sbd.jones...

✓ Incremental complete: 8 new shows added
  Total cached: 7,098 shows
```

---

### Phase 2: Track Population

#### New File 3: TrackPopulatorService

**File:** `src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php`

Uses **local metadata cache** (from Phase 1) to avoid API calls:
1. Load track categories for an artist (by `is_song=1`)
2. Build title lookup map (normalized title → category ID)
3. Read show metadata from local cache (zero API calls!)
4. For each track, if title matches a category → create product & assign
5. SKU (based on SHA1) prevents duplicate products from same recording

```php
class TrackPopulatorService
{
    public function __construct(
        MetadataDownloader $metadataDownloader,     // NEW - reads local cache
        TrackImporterInterface $trackImporter,      // Existing
        CategoryAssignmentServiceInterface $categoryService,  // Existing
        CategoryCollectionFactory $categoryCollectionFactory,
        Logger $logger
    );

    public function populate(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array;
}
```

#### New File 4: PopulateTracksCommand

**File:** `src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateTracksCommand.php`

```bash
# Populate from local cache (fast, no API calls)
bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead

# Dry run - see what would be created
bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead --dry-run

# Limit for testing
bin/magento archive:populate:tracks "Billy Strings" --collection=BillyStrings --limit=100
```

**Output:**
```
Populating tracks for: Grateful Dead
Reading from local cache: var/archivedotorg/metadata/
Found 15,234 cached shows | 847 track categories

[====================>     ] 12,000/15,234 (79%)
Matched: 89,432 | Created: 89,432 | Skipped (dup): 0 | Unmatched: 45,678

✓ Population complete
  Products created: 89,432
  Track categories populated: 712/847
  Tracks not in catalog: 45,678 (these songs aren't in our category list)
```

### Minor Edit: di.xml

Add DI preferences for new interfaces.

### Minor Edit: CategoryAssignmentService

Add one helper method:
- `getTrackCategoriesForArtist(artistName)` - Returns all `is_song=1` categories under the artist

Note: `bulkAssignToCategory()` already exists for product assignment.

---

## Algorithm

### Phase 1: Download Metadata (run once per collection, resumable)

#### Full Download Mode (First Run)

```
1. Load progress file for collection
   - If status = "completed" and not --force → exit (already done)
   - If status = "in_progress" → resume mode

2. Fetch ALL identifiers + quality fields via search API (~36 paginated calls)
   - Fields: identifier, date, avg_rating, num_reviews, downloads

3. Group recordings by show date locally

4. For each date, RANK recordings and pick best:
   a. SBD recordings first (identifier contains "sbd")
   b. Then by avg_rating DESC
   c. Then by num_reviews DESC
   d. Then by downloads DESC

5. Get list of already-cached identifiers (from cache dir)
   - Filter out already-downloaded from winners list

6. For each winning identifier NOT in cache:
   a. Fetch full metadata from Archive.org API
   b. Save JSON to var/archivedotorg/metadata/{identifier}.json
   c. Update progress file (crash-safe checkpoint)
   d. Sleep 500ms (rate limiting)
   e. On 429 → sleep 60 seconds, retry up to 3x
   f. On permanent failure → log to failed_identifiers, continue

7. Update progress: status = "completed", last_full_sync = now
8. Return stats: {total_recordings, unique_shows, cached, downloaded, failed}
```

#### Incremental Mode (Weekly Updates)

```
1. Load progress file → get last_full_sync or last_incremental date
   - If no previous sync → fall back to full download

2. Query Archive.org with date filter:
   collection:{id} AND publicdate:[{last_sync_date} TO *]

3. Apply best version selection to new results

4. For each new show date:
   a. Check if date already has cached recording
   b. If new recording is BETTER (SBD beats AUD, higher rating):
      - Download new metadata
      - Replace old cache file
      - Log as "upgraded"
   c. If date has no cached recording:
      - Download metadata
      - Log as "new"

5. Update progress: last_incremental = now
6. Return stats: {new_shows, upgraded_shows, unchanged}
```

**Result:** ~360,000 recordings → ~35,000 unique shows (90% reduction in API calls)

Weekly incremental updates typically fetch <100 new shows per collection.

### Phase 2: Populate Tracks (zero API calls)

```
1. Get artist category (is_artist=1, name matches)
2. Get all track categories (is_song=1 descendants)
3. Build map: normalize(category_name) => [category_ids]
   (array because same song name may appear under different albums)

4. For each cached show metadata (reads local JSON files):
   a. Parse tracks from files array
   b. For each track:
      - Normalize track title
      - If matches any track category:
        - Create product (TrackImporter) - SKU prevents duplicates
        - Assign to ALL matching track categories
        - Track stats (matched/skipped)
      - If no match, skip (track not in our catalog)

5. Return stats: {products_created, products_skipped, shows_processed, tracks_matched, tracks_unmatched}
```

### Title Normalization

```php
// "Scarlet Begonias >" → "scarlet begonias"
// "Fire On The Mountain" → "fire on the mountain"
// "drums/space" → "drums space"
$normalized = strtolower(preg_replace('/[^\w\s]/', '', trim($title)));
```

### Fuzzy Matching (Optional Enhancement)

For better matching, consider:
- Levenshtein distance for typos
- Common abbreviations: "FOTM" → "Fire On The Mountain"
- Segue markers: "Scarlet Begonias >" = "Scarlet Begonias"

## Execution

### Batch Strategy: Small → Large

Run one collection at a time. Validate before moving to the next.

```
┌─────────────────────────────────────────────────────────────┐
│  Goose (500) → Billy Strings (600) → moe (1,500) → ...     │
│     ↓              ↓                    ↓                   │
│  Validate       Validate             Validate               │
│  ✓ Products?    ✓ Matching?          ✓ No 429s?            │
│  ✓ Categories?  ✓ Performance?       ✓ Disk space?         │
└─────────────────────────────────────────────────────────────┘
```

### Batch 1: Small Collections (~15 min total)

Start with smaller collections to validate the process.

```bash
# Furthur (~254 unique shows, ~2 min)
bin/magento archive:download:metadata --collection=Furthur
bin/magento archive:populate:tracks "Furthur" --collection=Furthur --dry-run
bin/magento archive:populate:tracks "Furthur" --collection=Furthur

# JRAD (~358 unique shows, ~3 min)
bin/magento archive:download:metadata --collection=JRAD
bin/magento archive:populate:tracks "Joe Russo's Almost Dead" --collection=JRAD

# Billy Strings (~475 unique shows, ~4 min)
bin/magento archive:download:metadata --collection=BillyStrings
bin/magento archive:populate:tracks "Billy Strings" --collection=BillyStrings

# Goose (~486 unique shows, ~4 min)
bin/magento archive:download:metadata --collection=Goose
bin/magento archive:populate:tracks "Goose" --collection=Goose

# Verify
bin/mysql -e "SELECT COUNT(*) FROM catalog_product_entity WHERE sku LIKE 'archive-%';"
```

**Checkpoint:** How many products created? Any errors? Good match rate?

### Batch 2: Medium Collections (~1 hour total)

```bash
# STS9 (~552 unique shows, ~5 min)
bin/magento archive:download:metadata --collection=STS9
bin/magento archive:populate:tracks "STS9" --collection=STS9

# Tedeschi Trucks Band (~774 unique shows, ~6 min)
bin/magento archive:download:metadata --collection=TedeschiTrucksBand
bin/magento archive:populate:tracks "Tedeschi Trucks Band" --collection=TedeschiTrucksBand

# Railroad Earth (~1,275 unique shows, ~11 min)
bin/magento archive:download:metadata --collection=RailroadEarth
bin/magento archive:populate:tracks "Railroad Earth" --collection=RailroadEarth

# YMSB (~1,378 unique shows, ~12 min)
bin/magento archive:download:metadata --collection=YonderMountainStringBand
bin/magento archive:populate:tracks "Yonder Mountain String Band" --collection=YonderMountainStringBand

# moe. (~2,239 unique shows, ~19 min)
bin/magento archive:download:metadata --collection=moe
bin/magento archive:populate:tracks "moe." --collection=moe
```

### Batch 3: Large Collections (~2 hours total)

```bash
# Disco Biscuits (~2,000 unique shows, ~17 min)
bin/magento archive:download:metadata --collection=DiscoBiscuits
bin/magento archive:populate:tracks "Disco Biscuits" --collection=DiscoBiscuits

# Leftover Salmon (~2,000 unique shows, ~17 min)
bin/magento archive:download:metadata --collection=LeftoverSalmon
bin/magento archive:populate:tracks "Leftover Salmon" --collection=LeftoverSalmon

# String Cheese Incident (~2,500 unique shows, ~21 min)
bin/magento archive:download:metadata --collection=StringCheeseIncident
bin/magento archive:populate:tracks "String Cheese Incident" --collection=StringCheeseIncident

# Widespread Panic (~2,715 unique shows, ~23 min)
bin/magento archive:download:metadata --collection=WidespreadPanic
bin/magento archive:populate:tracks "Widespread Panic" --collection=WidespreadPanic

# Keller Williams (~3,053 unique shows, ~25 min)
bin/magento archive:download:metadata --collection=KellerWilliams
bin/magento archive:populate:tracks "Keller Williams" --collection=KellerWilliams
```

### Batch 4: Umphrey's McGee + Grateful Dead (~2 hours) - DO LAST

```bash
# Umphrey's McGee (~4,870 unique shows, ~41 min)
bin/magento archive:download:metadata --collection=UmphreysMcGee
bin/magento archive:populate:tracks "Umphrey's McGee" --collection=UmphreysMcGee

# Grateful Dead (~7,090 unique shows, ~59 min) - CAN RUN OVERNIGHT
bin/magento archive:download:metadata --collection=GratefulDead

# Can run overnight - progress is saved
# If interrupted, just run again to resume

bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead
```

### Progress Check Commands

```bash
# Check download progress
bin/magento archive:download:metadata --collection=GratefulDead --status

# Check cache size
du -sh var/archivedotorg/metadata/

# Count cached files per collection (using identifier patterns)
ls var/archivedotorg/metadata/ | grep "^gd" | wc -l           # Grateful Dead
ls var/archivedotorg/metadata/ | grep "^goose" | wc -l        # Goose
ls var/archivedotorg/metadata/ | grep "^billystrings" | wc -l # Billy Strings
ls var/archivedotorg/metadata/ | grep "^um" | wc -l           # Umphrey's McGee
ls var/archivedotorg/metadata/ | grep "^wsp" | wc -l          # Widespread Panic
```

## Artist Collection Mapping

| Artist | Archive.org Collection | Identifier Pattern |
|--------|------------------------|-------------------|
| Billy Strings | BillyStrings | `billystrings*` |
| Disco Biscuits | DiscoBiscuits | `db*` |
| Furthur | Furthur | `furthur*` |
| Goose | Goose | `goose*` |
| Grateful Dead | GratefulDead | `gd*` |
| JRAD (Joe Russo's Almost Dead) | JRAD | `jrad*` |
| Keller Williams | KellerWilliams | `kw*` |
| Leftover Salmon | LeftoverSalmon | `los*` |
| moe. | moe | `moe*` |
| My Morning Jacket | MyMorningJacket | `mmj*` |
| Phil Lesh & Friends | PhilLeshandFriends | `plf*` |
| Phish | Phish | `phish*` |
| Railroad Earth | RailroadEarth | `rre*` |
| Ratdog | Ratdog | `ratdog*` |
| String Cheese Incident | StringCheeseIncident | `sci*` |
| STS9 | STS9 | `sts9*` |
| Tea Leaf Green | TeaLeafGreen | `tlg*` |
| Tedeschi Trucks Band | TedeschiTrucksBand | `ttb*` |
| Twiddle | Twiddle | `twiddle*` |
| Umphrey's McGee | UmphreysMcGee | `um*` |
| Widespread Panic | WidespreadPanic | `wsp*` |
| YMSB (Yonder Mountain String Band) | YonderMountainStringBand | `ymsb*` |

**Note:** Studio bands (Smashing Pumpkins, King Gizzard, etc.) likely don't have Archive.org collections - skip for now, handle separately later.

## Disk Space Requirements

| Collection | Unique Shows | Est. Cache Size |
|------------|--------------|-----------------|
| Furthur | ~254 | ~25 MB |
| JRAD | ~358 | ~36 MB |
| Billy Strings | ~475 | ~48 MB |
| Goose | ~486 | ~49 MB |
| STS9 | ~552 | ~55 MB |
| Tedeschi Trucks | ~774 | ~77 MB |
| Railroad Earth | ~1,275 | ~128 MB |
| YMSB | ~1,378 | ~138 MB |
| Disco Biscuits | ~2,000 | ~200 MB |
| Leftover Salmon | ~2,000 | ~200 MB |
| moe. | ~2,239 | ~224 MB |
| String Cheese | ~2,500 | ~250 MB |
| Widespread Panic | ~2,715 | ~272 MB |
| Keller Williams | ~3,053 | ~305 MB |
| Umphrey's McGee | ~4,870 | ~487 MB |
| Grateful Dead | ~7,090 | ~709 MB |
| **Total** | **~35,000** | **~3.5 GB** |

Each show metadata JSON is ~50-150 KB (average ~100 KB).

### Cache Cleanup

```bash
# Delete all cached metadata (to free disk space after population)
rm -rf var/archivedotorg/metadata/

# Delete just one collection's cache
rm var/archivedotorg/metadata/gd*.json  # Grateful Dead

# Check cache size
du -sh var/archivedotorg/metadata/
```

## Files to Create/Modify

| File | Action | Phase |
|------|--------|-------|
| `Model/MetadataDownloader.php` | Create | 1 |
| `Api/MetadataDownloaderInterface.php` | Create | 1 |
| `Console/Command/DownloadMetadataCommand.php` | Create | 1 |
| `Model/TrackPopulatorService.php` | Create | 2 |
| `Api/TrackPopulatorServiceInterface.php` | Create | 2 |
| `Console/Command/PopulateTracksCommand.php` | Create | 2 |
| `etc/di.xml` | Add preferences | Both |
| `Api/CategoryAssignmentServiceInterface.php` | Add 1 method signature | 2 |
| `Model/CategoryAssignmentService.php` | Add 1 method implementation | 2 |

### MetadataDownloader Key Methods

| Method | Purpose |
|--------|---------|
| `download()` | Main download orchestrator (handles resume) |
| `selectBestRecordings()` | Pick best version per show date |
| `compareRecordingQuality()` | Ranking comparator (SBD > rating > reviews > downloads) |
| `getDownloadedIdentifiers()` | List cached identifiers (for resume) |
| `getProgress()` | Read progress for a collection |
| `needsDownload()` | Check if collection needs work |
| `fetchNewItems()` | Query Archive.org for items since date (incremental) |
| `updateProgress()` | Save progress after each download (crash-safe) |

### New Directories

```
var/archivedotorg/
├── metadata/                    # Cached JSON files (one per show)
│   ├── gd1977-05-08.sbd.miller.32601.sbeok.flac16.json
│   ├── gd1977-05-09.sbd.miller.32602.sbeok.flac16.json
│   └── ... (~35,000 files total)
├── download_progress.json       # Resume state for downloads (all collections)
└── populate_progress.json       # Resume state for population
```

### Configuration (etc/config.xml or system.xml)

```xml
<!-- Collection mapping for --all flag -->
<archive_collections>
    <GratefulDead>
        <artist_name>Grateful Dead</artist_name>
        <identifier_pattern>gd*</identifier_pattern>
    </GratefulDead>
    <Goose>
        <artist_name>Goose</artist_name>
        <identifier_pattern>goose*</identifier_pattern>
    </Goose>
    <!-- ... 20 more ... -->
</archive_collections>
```

## Verification

```bash
# Total products created
bin/mysql -e "SELECT COUNT(*) FROM catalog_product_entity WHERE sku LIKE 'archive-%';"

# Products per track category (shows distribution)
bin/mysql -e "
SELECT c.name AS track_name, COUNT(cp.product_id) AS recording_count
FROM catalog_category_product cp
JOIN catalog_category_entity c ON cp.category_id = c.entity_id
JOIN catalog_category_entity_int ci ON c.entity_id = ci.entity_id
JOIN eav_attribute a ON ci.attribute_id = a.attribute_id
WHERE a.attribute_code = 'is_song' AND ci.value = 1
GROUP BY c.entity_id, c.name
ORDER BY recording_count DESC
LIMIT 20;"

# Track categories still without products
bin/mysql -e "
SELECT COUNT(*) AS empty_tracks FROM catalog_category_entity c
JOIN catalog_category_entity_int ci ON c.entity_id = ci.entity_id
JOIN eav_attribute a ON ci.attribute_id = a.attribute_id
LEFT JOIN catalog_category_product cp ON c.entity_id = cp.category_id
WHERE a.attribute_code = 'is_song' AND ci.value = 1 AND cp.product_id IS NULL;"

# Test frontend playback
curl "https://magento.test/graphql" -d '{"query":"{ products(filter:{sku:{like:\"archive-%\"}},pageSize:1) { items { song_url } } }"}'
```

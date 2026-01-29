# Archive.org Import System - Developer Guide

**Version:** 2.0
**Last Updated:** 2026-01-28
**Status:** Active Development

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [System Components](#system-components)
3. [Data Flow](#data-flow)
4. [Adding a New Artist](#adding-a-new-artist)
5. [Extending Matching Logic](#extending-matching-logic)
6. [Database Schema](#database-schema)
7. [CLI Commands Reference](#cli-commands-reference)
8. [Troubleshooting](#troubleshooting)
9. [Development Workflow](#development-workflow)
10. [Testing](#testing)

---

## Architecture Overview

The Archive.org import system is a Magento 2 module that imports live concert recordings from Archive.org into product catalog entries. The system is designed for:

- **High performance**: Hybrid matching algorithms (~1,000x faster than fuzzy matching)
- **Reliability**: Lock protection, atomic file writes, crash recovery
- **Scalability**: Handles 186,000+ products across 35 artists
- **Quality**: Match rates >95% with phonetic matching fallback

### Key Design Principles

1. **YAML-Driven Configuration**: Artist metadata, track lists, and aliases are stored in YAML files for easy editing
2. **Organized File Structure**: JSON metadata organized by artist in `var/archivedotorg/metadata/{Artist}/`
3. **Hybrid Matching**: Progressive degradation from exact → alias → metaphone → limited fuzzy
4. **Crash-Safe Operations**: Atomic file writes, progress tracking, lock protection
5. **Admin Dashboard**: Real-time monitoring, unmatched track resolution, import history

---

## System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                    Archive.org Import System                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐ │
│  │ CLI Commands │──────│   Services   │──────│   Database   │ │
│  └──────────────┘      └──────────────┘      └──────────────┘ │
│         │                     │                      │         │
│         │                     │                      │         │
│  ┌──────▼──────┐      ┌──────▼──────┐      ┌────────▼──────┐ │
│  │  Download   │      │ Lock Service│      │  import_run   │ │
│  │  Populate   │      │  Matcher    │      │ artist_status │ │
│  │  Status     │      │  Importer   │      │ unmatched     │ │
│  └─────────────┘      └─────────────┘      └───────────────┘ │
│                                                                 │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐ │
│  │     YAML     │      │     JSON     │      │   Products   │ │
│  │   Configs    │      │   Metadata   │      │  (Catalog)   │ │
│  └──────────────┘      └──────────────┘      └──────────────┘ │
│         │                     │                      │         │
│         │                     │                      │         │
│  ┌──────▼───────────────────▼──────────────────────▼──────┐  │
│  │                   Admin Dashboard                       │  │
│  │  (Artists, Import History, Unmatched Tracks, Charts)   │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Core Modules

| Module | Purpose | Location |
|--------|---------|----------|
| **ArchiveDotOrg_Core** | Import engine, CLI commands, services | `src/app/code/ArchiveDotOrg/Core/` |
| **ArchiveDotOrg_Admin** | Dashboard, grids, admin UI | `src/app/code/ArchiveDotOrg/Admin/` |
| **ArchiveDotOrg_Player** | Frontend music player | `src/app/code/ArchiveDotOrg/Player/` |
| **ArchiveDotOrg_CategoryWork** | Album category management | `src/app/code/ArchiveDotOrg/CategoryWork/` |

### Key Services

| Service | File | Purpose |
|---------|------|---------|
| **LockService** | `Core/Model/LockService.php` | Prevents concurrent operations (file-based locking) |
| **ArchiveApiClient** | `Core/Model/ArchiveApiClient.php` | Archive.org API client with retry logic |
| **TrackMatcherService** | `Core/Model/TrackMatcherService.php` | Hybrid track matching (exact/alias/metaphone/fuzzy) |
| **BulkProductImporter** | `Core/Model/BulkProductImporter.php` | High-performance bulk product creation (~10x faster than ORM) |
| **ArtistConfigLoader** | `Core/Model/ArtistConfigLoader.php` | YAML configuration loader with caching |
| **MetadataDownloader** | `Core/Model/MetadataDownloader.php` | Downloads JSON metadata from Archive.org |
| **TrackPopulatorService** | `Core/Model/TrackPopulatorService.php` | Parses JSON and creates product entries |

---

## Data Flow

### Download Flow

```
User Command
    │
    ▼
bin/magento archive:download {artist}
    │
    ├─► LockService.acquire('download', 'artist-name')
    │
    ├─► ArchiveApiClient.search(collection: 'artist-name')
    │       │
    │       ▼
    │   Archive.org API (rate limited: ~1 req/sec)
    │       │
    │       ▼
    │   JSON responses (show metadata)
    │
    ├─► MetadataDownloader.save()
    │       │
    │       ▼
    │   var/archivedotorg/metadata/{Artist}/{identifier}.json
    │
    ├─► ProgressTracker.update()
    │       │
    │       ▼
    │   var/archivedotorg/progress/{artist}_download.json
    │
    └─► LockService.release()
```

### Populate Flow

```
bin/magento archive:populate {artist}
    │
    ├─► LockService.acquire('populate', 'artist-name')
    │
    ├─► ArtistConfigLoader.load('artist-name')
    │       │
    │       ▼
    │   config/artists/{artist}.yaml
    │
    ├─► TrackMatcherService.buildIndexes('artist-name')
    │       │
    │       ▼
    │   In-memory hash tables (exact, alias, metaphone)
    │
    ├─► For each JSON file:
    │   │
    │   ├─► TrackPopulatorService.parseShow($jsonData)
    │   │       │
    │   │       ▼
    │   │   Extract tracks, venue, date
    │   │
    │   ├─► TrackMatcherService.match($trackName, 'artist')
    │   │       │
    │   │       ├─► Exact match? Return immediately (100% confidence)
    │   │       ├─► Alias match? Return (95% confidence)
    │   │       ├─► Metaphone match? Return (85% confidence)
    │   │       ├─► Limited fuzzy (top 5)? Return (80%+ confidence)
    │   │       └─► No match? Log to unmatched_track table
    │   │
    │   └─► BulkProductImporter.import([$products])
    │           │
    │           ▼
    │       Direct SQL INSERT (10x faster than ORM)
    │           │
    │           ▼
    │       catalog_product_entity, catalog_product_entity_*
    │
    └─► LockService.release()
```

### Match Quality Metrics

| Match Type | Lookup Speed | Confidence | Example |
|------------|-------------|-----------|---------|
| Exact | O(1) - <1ms | 100% | "Tweezer" → "tweezer" |
| Alias | O(1) - <1ms | 95% | "YEM" → "You Enjoy Myself" |
| Metaphone | O(1) - <5ms | 85% | "Twezer" → "Tweezer" (phonetic) |
| Limited Fuzzy | O(5) - <20ms | 80-90% | "The Flew" → "The Flu" |
| Unmatched | - | 0% | Logged to unmatched_track table |

---

## Adding a New Artist

This is the most common developer task. Follow these steps to import a new artist.

### Step 1: Create YAML Configuration

Create a new YAML file in `src/app/code/ArchiveDotOrg/Core/config/artists/{artist-name}.yaml`:

```yaml
artist:
  name: "Lettuce"
  collection_id: "Lettuce"  # Archive.org collection ID
  url_key: "lettuce"        # URL-safe identifier

albums:
  - key: "outta-here"
    name: "Outta Here"
    url_key: "outta-here"
    year: 2002
    type: "studio"  # studio, live, compilation, virtual

  - key: "live-only"
    name: "Live Repertoire"
    type: "virtual"  # Tracks that appear live but no studio release

tracks:
  - key: "phyllis"
    name: "Phyllis"
    url_key: "phyllis"
    albums: ["outta-here"]       # Can appear on multiple albums
    canonical_album: "outta-here" # Primary/original album
    aliases: ["phillis", "philis"]  # Common misspellings/variations
    type: "original"  # original, cover, jam

  - key: "sam-huff"
    name: "Sam Huff"
    url_key: "sam-huff"
    albums: ["outta-here"]
    canonical_album: "outta-here"
    type: "original"

medleys:
  - pattern: "Phyllis > Sam Huff"
    tracks: ["phyllis", "sam-huff"]
    separator: ">"
```

**Key Points:**
- `collection_id` must match the Archive.org collection name exactly
- `url_key` must be lowercase alphanumeric + hyphens only
- `aliases` help match common misspellings and variations
- `medleys` pattern match concatenated tracks (e.g., "Song A > Song B")

### Step 2: Validate Configuration

```bash
bin/magento archivedotorg:validate lettuce
```

Expected output:
```
✓ YAML syntax valid
✓ Required fields present (artist.name, artist.collection_id)
✓ URL keys valid (lowercase, alphanumeric + hyphens)
✓ No duplicate track keys
✓ All canonical_album references exist
✓ No empty aliases arrays

Configuration is valid!
```

### Step 3: Set Up Category Structure

```bash
bin/magento archivedotorg:setup lettuce
```

This creates:
- Parent category: "Lettuce" (under root)
- Album subcategories: "Outta Here", "Live Repertoire", etc.
- Sets category images (if available)

### Step 4: Download Metadata

```bash
# Download first 50 shows (for testing)
bin/magento archivedotorg:download lettuce --limit=50

# Download all shows (production)
bin/magento archivedotorg:download lettuce
```

Progress is saved to `var/archivedotorg/progress/lettuce_download.json`. If interrupted, re-run the same command to resume.

**Files created:** `var/archivedotorg/metadata/Lettuce/*.json`

### Step 5: Populate Products

```bash
# Dry run (shows what would be created without committing)
bin/magento archivedotorg:populate lettuce --dry-run --limit=10

# Actual import
bin/magento archivedotorg:populate lettuce
```

### Step 6: Review Unmatched Tracks

```bash
bin/magento archivedotorg:show-unmatched lettuce
```

Output:
```
Unmatched tracks for Lettuce (15 total):

  Track Name          | Shows  | Suggested Match
  --------------------|--------|------------------
  Twezer              | 12     | Tweezer (metaphone)
  The Flue            | 5      | The Flu (metaphone)
  Phillis             | 3      | Phyllis (metaphone)
```

**Resolution:**
1. Add suggested matches as aliases in YAML:
   ```yaml
   - key: "tweezer"
     aliases: ["twezer", "twezzr"]  # Add common misspellings
   ```
2. Re-run populate to match previously unmatched tracks:
   ```bash
   bin/magento archivedotorg:populate lettuce
   ```

### Step 7: Monitor Status

```bash
bin/magento archivedotorg:status lettuce
```

Output:
```
Artist: Lettuce
  Downloaded shows:   523
  Processed shows:    510
  Unprocessed:        13
  Unmatched tracks:   15 (2.9%)
  Match rate:         97.1%
  Last download:      2026-01-27 14:30:00
  Last populate:      2026-01-27 15:45:00
```

---

## Extending Matching Logic

### Adding a Custom Match Strategy

You can extend `TrackMatcherService` with custom matching logic. Here's how to add a new match type:

**1. Create a custom matcher class:**

```php
<?php
declare(strict_types=1);

namespace YourVendor\YourModule\Model;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;
use ArchiveDotOrg\Core\Model\Data\MatchResult;

class CustomTrackMatcher
{
    /**
     * Match tracks using custom logic (e.g., abbreviations, Levenshtein on initials)
     */
    public function match(string $trackName, array $knownTracks): ?MatchResultInterface
    {
        // Example: Match "YEM" to "You Enjoy Myself" via initials
        $initials = $this->getInitials($trackName);

        foreach ($knownTracks as $track) {
            $trackInitials = $this->getInitials($track['name']);
            if ($initials === $trackInitials) {
                return new MatchResult(
                    $track['key'],
                    'initials',  // Custom match type
                    90           // Confidence score
                );
            }
        }

        return null;
    }

    private function getInitials(string $text): string
    {
        $words = explode(' ', strtolower($text));
        return implode('', array_map(fn($w) => $w[0] ?? '', $words));
    }
}
```

**2. Integrate into TrackMatcherService:**

Modify `Core/Model/TrackMatcherService.php` to call your custom matcher:

```php
public function match(string $trackName, string $artistKey): ?MatchResultInterface
{
    // Existing matchers...

    // 5. Custom matcher (after metaphone, before limited fuzzy)
    $customResult = $this->customMatcher->match($trackName, $this->allTracks[$artistKey] ?? []);
    if ($customResult && $customResult->getConfidence() >= 85) {
        return $customResult;
    }

    // Continue with limited fuzzy...
}
```

**3. Register in DI:**

Add to `etc/di.xml`:

```xml
<type name="ArchiveDotOrg\Core\Model\TrackMatcherService">
    <arguments>
        <argument name="customMatcher" xsi:type="object">YourVendor\YourModule\Model\CustomTrackMatcher</argument>
    </arguments>
</type>
```

### Custom String Normalizers

Create a custom normalizer for language-specific handling:

```php
<?php
declare(strict_types=1);

namespace YourVendor\YourModule\Model;

use ArchiveDotOrg\Core\Api\StringNormalizerInterface;

class GermanStringNormalizer implements StringNormalizerInterface
{
    public function normalize(string $input): string
    {
        // Convert German umlauts
        $input = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $input);

        // Standard normalization (lowercase, whitespace, etc.)
        return mb_strtolower(trim($input), 'UTF-8');
    }
}
```

---

## Database Schema

### Core Tables (Phase 0)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| **archivedotorg_artist** | Artist registry | artist_id, artist_name, collection_id, yaml_file_path |
| **archivedotorg_show_metadata** | Large JSON extracted from EAV | show_identifier, reviews_json, workable_servers |
| **archivedotorg_studio_albums** | Album artwork cache | album_id, artist_name, album_name, artwork_url |

### Dashboard Tables (Phase 5)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| **archivedotorg_import_run** | Audit trail of command executions | correlation_id, command, status, started_at, shows_processed |
| **archivedotorg_artist_status** | Pre-aggregated stats per artist | artist_id, match_rate, shows_downloaded, last_populate_at |
| **archivedotorg_unmatched_track** | Unmatched track quality tracking | track_name, occurrence_count, suggested_match, resolved |
| **archivedotorg_daily_metrics** | Time-series data for charts | metric_date, shows_imported, match_rate, api_requests |

### Important Indexes

Performance-critical indexes (must exist for <100ms dashboard queries):

```sql
-- catalog_product_entity
CREATE INDEX idx_created_at ON catalog_product_entity (created_at);

-- archivedotorg_import_run
CREATE INDEX idx_artist_status_started ON archivedotorg_import_run (artist_id, status, started_at DESC);
CREATE INDEX idx_correlation_id ON archivedotorg_import_run (correlation_id);

-- archivedotorg_artist_status
CREATE UNIQUE INDEX idx_artist_id ON archivedotorg_artist_status (artist_id);
```

---

## CLI Commands Reference

### Download Commands

```bash
# New command (recommended)
bin/magento archivedotorg:download {artist} [--limit=N] [--incremental] [--force]

# Legacy command (deprecated, shows warning)
bin/magento archivedotorg:download-metadata --collection={artist} [--limit=N]
```

### Populate Commands

```bash
# New command (recommended)
bin/magento archivedotorg:populate {artist} [--dry-run] [--limit=N] [--export-unmatched=file.txt]

# Legacy command (deprecated)
bin/magento archivedotorg:populate-tracks --collection={artist} [--limit=N]
```

### Status & Monitoring

```bash
# Artist-specific status
bin/magento archivedotorg:status {artist}

# All artists overview
bin/magento archivedotorg:status

# Show unmatched tracks
bin/magento archivedotorg:show-unmatched {artist}
```

### Configuration & Setup

```bash
# Validate YAML configuration
bin/magento archivedotorg:validate {artist}
bin/magento archivedotorg:validate --all  # Validate all 35 artists

# Set up category structure
bin/magento archivedotorg:setup {artist}

# Export legacy data to YAML
bin/magento archivedotorg:migrate:export
```

### Cleanup & Maintenance

```bash
# Clean cache files
bin/magento archivedotorg:cache-clear

# Cleanup orphaned products
bin/magento archivedotorg:cleanup-products --collection={artist}

# Organize metadata folders (one-time migration)
bin/magento archivedotorg:migrate:organize-folders [--dry-run]
```

### Album Artwork

```bash
# Download album artwork from MusicBrainz
bin/magento archivedotorg:download-album-art {artist} [--limit=N]

# Update category images with Wikipedia artwork
bin/magento archivedotorg:update-category-artwork

# Manually set artwork URL
bin/magento archivedotorg:set-artwork-url {category_id} {url}

# Retry failed artwork downloads
bin/magento archivedotorg:retry-missing-artwork
```

---

## Troubleshooting

### Common Errors

#### 1. Lock Already Held

**Error:**
```
[LockException] Lock 'download:lettuce' is already held by process 12345
```

**Cause:** Another process is currently downloading/populating the same artist.

**Solution:**
```bash
# Check if process is still running
ps aux | grep 12345

# If process is dead (stale lock):
rm var/archivedotorg/locks/download_lettuce.lock

# Then retry
bin/magento archivedotorg:download lettuce
```

#### 2. Progress File Corrupted

**Error:**
```
[RuntimeException] Progress file corrupted, scanning filesystem
```

**Cause:** Power loss or kill -9 during write.

**Solution:**
Progress is auto-recovered by scanning filesystem. Command will continue normally.

#### 3. Match Rate Below 90%

**Warning:**
```
Match rate: 78.5% (unmatched: 215 tracks)
```

**Solution:**
1. Review unmatched tracks:
   ```bash
   bin/magento archivedotorg:show-unmatched lettuce
   ```
2. Add aliases to YAML for suggested matches
3. Re-run populate:
   ```bash
   bin/magento archivedotorg:populate lettuce
   ```

#### 4. API Rate Limit Exceeded

**Error:**
```
[ArchiveApiException] Rate limit exceeded: 503 Service Temporarily Unavailable
```

**Cause:** Archive.org API rate limiting (~1 request/second).

**Solution:**
- Command will auto-retry with exponential backoff (built-in)
- For large imports, expect ~3.5 hours per 10,000 shows (API limitation)

#### 5. Database Migration Not Run

**Error:**
```
[Zend_Db_Statement_Exception] Table 'archivedotorg_import_run' doesn't exist
```

**Solution:**
```bash
bin/magento setup:upgrade
bin/magento cache:flush
```

### Log Locations

| Log | Path | Purpose |
|-----|------|---------|
| **Exception log** | `var/log/exception.log` | PHP exceptions and errors |
| **Archive.org log** | `var/log/archivedotorg.log` | Import operations, API calls |
| **Database log** | `archivedotorg_import_run` table | Command execution audit trail |
| **Progress files** | `var/archivedotorg/progress/*.json` | Resumable progress tracking |

### Debug Commands

```bash
# Check database tables
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"

# Verify indexes
bin/mysql -e "SHOW INDEX FROM catalog_product_entity WHERE Key_name LIKE 'idx_%';"

# Check recent imports
bin/mysql -e "SELECT correlation_id, command, status, started_at, completed_at
              FROM archivedotorg_import_run
              ORDER BY started_at DESC LIMIT 10;"

# Verify YAML syntax
php -r "yaml_parse_file('src/app/code/ArchiveDotOrg/Core/config/artists/lettuce.yaml');"

# Check file structure
find var/archivedotorg/metadata -type d | head -10
```

---

## Development Workflow

### Local Development Setup

1. **Enable developer mode:**
   ```bash
   bin/magento deploy:mode:set developer
   ```

2. **Disable caches for development:**
   ```bash
   bin/magento cache:disable config layout block_html
   ```

3. **Enable Xdebug (if needed):**
   ```bash
   bin/xdebug enable
   ```

### Making Code Changes

```bash
# After PHP changes
bin/magento setup:di:compile
bin/magento cache:flush

# After XML/YAML changes
bin/magento cache:flush config

# After schema changes
bin/magento setup:upgrade
bin/magento cache:flush

# After adding new commands
bin/magento cache:flush config
bin/magento list archivedotorg  # Verify command registered
```

### File Watcher (Auto-sync to Docker)

If running in Docker with named volumes:

```bash
# Start file watcher
bin/watch-start

# Check watcher status
bin/watch-status

# Stop watcher
bin/watch-stop
```

This auto-syncs changes from host (`src/`) to Docker container within 2 seconds.

---

## Testing

### Running Unit Tests

```bash
# All Archive.org tests
bin/magento dev:tests:run unit --filter=ArchiveDotOrg

# Specific test class
cd src
../vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  --filter="LockServiceTest" --testdox
```

### Running Integration Tests

```bash
# All Archive.org integration tests
bin/magento dev:tests:run integration --filter=ArchiveDotOrg
```

### Performance Benchmarks

```bash
# Matching algorithm performance
bin/magento archivedotorg:benchmark-matching --tracks=10000

# Import performance (if implemented)
bin/magento archivedotorg:benchmark-import --products=1000
```

**Expected Performance:**
- Exact match: <100ms for 10k tracks
- Metaphone match: <500ms for 10k tracks
- Dashboard load: <100ms with 186k products
- Bulk import: 10x faster than ORM (TrackImporter)

---

## Further Reading

- **[FIXES.md](import-rearchitecture/FIXES.md)** - Known issues and solutions
- **[Phase Documentation](import-rearchitecture/)** - Implementation phases 0-7
- **[Admin User Guide](ADMIN_GUIDE.md)** - Dashboard usage guide
- **[API Documentation](API.md)** - REST API endpoints (if available)

---

**Questions?** Check logs at `var/log/archivedotorg.log` or run debug commands above.

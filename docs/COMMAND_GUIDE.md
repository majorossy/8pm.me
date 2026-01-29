# Archive.org Import Commands - Quick Reference Guide

**Version:** 2.1 (Command Naming Standardization)
**Last Updated:** 2026-01-29

> **Note on Command Naming:** All commands now use the `archive:` prefix for consistency. Old command names with `archivedotorg:` prefix still work as aliases and are fully supported for backward compatibility.

---

## Command Categories

1. **Core Workflow** - Download → Populate → Monitor
2. **Setup & Configuration** - Artist setup, validation
3. **Maintenance** - Cleanup, refresh, sync
4. **Album Artwork** - Download and manage album artwork
5. **Benchmarking** - Performance testing and optimization
6. **Migration** - Data migration from legacy system
7. **Monitoring & Debugging** - Status, unmatched tracks

---

## 🚀 Core Workflow (Most Common)

### 1. `archive:download` - Download Show Metadata

Downloads JSON metadata from Archive.org and caches it locally.

**Basic Usage:**
```bash
# Download 50 shows for an artist
bin/magento archive:download "Phish" --limit=50

# Download ALL shows (use carefully - can be thousands!)
bin/magento archive:download "Grateful Dead"

# Force re-download (ignore cache)
bin/magento archive:download "STS9" --limit=100 --force

# Download specific show
bin/magento archive:download "Phish" --show=ph2024-12-31
```

**Options:**
- `--limit=N` - Download only N shows (recommended for testing)
- `--force` - Bypass progress tracking, re-download everything
- `--show=IDENTIFIER` - Download specific show by Archive.org identifier

**What It Does:**
- Fetches show list from Archive.org collection
- Downloads JSON metadata for each show
- Saves to: `var/archivedotorg/metadata/{Artist}/{identifier}.json`
- Tracks progress (resumable if interrupted)
- Logs to database: `archivedotorg_import_run` table

**Example Output:**
```
Downloading: Phish (Phish)
==============================

 Correlation ID: abc123...
Fetching show list from Archive.org for collection: Phish
 703/703 [============================] 100%

 ------------------ -------
  Metric             Count
 ------------------ -------
  Total Recordings   1,842
  Unique Shows       920
  Downloaded         50
  Already Cached     0
  Failed             0
 ------------------ -------

 [OK] Download complete!
```

**When to Use:**
- First time importing an artist
- Adding new shows periodically
- After Archive.org adds new content

---

### 2. `archive:populate` - Create Products from Metadata

Reads cached metadata and creates Magento products for each track.

**Basic Usage:**
```bash
# Populate products for all cached shows
bin/magento archive:populate "Phish"

# Populate with limit
bin/magento archive:populate "STS9" --limit=100

# Dry run (preview without creating products)
bin/magento archive:populate "Grateful Dead" --dry-run --limit=10
```

**Options:**
- `--limit=N` - Process only N shows
- `--dry-run` - Preview without creating products
- `--force` - Skip progress check, reprocess everything

**What It Does:**
- Reads JSON metadata from disk
- **Hybrid Track Matching:**
  1. Exact name match
  2. Alias match (from YAML config)
  3. Metaphone match (phonetic similarity)
  4. Fuzzy match (string similarity)
- Creates Magento products for each track
- Assigns to artist/album categories
- Tracks unmatched tracks for manual resolution

**Example Output:**
```
Populating products for: Phish
================================

Processing 50 shows...
 50/50 [============================] 100%

 ----------------------- -------
  Metric                  Count
 ----------------------- -------
  Shows Processed         50
  Tracks Created          782
  Tracks Updated          0
  Tracks Skipped          12
  Tracks Unmatched        8
  Match Rate              97.5%
 ----------------------- -------

 [OK] Populate complete!

⚠️  8 unmatched tracks found
Run: archive:show-unmatched "Phish"
```

**When to Use:**
- After downloading metadata
- To create products from cached data
- To retry failed imports

---

### 3. `archive:show-unmatched` - View Unmatched Tracks

Shows tracks that couldn't be matched to your catalog.

**Basic Usage:**
```bash
# Show unmatched for one artist
bin/magento archive:show-unmatched "Lettuce"

# Show unmatched for all artists
bin/magento archive:show-unmatched --all

# Export to CSV
bin/magento archive:show-unmatched "Phish" --export=unmatched.csv
```

**Example Output:**
```
Unmatched Tracks for: Lettuce
==============================

 Track Name              | Occurrences | Suggested Match (Metaphone)
-------------------------|-------------|----------------------------
 Twezer                  | 5           | Tweezer
 The Flu (Intro)         | 3           | The Flu
 Phylis                  | 2           | Phyllis
 Do It Like You Do       | 1           | Do It Like You Do It

Total Unmatched: 11 tracks across 4 unique names
Match Rate: 97.2%
```

**What to Do:**
1. Review suggested matches
2. Add aliases to `config/artists/{artist}.yaml`
3. Re-run `archive:populate`

**Example YAML Fix:**
```yaml
tracks:
  - key: "tweezer"
    name: "Tweezer"
    aliases: ["twezer", "tweezer reprise"]

  - key: "phyllis"
    name: "Phyllis"
    aliases: ["phylis", "phillis"]
```

---

## 🔧 Setup & Configuration

### 4. `archive:setup` - Initialize Artist Configuration

Loads artist YAML configuration into the database.

**Basic Usage:**
```bash
# Setup one artist
bin/magento archive:setup phish

# Setup all artists
bin/magento archive:setup --all

# Validate configuration during setup
bin/magento archive:setup lettuce --validate
```

**What It Does:**
- Reads `config/artists/{artist}.yaml`
- Creates database records in `archivedotorg_artist`
- Sets up category structure
- Validates configuration schema

**When to Use:**
- Before first import of a new artist
- After editing YAML configuration
- To rebuild category structure

---

### 5. `archive:validate` - Validate YAML Configuration

Checks YAML files for errors before using them.

**Basic Usage:**
```bash
# Validate one artist
bin/magento archive:validate phish

# Validate all artists
bin/magento archive:validate --all

# Strict mode (fail on warnings)
bin/magento archive:validate --all --strict
```

**What It Checks:**
- YAML syntax (parseable)
- Required fields present
- URL keys valid format
- No duplicate keys/aliases
- Album/track references valid

**Example Output:**
```
Validating: phish.yaml
=======================

✅ YAML syntax valid
✅ Required fields present
✅ URL keys valid format
✅ No duplicate keys
✅ Track references valid

⚠️  3 warnings:
- Album "junta" missing year
- Track "you-enjoy-myself" has 5 aliases (consider reducing)
- Track "fluffhead" not referenced in any album

Result: VALID (3 warnings)
```

**When to Use:**
- Before committing YAML changes
- After editing configuration
- As pre-deployment check

---

## 🧹 Maintenance Commands

### 6. `archive:status` - System Status & Statistics

Shows module status, configuration, and import statistics.

**Basic Usage:**
```bash
# Overall status
bin/magento archive:status

# Artist-specific status
bin/magento archive:status phish

# Test Archive.org API connectivity
bin/magento archive:status --test-collection=GratefulDead
```

**Example Output:**
```
Archive.org Import Status
==========================

Module: ENABLED
Debug Mode: DISABLED
Base URL: https://archive.org

Configured Artists: 12
  - Grateful Dead (GratefulDead) - 2,341 products
  - Phish (Phish) - 1,842 products
  - STS9 (STS9) - 523 products
  ... (9 more)

Recent Imports (Last 7 Days):
  - 2026-01-28: Phish (50 shows, 782 tracks) - SUCCESS
  - 2026-01-27: STS9 (100 shows, 1,205 tracks) - SUCCESS
  - 2026-01-26: Lettuce (25 shows, 380 tracks) - FAILED

Overall Statistics:
  Total Products: 15,432
  Average Match Rate: 97.8%
  Total Unmatched: 340 tracks
  Disk Usage: 2.3 GB
```

**When to Use:**
- Daily health checks
- Before starting large imports
- Troubleshooting issues

---

### 7. `archive:cleanup:cache` - Clean Old Metadata Cache

Removes old JSON metadata files to free disk space.

**Basic Usage:**
```bash
# Clean files older than 90 days
bin/magento archive:cleanup:cache --days=90

# Dry run to see what would be deleted
bin/magento archive:cleanup:cache --days=30 --dry-run

# Clean specific artist
bin/magento archive:cleanup:cache --artist=phish --days=60
```

**Options:**
- `--days=N` - Delete files older than N days (default: 90)
- `--dry-run` - Preview without deleting
- `--artist=NAME` - Clean specific artist only

**When to Use:**
- Disk space low
- After large imports complete
- Periodic maintenance (monthly)

---

### 8. `archive:cleanup:products` - Delete Imported Products

Bulk delete Archive.org products by collection or age.

**Basic Usage:**
```bash
# Delete by collection (dry run first!)
bin/magento archive:cleanup:products --collection=OldArtist --dry-run

# Delete products older than 1 year
bin/magento archive:cleanup:products --older-than=365 --dry-run

# Actually delete (with confirmation)
bin/magento archive:cleanup:products --collection=TestArtist

# Skip confirmation (dangerous!)
bin/magento archive:cleanup:products --collection=TestArtist --force
```

**Options:**
- `--collection=NAME` - Delete by Archive.org collection
- `--older-than=DAYS` - Delete products older than N days
- `--dry-run` - Preview without deleting
- `--force` - Skip confirmation prompt
- `--batch-size=N` - Products per batch (default: 100)

**When to Use:**
- Removing test imports
- Cleaning up discontinued artists
- Removing old/duplicate content

---

### 9. `archive:refresh:products` - Update Product Metadata

Refreshes existing products with fresh data from Archive.org (ratings, downloads, etc).

**Basic Usage:**
```bash
# Dry run to preview API calls
bin/magento archive:refresh:products "STS9" --dry-run

# Refresh with default fields (fast)
bin/magento archive:refresh:products "Grateful Dead"

# Refresh specific fields only
bin/magento archive:refresh:products "Phish" --fields=rating,reviews

# Include all fields (slow - includes per-show API)
bin/magento archive:refresh:products "STS9" --fields=rating,reviews,downloads,trending,length

# Force refresh (bypass 1-week cache)
bin/magento archive:refresh:products "Phish" --force --limit=100
```

**Available Fields:**
| Field | Description | API Mode |
|-------|-------------|----------|
| `rating` | Average rating | Batch (fast) |
| `reviews` | Number of reviews | Batch (fast) |
| `downloads` | Total downloads | Batch (fast) |
| `trending` | Weekly/monthly downloads | Batch (fast) |
| `length` | Track duration | Per-show (slow) |

**Options:**
- `--fields=FIELDS` - Comma-separated field list
- `--limit=N` - Process only N shows
- `--force` - Bypass cache (re-fetch from API)
- `--dry-run` - Preview API calls needed

**Performance:**
- Default fields: ~6 API calls for 523 shows (batch API)
- With `length`: 523 API calls (per-show API)
- Cache TTL: 1 week

**When to Use:**
- Weekly/monthly stats updates
- After Archive.org updates metadata
- Syncing popularity data

---

### 10. `archive:sync:albums` - Sync Category Structure

Syncs products to album/song categories based on title matching.

**Basic Usage:**
```bash
# Sync with default threshold (75%)
bin/magento archive:sync:albums

# Strict matching (90% threshold)
bin/magento archive:sync:albums --threshold=90

# Permissive matching (60% threshold)
bin/magento archive:sync:albums --threshold=60
```

**What It Does:**
- Reads album definitions from YAML
- Matches products to albums by title similarity
- Creates album categories if missing
- Assigns products to matched albums

**When to Use:**
- After updating album definitions in YAML
- Fixing category assignments
- Reorganizing product structure

---

## 🎨 Album Artwork Commands

### 11. `archive:artwork:download` - Download Album Artwork

Downloads studio album artwork from Wikipedia API.

**Basic Usage:**
```bash
# Download artwork for one artist
bin/magento archive:artwork:download "Phish" --limit=20

# Download artwork for all configured artists
bin/magento archive:artwork:download --all

# Dry run to preview
bin/magento archive:artwork:download "Grateful Dead" --limit=10 --dry-run
```

**Options:**
- `--limit=N` - Maximum albums per artist (default: 50)
- `--all` - Download for all configured artists
- `--dry-run` - Preview without downloading

**What It Does:**
- Fetches studio album list from Wikipedia
- Downloads album artwork URLs
- Caches artwork in `archivedotorg_studio_albums` table
- Maps albums to Magento categories

**When to Use:**
- After importing a new artist
- To enrich artist pages with album artwork
- When Wikipedia updates album images

**Note:** Old command name `archivedotorg:download-album-art` still works as an alias.

---

### 12. `archive:artwork:update` - Update Category Artwork

Updates category images with downloaded artwork URLs.

**Basic Usage:**
```bash
# Update all categories (dry run first)
bin/magento archive:artwork:update --dry-run

# Actually update
bin/magento archive:artwork:update
```

**Options:**
- `--dry-run` - Preview changes without applying

**What It Does:**
- Reads cached artwork URLs from database
- Sets `wikipedia_artwork_url` attribute on categories
- Maps album names to category IDs

**When to Use:**
- After downloading artwork
- To sync artwork to category structure

**Note:** Old command name `archivedotorg:update-category-artwork` still works as an alias.

---

### 13. `archive:artwork:set-url` - Manually Set Artwork URL

Manually set or clear artwork URL for a specific album category.

**Basic Usage:**
```bash
# List albums missing artwork
bin/magento archive:artwork:set-url --list-missing

# Set artwork URL
bin/magento archive:artwork:set-url 1234 "https://upload.wikimedia.org/..."

# Clear artwork
bin/magento archive:artwork:set-url 1234 none
```

**Options:**
- `--list-missing` - Show albums without artwork
- `--notes=TEXT` - Add notes about manual override

**When to Use:**
- Artwork auto-detection failed
- Correcting incorrect artwork
- Using custom artwork sources

**Note:** Old command name `archivedotorg:set-artwork-url` still works as an alias.

---

### 14. `archive:artwork:retry` - Retry Missing Artwork

Re-enriches albums that failed to get artwork initially.

**Basic Usage:**
```bash
# Dry run to preview
bin/magento archive:artwork:retry --dry-run

# Retry with limit
bin/magento archive:artwork:retry --limit=50

# Actually retry all
bin/magento archive:artwork:retry
```

**Options:**
- `--limit=N` - Process only N albums
- `--dry-run` - Preview without processing

**What It Does:**
- Finds albums without artwork URLs
- Re-runs enrichment with improved matching
- Updates database with found artwork

**When to Use:**
- After initial artwork download completes
- To catch albums that failed initially
- When Wikipedia updates missing artwork

**Note:** Old command name `archivedotorg:retry-missing-artwork` still works as an alias.

---

## ⚡ Benchmarking Commands

### 15. `archive:benchmark:matching` - Track Matching Performance

Benchmarks the hybrid track matching algorithm performance.

**Basic Usage:**
```bash
# Benchmark with 10,000 tracks
bin/magento archive:benchmark:matching --tracks=10000

# Benchmark specific algorithm
bin/magento archive:benchmark:matching --algorithm=fuzzy

# Benchmark with 50,000 tracks
bin/magento archive:benchmark:matching --tracks=50000 --iterations=20
```

**Options:**
- `--tracks=N` - Number of tracks to test (default: 10000)
- `--iterations=N` - Iterations per test (default: 10)
- `--algorithm=TYPE` - Test specific algorithm: exact, alias, metaphone, fuzzy, all
- `--compare-levenshtein` - Compare with full Levenshtein (WARNING: slow, max 100 tracks)

**What It Tests:**
- Index building speed (<5000ms target)
- Exact match (<100ms target)
- Alias match (<100ms target)
- Metaphone match (<500ms target)
- Fuzzy match (<2000ms target)
- Memory usage (<50MB target)

**Example Output:**
```
Track Matching Algorithm Benchmarks
====================================

Tracks: 10,000
Iterations: 10

Index Building Performance:
Duration: 0.44 ms ✓ PASS
Memory: 4.5 MB
Tracks Indexed: 10,000

Exact Match:
Duration: 0.01 ms ✓ PASS
Avg per Match: 0.001 ms

Metaphone Match:
Duration: 0.26 ms ✓ PASS
Avg per Match: 0.026 ms

Summary: ✓ All performance targets met!
```

**When to Use:**
- Performance testing after code changes
- System health checks
- Capacity planning

**Note:** Old command name `archivedotorg:benchmark-matching` still works as an alias.

---

### 16. `archive:benchmark:import` - Import Strategy Performance

Compares ORM vs Bulk SQL import performance.

**Basic Usage:**
```bash
# Benchmark both methods with 1000 products
bin/magento archive:benchmark:import --products=1000

# Benchmark specific method
bin/magento archive:benchmark:import --method=bulk --products=5000

# Keep test products for inspection
bin/magento archive:benchmark:import --skip-cleanup
```

**Options:**
- `--products=N` - Number of test products (default: 1000)
- `--method=TYPE` - Test specific method: orm, bulk, all
- `--skip-cleanup` - Keep test products in database

**What It Tests:**
- ORM import speed (products/second)
- Bulk SQL import speed (products/second)
- Memory usage comparison
- Query count comparison
- Speedup factor (target: 10x)

**Example Output:**
```
Product Import Benchmarks
==========================

Products: 1,000
Method: all

ORM Import:
Duration: 45.3 seconds
Memory: 256 MB
Products/Second: 22.1

Bulk SQL Import:
Duration: 3.2 seconds
Memory: 64 MB
Products/Second: 312.5

Comparison:
Speedup Factor: 14.2x ✓ PASS
Memory Reduction: 75% ✓ PASS

✓ All performance targets met!
```

**When to Use:**
- Comparing import strategies
- Performance optimization
- Before large imports

**Note:** Old command name `archivedotorg:benchmark-import` still works as an alias.

---

### 17. `archive:benchmark:dashboard` - Dashboard Query Performance

Benchmarks admin dashboard query performance.

**Basic Usage:**
```bash
# Run all dashboard benchmarks
bin/magento archive:benchmark:dashboard
```

**What It Tests:**
- Artist grid query (<100ms target)
- Import history query (<100ms target)
- Unmatched tracks query (<100ms target)
- Imports per day chart (<50ms target)
- Daily metrics aggregation (<200ms target)
- Index verification (ensures indexes are used)

**Example Output:**
```
Dashboard Query Benchmarks
==========================

Artist Grid Query:
Duration: 42 ms ✓ PASS
Rows Returned: 12
Index Used: Yes ✓

Import History Query:
Duration: 38 ms ✓ PASS
Rows Returned: 25
Index Used: Yes ✓

Index Verification:
Table: archivedotorg_import_run | Index: idx_artist_started | ✓ USED
Table: archivedotorg_track_match | Index: idx_artist_name | ✓ USED

✓ All performance targets met!
```

**When to Use:**
- After database migrations
- Performance troubleshooting
- Post-deployment verification

**Note:** Old command name `archivedotorg:benchmark-dashboard` still works as an alias.

---

## 🔄 Migration Commands (One-Time)

### 18. `archive:migrate:organize-folders` - Migrate to Artist Folders

Migrates flat metadata structure to organized artist-based folders.

**Basic Usage:**
```bash
# Dry run to preview
bin/magento archive:migrate:organize-folders --dry-run

# Execute migration
bin/magento archive:migrate:organize-folders

# Backup location
ls var/archivedotorg/metadata.backup.*
```

**What It Does:**
- **Before:** `var/archivedotorg/metadata/gd1977-05-08.json`
- **After:** `var/archivedotorg/metadata/Grateful Dead/gd1977-05-08.json`
- Creates backup before migrating
- Moves files to artist subdirectories
- Quarantines unmappable files

**When to Use:**
- **ONE-TIME ONLY** during Phase 7 deployment
- **Already completed** if you ran Phase 3 successfully

---

### 19. `archive:migrate:export` - Export to YAML Configuration

Exports artist data from PHP patches to YAML configuration files.

**Basic Usage:**
```bash
# Export all artists
bin/magento archive:migrate:export

# Export specific artist
bin/magento archive:migrate:export --artist=phish

# Dry run
bin/magento archive:migrate:export --dry-run
```

**What It Does:**
- Reads artist data from old PHP data patches
- Generates YAML files: `config/artists/{artist}.yaml`
- Includes artist info, albums, tracks, aliases
- Creates backup of existing YAML files

**When to Use:**
- **ONE-TIME ONLY** during Phase 7 deployment
- **Already completed** if you ran Phase 3 successfully

---

## 📊 Monitoring & Debugging

### 20. `archive:status` - System Status (Moved Above)

## 🎯 Common Workflows

### Workflow 1: First Time Artist Import

```bash
# 1. Create YAML configuration
vim src/app/code/ArchiveDotOrg/Core/config/artists/new-artist.yaml

# 2. Validate configuration
bin/magento archive:validate new-artist

# 3. Setup artist
bin/magento archive:setup new-artist

# 4. Download metadata (start small!)
bin/magento archive:download "New Artist" --limit=10

# 5. Populate products
bin/magento archive:populate "New Artist"

# 6. Check for unmatched tracks
bin/magento archive:show-unmatched "New Artist"

# 7. Fix aliases in YAML if needed
vim src/app/code/ArchiveDotOrg/Core/config/artists/new-artist.yaml

# 8. Re-populate with fixes
bin/magento archive:populate "New Artist"

# 9. Verify in admin dashboard
# Navigate to: Content > Archive.org Import > Dashboard
```

---

### Workflow 2: Periodic Content Updates

```bash
# 1. Download new shows
bin/magento archive:download "Phish" --limit=50

# 2. Populate new products
bin/magento archive:populate "Phish"

# 3. Check status
bin/magento archive:status phish

# 4. Refresh stats weekly
bin/magento archive:refresh:products "Phish"
```

---

### Workflow 3: Troubleshooting Failed Import

```bash
# 1. Check status
bin/magento archive:status phish

# 2. Check logs
tail -100 var/log/archivedotorg.log
tail -100 var/log/exception.log | grep -i archivedotorg

# 3. Check for unmatched tracks
bin/magento archive:show-unmatched phish

# 4. Check database
bin/mysql -e "SELECT * FROM archivedotorg_import_run WHERE status='failed' ORDER BY started_at DESC LIMIT 5;"

# 5. Retry with force flag
bin/magento archive:download "Phish" --force --limit=10
bin/magento archive:populate "Phish" --force
```

---

### Workflow 4: Clean Up Test Data

```bash
# 1. Dry run to preview
bin/magento archive:cleanup:products --collection=TestArtist --dry-run

# 2. Delete products
bin/magento archive:cleanup:products --collection=TestArtist

# 3. Clean metadata cache
bin/magento archive:cleanup:cache --artist=testartist --days=0

# 4. Verify cleanup
bin/magento archive:status testartist
```

---

## 📝 Command Comparison: Old vs New

| Old Command | New Command | Status |
|-------------|-------------|--------|
| `archivedotorg:download-metadata` | `archive:download` | ✅ Replaced |
| `archivedotorg:populate-tracks` | `archive:populate` | ✅ Replaced |
| `archivedotorg:import-shows` | `archive:download` + `archive:populate` | ✅ Split into 2 commands |
| N/A | `archive:show-unmatched` | ✅ New |
| N/A | `archive:validate` | ✅ New |
| N/A | `archive:setup` | ✅ New |
| `archivedotorg:status` | `archive:status` | ✅ Enhanced |
| `archivedotorg:sync-albums` | `archive:sync:albums` | ✅ Renamed |
| `archivedotorg:cleanup-products` | `archive:cleanup:products` | ✅ Renamed |
| `archivedotorg:refresh-products` | `archive:refresh:products` | ✅ Renamed |

---

## 🔍 Quick Reference Card

**Download → Populate → Monitor:**
```bash
archive:download "Artist" --limit=50    # Get metadata
archive:populate "Artist"               # Create products
archive:show-unmatched "Artist"         # Check matches
archive:status "Artist"                 # Verify success
```

**Setup New Artist:**
```bash
archive:validate artist-key             # Check YAML
archive:setup artist-key                # Load config
archive:download "Artist" --limit=10    # Test import
```

**Maintenance:**
```bash
archive:refresh:products "Artist"       # Update stats
archive:cleanup:cache --days=90         # Free space
archive:sync:albums --threshold=75      # Fix categories
```

**Troubleshooting:**
```bash
archive:status                          # System health
archive:show-unmatched --all            # Find issues
tail -f var/log/archivedotorg.log       # Watch logs
```

---

## 💡 Pro Tips

1. **Always dry-run first:** Use `--dry-run` flag for destructive operations
2. **Start small:** Use `--limit=10` for testing before full imports
3. **Monitor logs:** Watch `var/log/archivedotorg.log` during imports
4. **Check unmatched:** Run `archive:show-unmatched` after every populate
5. **Validate YAML:** Run `archive:validate` before committing changes
6. **Clear locks:** If import hangs, check `var/locks/archivedotorg/`
7. **Use status command:** Run `archive:status` daily for health checks
8. **Batch imports:** For large artists, import in batches of 100-500 shows
9. **Schedule refreshes:** Run `archive:refresh:products` weekly via cron
10. **Document aliases:** Keep YAML files updated with new track aliases

---

## 📚 Additional Resources

- **Runbook:** `docs/RUNBOOK.md` - Operational procedures
- **Developer Guide:** `docs/DEVELOPER_GUIDE.md` - API reference
- **Admin Guide:** `docs/ADMIN_GUIDE.md` - Dashboard usage
- **Deployment Plan:** `deployment/DEPLOYMENT_PLAN.md` - Deployment strategy

---

**End of Command Guide**

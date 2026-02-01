# Archive.org Import Specialist - 8PM Project

You are an import pipeline specialist for the 8PM live music archive.

## Critical Knowledge

**Import Pipeline:** Download → Populate → Enrich → Fix-Index
**CRITICAL:** Always run `bin/fix-index` after `archive:populate`!
**Status Tracking:** `docs/IMPORT_STATUS.md`
**Logs:** `var/log/archivedotorg.log` and `var/log/import-all-artists-*.log`

## Full Import Pipeline

```bash
# 1. Download metadata from Archive.org
bin/magento archive:download "Phish"
# Saves to: var/archivedotorg/metadata/{CollectionId}/

# 2. Create Magento products from metadata
bin/magento archive:populate "Phish"
# Creates products in catalog_product_entity

# 3. Fix category-product index (CRITICAL!)
bin/fix-index
# Populates catalog_category_product_index_store1

# 4. Enrich artist category with bio, stats
bin/magento archive:artist:enrich "Phish" --fields=bio,origin,stats
```

## ⚠️ CRITICAL: Fix-Index After Populate

**Why:** Magento's native indexer only populates `catalog_category_product_index` (base table), but GraphQL uses `catalog_category_product_index_store1` (store-specific). Without `bin/fix-index`, GraphQL returns 0 products!

```bash
# ALWAYS run after populate
bin/magento archive:populate "Phish"
bin/fix-index   # REQUIRED!

# Verify it worked
bin/mysql -e "SELECT COUNT(*) FROM catalog_category_product_index_store1 WHERE category_id = 1510;"
```

## Command Reference

### Download Commands
```bash
# Basic download
bin/magento archive:download "Phish"

# Resume interrupted download
bin/magento archive:download "Phish" --incremental

# Limit number of shows
bin/magento archive:download "Phish" --limit=100

# Download saves to:
# var/archivedotorg/metadata/{CollectionId}/*.json
```

## ⚠️ CRITICAL: Concurrency Rules

### Downloads: ONE AT A TIME (Global Lock)
```bash
# ❌ NEVER run downloads in parallel - will fail with lock error
bin/magento archive:download "Phish" &
bin/magento archive:download "Grateful Dead" &  # FAILS - global lock

# ✅ Run downloads sequentially
bin/magento archive:download "Phish"
bin/magento archive:download "Grateful Dead"
```

**Why:** Archive.org rate-limits requests. Concurrent downloads cause API failures and data corruption. A global lock (`_GLOBAL_`) prevents parallel downloads.

### Populate: CAN Run in Parallel (Per-Artist Lock)
```bash
# ✅ Populate can run multiple artists in parallel
bin/magento archive:populate "Phish" &
bin/magento archive:populate "Grateful Dead" &
wait  # Wait for both to finish
bin/fix-index  # Run once after all populates
```

**Why:** Populate reads local metadata files, not Archive.org API. Per-artist locks prevent the same artist from being populated twice simultaneously.

### Lock Files
Locks stored in: `var/archivedotorg/locks/`
- `download__GLOBAL_.lock` - Global download lock
- `download_Phish.lock` - Per-artist download lock
- `populate_Phish.lock` - Per-artist populate lock

If a process crashes and leaves a stale lock:
```bash
# Check lock status
ls -la var/archivedotorg/locks/

# Remove stale lock manually (only if process is dead!)
rm var/archivedotorg/locks/download__GLOBAL_.lock
```

### Populate Commands
```bash
# Basic populate (creates Magento products)
bin/magento archive:populate "Phish"

# Re-import existing (use after YAML changes)
bin/magento archive:populate "Phish" --force

# Preview without making changes
bin/magento archive:populate "Phish" --dry-run

# Limit for testing
bin/magento archive:populate "Phish" --limit=50

# Export unmatched tracks to YAML
bin/magento archive:populate "Phish" --export-unmatched
```

### Enrichment Commands
```bash
# All fields
bin/magento archive:artist:enrich "Phish" --force

# Specific fields only
bin/magento archive:artist:enrich "Phish" --fields=bio,origin,stats

# All configured artists
bin/magento archive:artist:enrich --all --fields=bio,origin,stats

# Preview without changes
bin/magento archive:artist:enrich "Phish" --dry-run
```

### Status & Validation
```bash
# Check overall status
bin/magento archive:status

# Test specific collection
bin/magento archive:status --test-collection=Phish

# Validate YAML config
bin/magento archive:validate "Phish"

# View unmatched tracks
bin/magento archive:show-unmatched "Phish"
bin/magento archive:show-unmatched "Phish" --limit=100
bin/magento archive:show-unmatched "Phish" --export=yaml
```

### Cleanup Commands
```bash
# Preview deletion
bin/magento archive:cleanup:products --collection=TestArtist --dry-run

# Actually delete products
bin/magento archive:cleanup:products --collection=TestArtist --force
```

## Track Matching Algorithm

The populate command uses **hybrid matching** (not simple fuzzy):

1. **Exact hash match** - Direct title comparison with normalization
2. **Alias match** - Configured aliases in YAML (e.g., "Tweezer Reprise" → "Tweezer (Reprise)")
3. **Metaphone phonetic match** - Sounds-alike matching (handles typos)
4. **Limited fuzzy (Levenshtein)** - Only on top 5 candidates to avoid false positives

The `fuzzy_threshold` in YAML (default 0.85) controls minimum similarity for fuzzy matches.

## YAML Configuration

### File Location
`src/app/code/ArchiveDotOrg/Core/config/artists/{collection-id}.yaml`

### Complete Structure
```yaml
# ========================================
# Artist Configuration
# ========================================

artist:
  name: "Artist Display Name"           # Required: Displayed name
  collection_id: "ArchiveCollectionId"  # Required: Archive.org collection
  url_key: "artist-url-key"             # Required: lowercase, hyphens

# ========================================
# Albums Section
# ========================================
albums:
  - key: "album-key"                    # Required: unique identifier
    name: "Album Name"                  # Required: display name
    url_key: "album-url-key"            # Required: lowercase, hyphens
    type: "studio"                      # Required: studio, live, compilation
    year: 2000                          # Optional: release year

# ========================================
# Tracks Section
# ========================================
tracks:
  - key: "track-key"                    # Required: unique identifier
    name: "Track Name"                  # Required: display name
    url_key: "track-url-key"            # Required: lowercase, hyphens
    type: "original"                    # Required: original, cover, medley, intro, outro
    albums: ["album-key"]               # Required: array of album keys
    canonical_album: "album-key"        # Required: primary album
    aliases:                            # Optional: alternate names for matching
      - "Alternative Name"
      - "Live Version Name"

# ========================================
# Medleys Section (Optional)
# ========================================
medleys:
  - pattern: "Track A > Track B"        # Medley pattern to detect
    tracks: ["track-a", "track-b"]      # Component tracks
```

### Field Requirements

| Field | Location | Type | Required | Notes |
|-------|----------|------|----------|-------|
| name | artist | string | Yes | Display name |
| collection_id | artist | string | Yes | Must match Archive.org |
| url_key | all | string | Yes | lowercase, hyphens, max 255 |
| key | album/track | string | Yes | Unique identifier |
| type | album | enum | Yes | studio, live, compilation |
| type | track | enum | Yes | original, cover, medley, intro, outro |
| albums | track | array | Yes | Album keys containing track |
| canonical_album | track | Yes | string | Primary album for track |
| aliases | track | array | No | Alternate names for matching |

## Enrichment Fields

Valid `--fields` options for `archive:artist:enrich`:

| Field | Source | Purpose |
|-------|--------|---------|
| `bio` | Wikipedia | Extended biography |
| `origin` | Wikipedia Infobox | City/country of origin |
| `years_active` | Wikipedia Infobox | Active years (includes formation date) |
| `genres` | Wikipedia Infobox | Musical genres |
| `website` | Wikipedia Infobox | Official website URL |
| `facebook` | Brave Search | Facebook page |
| `instagram` | Brave Search | Instagram handle |
| `twitter` | Brave Search | Twitter/X handle |
| `stats` | Local Database | Total shows, most played track |
| `stats_extended` | Local Database | Additional metrics (top venues, show frequency) |

**Requirements:**
- Wikipedia fields work without API keys
- Social media fields (`facebook`, `instagram`, `twitter`) require **Brave Search API key** in `env/magento.env`:
  ```
  BRAVE_SEARCH_API_KEY=your-api-key-here
  ```
- Get API key from: https://brave.com/search/api/

## Adding a New Artist

### Step 1: Find Archive.org Collection ID
```bash
# Search on Archive.org
# https://archive.org/search?query=collection:ArtistName

# Example: Lettuce
# URL: https://archive.org/details/Lettuce
# Collection ID: Lettuce
```

### Step 2: Create YAML File
```bash
# File: src/app/code/ArchiveDotOrg/Core/config/artists/lettuce.yaml

artist:
  name: "Lettuce"
  collection_id: "Lettuce"
  url_key: "lettuce"

albums: []    # Auto-populated during first import
tracks: []    # Auto-populated during first import
medleys: []   # Optional
```

### Step 3: Validate (Optional)
```bash
bin/magento archive:validate "Lettuce"
```

### Step 4: Download
```bash
bin/magento archive:download "Lettuce"
```

### Step 5: Populate
```bash
bin/magento archive:populate "Lettuce"
```

### Step 6: Fix Index (CRITICAL!)
```bash
bin/fix-index
```

### Step 7: Enrich (Optional)
```bash
bin/magento archive:artist:enrich "Lettuce" --fields=bio,origin,stats
```

## Monitoring Progress

### During Import
```bash
# Watch log in real-time
tail -f var/log/archivedotorg.log

# Check cached metadata files
ls var/archivedotorg/metadata/Phish/*.json | wc -l

# Check progress files
cat var/archivedotorg/download_progress.json
cat var/archivedotorg/populate_progress.json
```

### After Import
```bash
# Check artist status in database
bin/mysql -e "SELECT * FROM archivedotorg_artist WHERE artist_name='Phish'"

# View import history
bin/mysql -e "SELECT artist_name, command_name, items_successful, items_failed, duration_seconds FROM archivedotorg_import_run ORDER BY created_at DESC LIMIT 10;"

# Check match rate
bin/mysql -e "SELECT artist_name, match_rate_percent FROM archivedotorg_artist WHERE imported_tracks > 0;"

# Check product count in GraphQL index
bin/mysql -e "SELECT COUNT(*) FROM catalog_category_product_index_store1 WHERE category_id = 1510;"
```

## Troubleshooting

### Common Issues

| Problem | Cause | Solution |
|---------|-------|----------|
| GraphQL returns 0 products | Missing store index | Run `bin/fix-index` |
| "Failed to build indexes" | No metadata downloaded | Run `archive:download` first |
| Download timeout | Archive.org rate limit | Use `--limit=500`, retry later |
| Low match rate | Missing aliases | Add aliases to YAML, use `--force` |
| Memory exhausted | Too many shows at once | Use `--limit=100` |
| "Lock timeout" | Another import running | Wait or kill other process |

### Recovery Procedures

**If download fails mid-way:**
```bash
# Resume with incremental mode
bin/magento archive:download "Phish" --incremental
```

**If populate fails mid-way:**
```bash
# Check what was imported
bin/magento archive:status --test-collection=Phish

# Resume with force
bin/magento archive:populate "Phish" --force

# Always fix index after
bin/fix-index
```

**If you need to rollback:**
```bash
# Preview what would be deleted
bin/magento archive:cleanup:products --collection=TestArtist --dry-run

# Delete products
bin/magento archive:cleanup:products --collection=TestArtist --force

# Reindex
bin/fix-index
```

## Production Deployment

### Scheduled Imports (Cron)

Configure in Admin: **Stores > Configuration > Archive.org Import > Scheduled Import**

Or add to crontab:
```bash
# Daily incremental import at 2 AM
0 2 * * * cd /path/to/magento && bin/magento archive:download "Grateful Dead" --incremental 2>&1 | logger
15 2 * * * cd /path/to/magento && bin/magento archive:populate "Grateful Dead" 2>&1 | logger
30 2 * * * cd /path/to/magento && bin/fix-index 2>&1 | logger
```

### Large Collection Import (1000+ shows)

```bash
# 1. Set indexers to scheduled mode
bin/magento indexer:set-mode schedule

# 2. Download in batches
bin/magento archive:download "Phish" --limit=500

# 3. Populate
bin/magento archive:populate "Phish"

# 4. Fix index
bin/fix-index

# 5. Full reindex
bin/magento indexer:reindex

# 6. Restore indexer mode
bin/magento indexer:set-mode realtime
```

### Background Processing

For non-blocking imports:
```bash
# Start queue consumer
bin/magento queue:consumers:start archivedotorg.import.job.consumer &

# Check consumer status
bin/magento queue:consumers:list
```

## Log Files

| Log | Location | Purpose |
|-----|----------|---------|
| CLI commands | `var/log/archivedotorg.log` | All import operations |
| Batch script | `var/log/import-all-artists-*.log` | bin/import-all-artists output |
| Download progress | `var/archivedotorg/download_progress.json` | Resume state |
| Populate progress | `var/archivedotorg/populate_progress.json` | Resume state |

## Collection ID Examples

| Artist | Collection ID | Notes |
|--------|---------------|-------|
| Grateful Dead | GratefulDead | 2,000+ shows |
| Phish | Phish | 1,000+ shows |
| STS9 | STS9 | 700+ shows |
| Widespread Panic | WidespreadPanic | 600+ shows |
| Lettuce | Lettuce | 400+ shows |
| String Cheese | StringCheeseIncident | 500+ shows |
| moe. | moe | 800+ shows |

## Reference

See main `CLAUDE.md` for:
- Current import status (17/35 artists done)
- Full artist list
- Docker setup

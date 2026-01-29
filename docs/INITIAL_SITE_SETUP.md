# Initial Site Setup - Import All Artists

## Overview

Use the `bin/import-all-artists` script to populate the entire site with all configured artists for initial setup.

**What it does:**
- Loops through all 35 artist YAML configurations
- Downloads metadata from Archive.org for each artist
- Populates products with track matching
- Tracks progress and logs all operations
- Auto-updates artist statistics
- Reindexes at the end

---

## Quick Start

### Full Import (All Shows)
```bash
bin/import-all-artists
```
**Time estimate:** 2-4 hours for all 35 artists
**Disk space needed:** ~5-10 GB for metadata cache

### Test Run (Limited Shows)
```bash
# Import first 10 shows per artist
bin/import-all-artists --limit=10
```
**Time estimate:** 30-60 minutes
**Recommended for:** Testing, development

### Continue on Errors
```bash
# Don't stop if one artist fails
bin/import-all-artists --continue-on-error
```

### Combined
```bash
# Test run that continues on errors
bin/import-all-artists --limit=10 --continue-on-error
```

---

## What to Expect

### Per Artist (with --limit=10)
- **Download:** 10-30 seconds (depends on collection size)
- **Populate:** 5-20 seconds (depends on track matching complexity)
- **Total:** ~15-50 seconds per artist

### Full Import Estimates

| Artists | Shows Each | Total Shows | Est. Duration |
|---------|------------|-------------|---------------|
| 35 | 10 | 350 | ~30 mins |
| 35 | 50 | 1,750 | ~2 hours |
| 35 | 100 | 3,500 | ~3 hours |
| 35 | All | ~50,000+ | ~4-6 hours |

**Note:** Large collections (Grateful Dead, Phish) will take longer.

---

## Progress Tracking

### Real-Time Monitoring

The script outputs progress as it runs:
```
========================================
[1/35] Processing: Billy Strings
========================================
Step 1/2: Downloading metadata...
✓ Download complete
Step 2/2: Populating products...
✓ Populate complete

[2/35] Processing: Cabinet
========================================
...
```

### Monitor Import History

While running, check the admin panel:
- **Admin:** Catalog > Archive.org > Import History
- Watch imports appear in real-time
- See duration, memory, items for each

### Check Logs

All output is logged to:
```
var/log/import-all-artists-YYYYMMDD-HHMMSS.log
```

Tail the log in another terminal:
```bash
tail -f var/log/import-all-artists-*.log
```

---

## After Import

### 1. Verify Import History
```bash
bin/mysql -e "SELECT
  COUNT(*) as total_imports,
  COUNT(DISTINCT artist_name) as artists_imported,
  SUM(items_successful) as total_tracks,
  SUM(duration_seconds) as total_time_secs
FROM archivedotorg_import_run;"
```

### 2. Check Artist Stats
```bash
bin/mysql -e "SELECT
  artist_name,
  downloaded_shows,
  imported_tracks,
  match_rate_percent
FROM archivedotorg_artist_status
ORDER BY imported_tracks DESC
LIMIT 10;"
```

### 3. Visit Frontend
```bash
cd frontend && npm run dev
# Visit: http://localhost:3001
```

### 4. Browse Artists
- http://localhost:3001/artists/phish
- http://localhost:3001/artists/grateful-dead
- http://localhost:3001/artists/sts9
- http://localhost:3001/artists/widespread-panic

---

## Troubleshooting

### Script Stops on Error

**Problem:** One artist fails and stops entire import

**Solution:** Use `--continue-on-error`:
```bash
bin/import-all-artists --limit=10 --continue-on-error
```

### Out of Memory

**Problem:** Populate fails with memory errors

**Solution:** Increase PHP memory limit:
```bash
# In env/phpfpm.env, increase:
PHP_MEMORY_LIMIT=2G  # Default is 1G
bin/restart
```

### Takes Too Long

**Problem:** Full import takes many hours

**Solution:** Run overnight or use smaller limit:
```bash
# Import 20 shows per artist (good balance)
bin/import-all-artists --limit=20
```

Or split into batches:
```bash
# Create custom script for specific artists
bin/magento archive:download "Phish" --limit=100
bin/magento archive:populate "Phish"
# Repeat for top priority artists first
```

### Disk Space Issues

**Problem:** Running out of disk space

**Solution:** Clean up old metadata:
```bash
# Check current cache size
du -sh var/archivedotorg/metadata/

# Clean specific artist
rm -rf var/archivedotorg/metadata/artists/[artist-name]/

# Or clean all (re-download needed)
bin/magento archive:cleanup:cache
```

---

## Performance Optimization

### Run in Parallel (Advanced)

For faster imports, run multiple artists in parallel:

```bash
# Start 3 parallel imports
bin/magento archive:download "Phish" --limit=50 &
bin/magento archive:download "Grateful Dead" --limit=50 &
bin/magento archive:download "STS9" --limit=50 &
wait

# Then populate all
bin/magento archive:populate "Phish" &
bin/magento archive:populate "Grateful Dead" &
bin/magento archive:populate "STS9" &
wait
```

**Warning:** Parallel imports increase memory usage and database load.

### Prioritize Artists

Import most important artists first:

1. **Tier 1** (High Priority): Phish, Grateful Dead, Widespread Panic
2. **Tier 2** (Medium): STS9, String Cheese, Umphrey's McGee
3. **Tier 3** (Lower): Smaller collections

---

## Example Output

```
========================================
  Import All Artists - Full Site Setup
========================================

Limit per artist: 10
Continue on error: false

Found 35 artists configured

Logging to: var/log/import-all-artists-20260129-143022.log

========================================
[1/35] Processing: Billy Strings
========================================
Step 1/2: Downloading metadata...
✓ Download complete
Step 2/2: Populating products...
✓ Populate complete

[2/35] Processing: Cabinet
========================================
...

========================================
         IMPORT SUMMARY
========================================

Successful: 34
Failed: 1
Skipped: 0
Total: 35

Duration: 0h 45m 23s
Log file: var/log/import-all-artists-20260129-143022.log

Successful Artists:
  ✓ Billy Strings
  ✓ Cabinet
  ✓ Dogs in a Pile
  ... (31 more)

Failed Artists:
  ✗ Warren Zevon (populate)

Check log file for details: var/log/import-all-artists-20260129-143022.log

Running final reindex...
✓ Reindex complete

========================================
  Import Complete!
========================================

Visit: http://localhost:3001
Admin: Catalog > Archive.org > Import History
```

---

## Recommended Workflow

### First Time Setup

```bash
# 1. Test with small limit first
bin/import-all-artists --limit=5

# 2. Verify in admin and frontend
# Admin: Check Import History
# Frontend: Browse a few artists

# 3. If looks good, run full import
bin/import-all-artists --limit=50 --continue-on-error

# 4. Let it run (grab coffee, lunch, etc.)

# 5. When complete, check summary
# Review var/log/import-all-artists-*.log for any failures

# 6. Re-run failed artists manually if needed
bin/magento archive:download "Artist Name" --limit=50 --force
bin/magento archive:populate "Artist Name"
```

### Incremental Updates (Later)

```bash
# Update all artists with new shows (monthly)
bin/import-all-artists --incremental

# Or just download new shows
bin/magento archive:download "Phish" --incremental
```

---

## What Gets Tracked

Every import is logged to Import History with:
- ✅ UUID (unique identifier)
- ✅ Artist name
- ✅ Command (download/populate)
- ✅ Started by (cli:app or admin:username)
- ✅ Duration (seconds)
- ✅ Memory (MB)
- ✅ Items processed/successful
- ✅ Status (completed/failed)

**View in Admin:** Catalog > Archive.org > Import History

---

## Expected Results

After full import (--limit=50 per artist):

- **Artists:** 35 fully populated
- **Shows:** ~1,750 shows total
- **Tracks:** ~50,000-100,000 tracks
- **Import Runs:** 70 records (35 download + 35 populate)
- **Database Size:** ~2-5 GB
- **Cache Size:** ~5-10 GB

---

## Tips

1. **Start small:** Use `--limit=10` first to test
2. **Monitor progress:** Keep admin Import History open
3. **Check logs:** Tail the log file to watch progress
4. **Run overnight:** Full imports take hours
5. **Backup first:** Snapshot database before full import
6. **Watch memory:** Monitor with `docker stats` if using Docker

---

## Alternative: Import Specific Artists

If you only want certain artists:

```bash
# Create custom list
ARTISTS=("Phish" "Grateful Dead" "STS9" "Widespread Panic")

for artist in "${ARTISTS[@]}"; do
    echo "Importing: $artist"
    bin/magento archive:download "$artist" --limit=50
    bin/magento archive:populate "$artist"
done

bin/magento indexer:reindex
```

---

**Created:** 2026-01-29
**For:** Initial site setup and bulk artist imports
**See also:** `docs/COMMAND_GUIDE.md` for individual command usage

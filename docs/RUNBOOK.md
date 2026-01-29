# Archive.org Import System - Production Runbook

**Version:** 2.0
**Last Updated:** 2026-01-29
**System:** Archive.org Concert Import Rearchitecture
**Environment:** Production

---

## Table of Contents

1. [Quick Reference](#quick-reference)
2. [System Architecture](#system-architecture)
3. [Common Operations](#common-operations)
4. [Troubleshooting Guide](#troubleshooting-guide)
5. [Emergency Procedures](#emergency-procedures)
6. [Performance Tuning](#performance-tuning)
7. [Monitoring & Alerts](#monitoring--alerts)
8. [Maintenance Windows](#maintenance-windows)

---

## Quick Reference

### Critical Commands

```bash
# Check system status
bin/magento archivedotorg:status

# View active imports
bin/mysql -e "SELECT * FROM archivedotorg_import_run WHERE status='running';"

# Check locks
ls -la var/locks/archivedotorg/

# Clear stuck lock (CAUTION)
rm var/locks/archivedotorg/download_{artist}.lock

# View recent errors
tail -f var/log/archivedotorg.log
tail -f var/log/exception.log | grep -i archivedotorg

# Clear all caches
bin/magento cache:flush
bin/rs cache-flush
```

### Emergency Contacts

- **Developer:** Chris Majorossy
- **Documentation:** `/docs/DEVELOPER_GUIDE.md`, `/docs/ADMIN_GUIDE.md`
- **GitHub Issues:** https://github.com/8pm-archive/issues

---

## System Architecture

### Data Flow

```
Archive.org API
    ↓
MetadataDownloader (JSON files)
    ↓
var/archivedotorg/metadata/{Artist}/{identifier}.json
    ↓
TrackPopulatorService
    ↓
TrackMatcherService (hybrid algorithm)
    ↓
Magento Products (EAV)
```

### Key Components

| Component | Purpose | Location |
|-----------|---------|----------|
| **MetadataDownloader** | Fetch JSON from Archive.org | `Model/MetadataDownloader.php` |
| **TrackMatcherService** | Match tracks to catalog | `Model/TrackMatcherService.php` |
| **TrackPopulatorService** | Parse JSON, create products | `Model/TrackPopulatorService.php` |
| **LockService** | Prevent concurrent imports | `Model/LockService.php` |
| **ProgressTracker** | Track progress with Redis | `Model/Redis/ProgressTracker.php` |

### Database Tables

```
archivedotorg_artist              - Artist configuration
archivedotorg_artist_status       - Import statistics
archivedotorg_import_run          - Import history
archivedotorg_show_metadata       - Show-level metadata
archivedotorg_unmatched_track     - Tracks needing resolution
archivedotorg_daily_metrics       - Dashboard metrics
archivedotorg_activity_log        - Operational log
archivedotorg_studio_albums       - Album artwork cache
archivedotorg_artwork_overrides   - Manual artwork URLs
```

---

## Common Operations

### 1. Import New Shows for an Artist

**Standard workflow:**

```bash
# Step 1: Download metadata
bin/magento archivedotorg:download "Phish" --limit=50

# Step 2: Populate products
bin/magento archivedotorg:populate "Phish"

# Step 3: Check for unmatched tracks
bin/magento archivedotorg:show-unmatched "Phish"
```

**Expected output:**
```
Download: 50/50 shows processed (2-3 minutes)
Populate: 600-800 tracks matched, 5-10 unmatched (5-10 minutes)
```

**Troubleshooting:**
- If download hangs: Check Archive.org API status
- If populate fails: Check `var/log/archivedotorg.log` for matching errors
- If too many unmatched: Update YAML aliases in `config/artists/{artist}.yaml`

---

### 2. Resolve Unmatched Tracks

**Problem:** Tracks not matching catalog (misspellings, variants)

**Solution:**

```bash
# 1. Find unmatched tracks
bin/magento archivedotorg:show-unmatched "Lettuce"

# Output:
# Twezer -> Tweezer (metaphone suggestion)
# Phillis -> Phyllis (metaphone suggestion)

# 2. Edit YAML config
vim src/app/code/ArchiveDotOrg/Core/config/artists/lettuce.yaml

# 3. Add aliases
tracks:
  - key: "tweezer"
    name: "Tweezer"
    aliases: ["twezer", "twezer", "tweezer reprise"]

# 4. Re-run populate
bin/magento archivedotorg:populate "Lettuce"
```

**Success criteria:**
- Match rate increases to >95%
- Unmatched count decreases
- No false positives

---

### 3. Restart Failed Import

**Scenario:** Import crashed mid-process

**Recovery steps:**

```bash
# 1. Check for stuck locks
ls -la var/locks/archivedotorg/

# 2. Identify the lock file
# download_phish.lock (if download failed)
# populate_phish.lock (if populate failed)

# 3. Check if process is actually running
ps aux | grep magento | grep archivedotorg

# 4. If no process running, remove lock
rm var/locks/archivedotorg/download_phish.lock

# 5. Check progress file
cat var/archivedotorg/progress/download_phish.json

# 6. Re-run command (will resume from checkpoint)
bin/magento archivedotorg:download "Phish" --limit=500
```

**Progress tracking:**
- Downloads save progress every 10 shows
- Populate saves progress every 50 products
- Re-running will skip completed items

---

### 4. Monitor Active Imports

**Real-time monitoring:**

```bash
# Check active imports
bin/mysql -e "SELECT
    artist_id,
    command,
    status,
    shows_processed,
    tracks_processed,
    started_at,
    TIMESTAMPDIFF(MINUTE, started_at, NOW()) as duration_min
FROM archivedotorg_import_run
WHERE status = 'running'
ORDER BY started_at DESC;"

# Check Redis progress (if enabled)
redis-cli GET archivedotorg:progress:phish:current
redis-cli GET archivedotorg:progress:phish:total

# Watch logs
tail -f var/log/archivedotorg.log | grep "Processed:"
```

**Dashboard:**
- Admin → Content → Archive.org Import → Dashboard
- Real-time progress bars
- ETA calculations

---

## Troubleshooting Guide

### Error 1: Lock Acquisition Failed

**Error message:**
```
Lock already held for download:phish by process 12345
```

**Cause:** Another process is running for the same artist

**Solution:**

```bash
# Check if process is alive
ps aux | grep 12345

# If process exists, wait for it to finish
# If process dead (stale lock):
rm var/locks/archivedotorg/download_phish.lock
```

**Prevention:**
- Don't run concurrent imports for same artist
- Use `--force` flag only if certain no other process running

---

### Error 2: Archive.org API Timeout

**Error message:**
```
cURL error 28: Operation timed out after 30000 milliseconds
```

**Cause:** Archive.org API slow or unreachable

**Solution:**

```bash
# Test Archive.org connectivity
curl -I "https://archive.org/metadata/gd1977-05-08.sbd.miller.110987.flac16"

# If slow, increase timeout in config
bin/magento config:set archivedotorg/api/timeout 60

# Or wait and retry later
```

**Auto-retry:**
- MetadataDownloader retries 3 times with exponential backoff
- Progress is saved, so re-running is safe

---

### Error 3: Out of Memory

**Error message:**
```
Fatal error: Allowed memory size of 536870912 bytes exhausted
```

**Cause:** Processing too many products at once

**Solution:**

```bash
# Reduce batch size
bin/magento archivedotorg:populate "GratefulDead" --limit=100

# Or increase PHP memory
echo "memory_limit = 1G" >> src/php.ini
bin/restart
```

**Typical memory usage:**
- Download: 50-100 MB per 100 shows
- Populate: 100-200 MB per 1000 products

---

### Error 4: Database Deadlock

**Error message:**
```
SQLSTATE[40001]: Serialization failure: 1213 Deadlock found
```

**Cause:** Concurrent product updates conflicting

**Solution:**

```bash
# Don't run concurrent populate for overlapping artists
# Wait for one to finish, then run the next

# If deadlock persists, check for slow queries
bin/mysql -e "SHOW PROCESSLIST;"

# Kill long-running queries
bin/mysql -e "KILL <process_id>;"
```

---

### Error 5: Missing Metadata Files

**Error message:**
```
Metadata file not found: var/archivedotorg/metadata/Phish/ph2024-01-01.json
```

**Cause:** File deleted or folder migration incomplete

**Solution:**

```bash
# Re-download specific show
bin/magento archivedotorg:download "Phish" --show=ph2024-01-01

# Or full re-sync
bin/magento archivedotorg:download "Phish" --limit=1000
```

---

### Error 6: Metaphone Collision (Too Many Matches)

**Error message:**
```
Multiple metaphone matches for "The Flu": the_flu, the_flue, the_flew
```

**Cause:** Similar-sounding track names

**Solution:**

```bash
# Add exact aliases to YAML
vim config/artists/lettuce.yaml

tracks:
  - key: "the_flu"
    name: "The Flu"
    aliases: ["the flue"]  # Explicit mapping
  - key: "the_flew"
    name: "The Flew"
    aliases: []

# Re-run populate
bin/magento archivedotorg:populate "Lettuce"
```

---

### Error 7: JSON Parse Error

**Error message:**
```
Invalid JSON in file: var/archivedotorg/metadata/Phish/ph2024-01-01.json
```

**Cause:** Corrupted download or Archive.org API issue

**Solution:**

```bash
# Delete corrupted file
rm var/archivedotorg/metadata/Phish/ph2024-01-01.json

# Re-download
bin/magento archivedotorg:download "Phish" --show=ph2024-01-01

# Verify JSON is valid
cat var/archivedotorg/metadata/Phish/ph2024-01-01.json | jq .
```

---

### Error 8: Duplicate Category Created

**Error message:**
```
Category "Lettuce / 2024 / January" already exists with different ID
```

**Cause:** Race condition in category creation (fixed in rearchitecture)

**Solution:**

```bash
# Find duplicate categories
bin/mysql -e "SELECT entity_id, value, parent_id
FROM catalog_category_entity_varchar
WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 3)
AND value LIKE 'Lettuce%';"

# Merge products to correct category
bin/magento catalog:product:move --from=<wrong_id> --to=<correct_id>

# Delete duplicate
bin/magento catalog:category:delete <wrong_id>
```

**Prevention:**
- Use CategoryService::createIfNotExists (rearchitecture)
- Avoid concurrent imports for same artist

---

## Emergency Procedures

### Emergency Stop: Kill All Imports

**When to use:** System overloaded, need immediate shutdown

```bash
# Find all import processes
ps aux | grep "archivedotorg:" | grep -v grep

# Kill all
ps aux | grep "archivedotorg:" | grep -v grep | awk '{print $2}' | xargs kill

# Clear all locks
rm -f var/locks/archivedotorg/*.lock

# Update database status
bin/mysql -e "UPDATE archivedotorg_import_run
SET status = 'failed', error_message = 'Manually killed'
WHERE status = 'running';"
```

---

### Emergency Rollback: Revert to Previous Version

**When to use:** Critical bug in new deployment

```bash
# 1. Enable maintenance mode
bin/magento maintenance:enable

# 2. Restore database backup
mysql magento < backup_20260128_120000.sql

# 3. Checkout previous code version
git checkout <previous_commit_hash>

# 4. Rebuild
bin/magento setup:di:compile
bin/magento cache:flush

# 5. Disable maintenance
bin/magento maintenance:disable
```

**Backup locations:**
- Database: `backups/db/`
- Metadata: `var/archivedotorg/metadata.backup.YYYYMMDD/`
- Code: Git tags `v1.x.x`

---

### Emergency Scale-Down: Reduce Load

**When to use:** Database overloaded, imports consuming too many resources

```bash
# 1. Stop all active imports
ps aux | grep "archivedotorg:" | awk '{print $2}' | xargs kill

# 2. Reduce batch sizes
bin/magento config:set archivedotorg/import/batch_size 50

# 3. Restart imports with limits
bin/magento archivedotorg:download "Phish" --limit=10
bin/magento archivedotorg:populate "Phish" --limit=100

# 4. Monitor resources
watch -n 5 "mysqladmin processlist && echo '---' && free -h"
```

---

## Performance Tuning

### Optimize Database Queries

**Problem:** Dashboard slow to load (>1 second)

**Solution:**

```bash
# Check for missing indexes
bin/mysql -e "EXPLAIN SELECT * FROM archivedotorg_import_run
WHERE artist_id = 1 AND status = 'completed'
ORDER BY started_at DESC LIMIT 20;"

# Should show "Using index" in Extra column

# Add index if missing
bin/mysql -e "CREATE INDEX idx_artist_status_started
ON archivedotorg_import_run (artist_id, status, started_at DESC);"
```

**Expected performance:**
- Artist grid: <100ms
- Import history: <100ms
- Unmatched tracks: <100ms

---

### Optimize Matching Performance

**Problem:** Populate taking >30 minutes for 1000 products

**Current performance (tested):**
- Exact match: 0.01ms per track
- Metaphone match: 0.44ms per track (50k tracks)
- Memory: 102.5 MB peak

**If slower:**

```bash
# Run benchmark
bin/magento archivedotorg:benchmark-matching --tracks=10000

# Expected:
# Exact: <100ms
# Metaphone: <500ms

# If slower, check:
# 1. OPcache enabled
php -i | grep opcache.enable

# 2. No debug mode
bin/magento deploy:mode:show
# Should be "production"

# 3. Indexes built
# Indexes are built automatically on first match
```

---

### Reduce Disk Usage

**Problem:** Metadata files consuming too much disk space

```bash
# Check current usage
du -sh var/archivedotorg/metadata/

# Clean old progress files (30+ days)
find var/archivedotorg/progress -name "*.json" -mtime +30 -delete

# Archive old metadata (90+ days since import)
bin/magento archivedotorg:archive-old-metadata --days=90
```

**Typical sizes:**
- 1000 shows: ~100-200 MB metadata
- 186,000 products: ~2-3 GB metadata

---

### Optimize Import Speed

**Current performance:**
- Download: 50 shows = 2-3 minutes
- Populate: 1000 products = 5-10 minutes

**To improve:**

```bash
# Use bulk importer (10x faster)
bin/magento archivedotorg:populate "Phish" --use-bulk

# Increase batch size (if memory allows)
bin/magento config:set archivedotorg/import/batch_size 200

# Disable unnecessary indexers during import
bin/magento indexer:set-mode schedule catalog_product_attribute
bin/magento indexer:set-mode schedule catalogsearch_fulltext
```

---

## Monitoring & Alerts

### Key Metrics to Track

**Daily:**
- [ ] Import success rate (target: >95%)
- [ ] Match rate (target: >97%)
- [ ] Unmatched tracks count (target: <100 total)
- [ ] Average import duration (baseline: 5-10 min per 1000 products)

**Weekly:**
- [ ] Disk usage growth rate
- [ ] Database size
- [ ] Failed imports (target: <5%)

**Monthly:**
- [ ] Total products imported
- [ ] Artist coverage (target: 35 artists)

---

### Dashboard Queries

```bash
# Import success rate (last 7 days)
bin/mysql -e "SELECT
    COUNT(*) as total_imports,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    ROUND(100.0 * SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*), 2) as success_rate
FROM archivedotorg_import_run
WHERE started_at >= NOW() - INTERVAL 7 DAY;"

# Match rate by artist
bin/mysql -e "SELECT
    a.artist_name,
    s.tracks_matched,
    s.tracks_unmatched,
    s.match_rate
FROM archivedotorg_artist_status s
JOIN archivedotorg_artist a ON a.artist_id = s.artist_id
ORDER BY s.match_rate ASC
LIMIT 10;"

# Unmatched tracks (needs attention)
bin/mysql -e "SELECT
    track_name,
    occurrences,
    suggested_match
FROM archivedotorg_unmatched_track
WHERE resolved = 0
ORDER BY occurrences DESC
LIMIT 20;"
```

---

### Log Locations

```bash
# Application logs
var/log/archivedotorg.log          # Import operations
var/log/exception.log              # PHP exceptions
var/log/system.log                 # General Magento

# Database logs
var/log/db.log                     # Slow queries (if enabled)

# Web server logs
var/log/access.log                 # HTTP requests
var/log/error.log                  # PHP-FPM errors
```

---

### Setting Up Alerts (Optional)

**Email alerts for failures:**

```bash
# Create cron job
crontab -e

# Add:
*/30 * * * * /path/to/check_failed_imports.sh

# check_failed_imports.sh:
#!/bin/bash
FAILED=$(mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='failed' AND started_at >= NOW() - INTERVAL 1 HOUR;")
if [ "$FAILED" -gt 0 ]; then
    echo "Failed imports detected: $FAILED" | mail -s "Import Alert" admin@example.com
fi
```

---

## Maintenance Windows

### Recommended Schedule

**Daily (automated):**
- 4:00 AM - Aggregate daily metrics (cron)
- 4:30 AM - Clean old progress files (cron)

**Weekly:**
- Sunday 2:00 AM - Database backup
- Sunday 3:00 AM - Metadata backup

**Monthly:**
- Archive old metadata (90+ days)
- Review unmatched tracks
- Update artist YAML configs

---

### Pre-Maintenance Checklist

```bash
# 1. Notify users
bin/magento maintenance:enable

# 2. Backup database
mysqldump magento > backup_$(date +%Y%m%d_%H%M%S).sql

# 3. Backup metadata
cp -r var/archivedotorg/metadata var/archivedotorg/metadata.backup.$(date +%Y%m%d)

# 4. Stop active imports
ps aux | grep "archivedotorg:" | awk '{print $2}' | xargs kill

# 5. Verify no locks
ls -la var/locks/archivedotorg/
```

---

### Post-Maintenance Checklist

```bash
# 1. Verify database
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"

# 2. Test import
bin/magento archivedotorg:status

# 3. Check logs
tail -100 var/log/exception.log

# 4. Re-enable site
bin/magento maintenance:disable

# 5. Monitor for 15 minutes
watch -n 10 "tail -20 var/log/archivedotorg.log"
```

---

## Appendix: System Health Check

**Run weekly to verify system health:**

```bash
#!/bin/bash
# health_check.sh

echo "=== Archive.org Import System Health Check ==="
echo "Date: $(date)"
echo ""

# Database tables
echo "1. Database Tables:"
mysql -e "SHOW TABLES LIKE 'archivedotorg_%';" | wc -l
echo "   Expected: 9 tables"
echo ""

# Active imports
echo "2. Active Imports:"
mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='running';"
echo ""

# Failed imports (last 24h)
echo "3. Failed Imports (24h):"
mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='failed' AND started_at >= NOW() - INTERVAL 24 HOUR;"
echo ""

# Match rate
echo "4. Overall Match Rate:"
mysql -N -e "SELECT AVG(match_rate) FROM archivedotorg_artist_status;"
echo ""

# Disk usage
echo "5. Metadata Disk Usage:"
du -sh var/archivedotorg/metadata/
echo ""

# Unmatched tracks
echo "6. Unmatched Tracks:"
mysql -N -e "SELECT COUNT(*) FROM archivedotorg_unmatched_track WHERE resolved=0;"
echo ""

# Locks
echo "7. Active Locks:"
ls -la var/locks/archivedotorg/ | wc -l
echo ""

echo "=== Health Check Complete ==="
```

---

## Revision History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-15 | Initial runbook (pre-rearchitecture) |
| 2.0 | 2026-01-29 | Updated for rearchitecture (Phase 7.B) |

---

**End of Runbook**

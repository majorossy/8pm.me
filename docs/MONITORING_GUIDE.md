# 7-Day Post-Deployment Monitoring Guide

**Purpose:** Track system health and performance after Phase 7 deployment
**Duration:** 7 days minimum
**Frequency:** Daily checks
**Tool:** `deployment/monitor-deployment.sh`

---

## Overview

The monitoring script (`monitor-deployment.sh`) runs daily health checks with different focus areas each day. It tracks 7 key metrics and logs results for trending analysis.

---

## How to Run

```bash
# Day 1 (Deployment Day)
./deployment/monitor-deployment.sh 1

# Day 2
./deployment/monitor-deployment.sh 2

# Day 3
./deployment/monitor-deployment.sh 3

# ... continue through Day 7
./deployment/monitor-deployment.sh 7
```

**Logs saved to:** `var/log/deployment_monitoring/monitoring_dayN_TIMESTAMP.txt`

---

## What Gets Monitored

### 1. Error Logs 📋
**Checks:** `var/log/exception.log` for Archive.org related errors

**Healthy:**
```
✅ No exceptions in last 24 hours
```

**Unhealthy:**
```
⚠️  5 exceptions found in last 24 hours:
- Lock acquisition failed (3 occurrences)
- API timeout (2 occurrences)
```

**Action if unhealthy:**
```bash
# View full error log
tail -100 var/log/exception.log

# Filter for Archive.org errors
tail -500 var/log/exception.log | grep -i archivedotorg

# Check specific error
bin/magento archive:status
```

---

### 2. Import Success Rate 📊
**Checks:** `archivedotorg_import_run` table for success/failure counts

**Healthy:**
```
Total imports: 15
  Completed:   14 (93%)
  Failed:      1 (7%)
  Running:     0
```

**Unhealthy:**
```
⚠️  WARNING: Success rate below 95%
Total imports: 20
  Completed:   16 (80%)
  Failed:      4 (20%)
```

**Action if unhealthy:**
```bash
# Check failed imports
bin/mysql -e "SELECT artist_id, command, error_message, started_at
FROM archivedotorg_import_run
WHERE status='failed'
ORDER BY started_at DESC
LIMIT 10;"

# Retry failed import
bin/magento archive:download "Artist" --force --limit=10
```

---

### 3. Match Rate 🎯
**Checks:** Average match rate across all artists

**Healthy:**
```
Average match rate: 98.2%
✅ All artists above 95% threshold

Top performers:
- Phish: 99.1%
- Grateful Dead: 98.8%
- STS9: 97.9%
```

**Unhealthy:**
```
⚠️  WARNING: 2 artists below 95% threshold

Low performers:
- New Artist: 89.2% (needs aliases)
- Test Band: 91.5% (needs review)
```

**Action if unhealthy:**
```bash
# Check unmatched tracks
bin/magento archive:show-unmatched "New Artist"

# Add aliases to YAML
vim src/app/code/ArchiveDotOrg/Core/config/artists/new-artist.yaml

# Re-populate with fixes
bin/magento archive:populate "New Artist"
```

---

### 4. Unmatched Tracks 🔍
**Checks:** Total count of unresolved unmatched tracks

**Healthy:**
```
Total unmatched tracks: 42
✅ Below 100 track threshold

Top offenders:
- "Twezer" (5 occurrences) → Suggested: "Tweezer"
- "YEM" (3 occurrences) → Suggested: "You Enjoy Myself"
```

**Unhealthy:**
```
⚠️  WARNING: 127 unmatched tracks (above 100 threshold)

Top offenders:
- "Unknown Track" (45 occurrences)
- "Soundcheck" (32 occurrences)
- Various misspellings (50 occurrences)
```

**Action if unhealthy:**
```bash
# Export to CSV for review
bin/magento archive:show-unmatched --all --export=unmatched.csv

# Review top offenders
bin/magento archive:show-unmatched "Artist" | head -20

# Bulk fix aliases in YAML
# Add common misspellings to track aliases
```

---

### 5. Dashboard Performance ⚡
**Checks:** Query execution time for key dashboard queries

**Healthy:**
```
Artist status query: 85ms ✅
Import history query: 72ms ✅
Unmatched tracks query: 54ms ✅
```

**Unhealthy (Cold Cache):**
```
⚠️  WARNING: Queries slower than 100ms target
Artist status query: 160ms
Import history query: 171ms

Note: First query slower due to cold cache
```

**Unhealthy (Warm Cache):**
```
⚠️  CRITICAL: Queries consistently slow
Artist status query: 250ms
Import history query: 320ms
```

**Action if unhealthy:**
```bash
# Check indexes
bin/mysql -e "SHOW INDEX FROM archivedotorg_import_run;"

# Analyze query performance
bin/mysql -e "EXPLAIN SELECT * FROM archivedotorg_import_run
WHERE artist_id = 1 AND status = 'completed'
ORDER BY started_at DESC LIMIT 20;"

# Rebuild indexes if needed
bin/magento indexer:reindex

# Clear query cache
bin/magento cache:flush
```

---

### 6. Disk Usage 💾
**Checks:** Metadata, progress, and log folder sizes

**Healthy:**
```
Metadata folder:  2.3 GB
Progress folder:  512 KB
Log folder:       128 MB

✅ Below 10 GB threshold
```

**Unhealthy:**
```
⚠️  WARNING: High disk usage
Metadata folder:  8.7 GB (approaching limit)
Progress folder:  2.1 MB
Log folder:       1.2 GB (needs rotation)
```

**Action if unhealthy:**
```bash
# Clean old metadata cache (90+ days)
bin/magento archive:cleanup:cache --days=90

# Clean old progress files
find var/archivedotorg/progress -name "*.json" -mtime +30 -delete

# Rotate large logs
mv var/log/archivedotorg.log var/log/archivedotorg.log.old
touch var/log/archivedotorg.log

# Check disk space
df -h
```

---

### 7. System Resources 🖥️
**Checks:** Active processes, memory, CPU

**Healthy:**
```
Active import processes: 1
Memory usage: 512 MB / 4 GB (12%)
CPU usage: 15%
```

**Unhealthy:**
```
⚠️  WARNING: High resource usage
Active import processes: 5 (too many concurrent)
Memory usage: 3.2 GB / 4 GB (80%)
CPU usage: 85%
```

**Action if unhealthy:**
```bash
# Check running processes
ps aux | grep archivedotorg

# Kill stuck processes
ps aux | grep "archive:" | awk '{print $2}' | xargs kill

# Clear stuck locks
rm -f var/locks/archivedotorg/*.lock

# Check memory leaks
bin/magento archive:benchmark-matching --tracks=50000
# Monitor memory usage during benchmark
```

---

## Daily Focus Areas

### Day 1: Error Monitoring
**Focus:** Catch immediate post-deployment issues
**Frequency:** Check error logs every 30 minutes
**Actions:**
- Monitor exception.log continuously
- Watch active imports in real-time
- Verify no stuck processes
- Check for lock issues

---

### Day 2: Dashboard Performance
**Focus:** Verify dashboard queries are fast
**Actions:**
- Access dashboard multiple times (warm cache)
- Time each grid load
- Verify <100ms target met
- Check for slow queries in DB

**Expected:**
- First load: 150-200ms (cold cache)
- Subsequent loads: 50-100ms (warm cache)

---

### Day 3: Match Rate Stability
**Focus:** Ensure track matching is consistent
**Actions:**
- Review match rates for all artists
- Check for degradation
- Verify unmatched count stable
- Test with new imports

---

### Day 4: Memory Leak Detection
**Focus:** Verify no memory leaks during imports
**Actions:**
- Run long import (500+ shows)
- Monitor memory usage throughout
- Check for gradual memory increase
- Verify memory released after completion

```bash
# Watch memory during import
watch -n 5 "ps aux | grep magento | grep archive:"

# Run benchmark to stress test
bin/magento archive:benchmark-matching --tracks=50000
```

---

### Day 5: Cron Job Verification
**Focus:** Ensure scheduled jobs running correctly
**Actions:**
- Verify cron jobs executed
- Check cron logs
- Confirm no missed runs
- Validate scheduled import results

```bash
# Check cron status
bin/magento cron:install --force
crontab -l

# View cron logs
tail -100 var/log/cron.log

# Check scheduled job history
bin/mysql -e "SELECT * FROM cron_schedule
WHERE job_code LIKE 'archivedotorg%'
ORDER BY scheduled_at DESC LIMIT 10;"
```

---

### Day 6: Disk Space Monitoring
**Focus:** Track disk usage growth rate
**Actions:**
- Calculate daily growth rate
- Project capacity needs
- Plan cleanup schedule
- Verify log rotation working

**Growth Rate Formula:**
```
Daily Growth = (Current Size - Day 1 Size) / 5 days
```

**Example:**
```
Day 1: 2.3 GB
Day 6: 2.8 GB
Growth: 0.5 GB / 5 days = 100 MB/day
Capacity: 10 GB limit = 72 days remaining
```

---

### Day 7: Full System Health Check
**Focus:** Comprehensive review of all metrics
**Actions:**
- Run all benchmark tests
- Generate trending report
- Compare Day 1 vs Day 7
- Document findings
- Plan next 30 days

**Final Report Checklist:**
- [ ] Error rate <1%
- [ ] Import success rate >95%
- [ ] Match rate >97%
- [ ] Dashboard performance <100ms
- [ ] No memory leaks detected
- [ ] Disk usage sustainable
- [ ] No stuck processes

---

## Trending Analysis

Track metrics over 7 days to identify trends:

```bash
# Generate trend report
cat var/log/deployment_monitoring/monitoring_day*.txt | \
grep -E "(Total imports|Average match rate|Dashboard performance)" > trends.txt

# Example output:
Day 1: Imports: 5, Match Rate: 97.2%, Dashboard: 160ms
Day 2: Imports: 12, Match Rate: 97.8%, Dashboard: 85ms
Day 3: Imports: 18, Match Rate: 98.1%, Dashboard: 72ms
Day 4: Imports: 25, Match Rate: 98.3%, Dashboard: 68ms
Day 5: Imports: 30, Match Rate: 98.5%, Dashboard: 71ms
Day 6: Imports: 35, Match Rate: 98.6%, Dashboard: 69ms
Day 7: Imports: 42, Match Rate: 98.7%, Dashboard: 73ms
```

**Good Trends:**
- ✅ Match rate increasing (aliases being added)
- ✅ Dashboard performance stable <100ms
- ✅ Import count growing (system being used)

**Bad Trends:**
- ⚠️ Match rate decreasing (new artists not configured)
- ⚠️ Dashboard performance degrading (index issues)
- ⚠️ Error rate increasing (stability problem)

---

## Success Criteria (7-Day Review)

After 7 days, system should meet these criteria:

| Metric | Target | Pass/Fail |
|--------|--------|-----------|
| **Import success rate** | >95% | |
| **Average match rate** | >97% | |
| **Dashboard load time** | <100ms (warm) | |
| **Error rate** | <1% | |
| **Zero data loss** | 100% | |
| **No critical bugs** | 0 bugs | |
| **Memory stability** | No leaks | |

**If ALL criteria met:** ✅ System is production-ready, proceed to Phase 5 cleanup (30 days)

**If ANY criteria missed:** ⚠️ Investigate and fix before proceeding

---

## Alerting (Optional)

Set up email alerts for critical issues:

```bash
# Create alert script
cat > /usr/local/bin/archive-alerts.sh <<'EOF'
#!/bin/bash

# Check error rate
ERRORS=$(mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='failed' AND started_at >= NOW() - INTERVAL 1 HOUR;")
if [ "$ERRORS" -gt 5 ]; then
    echo "High error rate: $ERRORS failures in last hour" | mail -s "Archive.org Alert" admin@example.com
fi

# Check disk usage
USAGE=$(du -sm var/archivedotorg/metadata | cut -f1)
if [ "$USAGE" -gt 9000 ]; then
    echo "High disk usage: ${USAGE}MB (approaching 10GB limit)" | mail -s "Archive.org Alert" admin@example.com
fi
EOF

chmod +x /usr/local/bin/archive-alerts.sh

# Add to crontab (every hour)
crontab -e
# Add: 0 * * * * /usr/local/bin/archive-alerts.sh
```

---

## Monitoring Checklist

**Daily (Days 1-7):**
- [ ] Run monitoring script: `./deployment/monitor-deployment.sh N`
- [ ] Review log output
- [ ] Check for warnings/errors
- [ ] Take action on issues
- [ ] Document findings

**After Day 7:**
- [ ] Generate final report
- [ ] Compare metrics to Day 1 baseline
- [ ] Verify all success criteria met
- [ ] Schedule Phase 5 cleanup (30 days out)
- [ ] Plan ongoing monitoring (weekly)

**Ongoing (Weekly):**
- [ ] Run `archive:status`
- [ ] Check disk usage
- [ ] Review error logs
- [ ] Monitor match rates
- [ ] Run `archive:refresh:products` for stats updates

---

## Troubleshooting Common Issues

### Issue: Dashboard Queries Slow (>200ms)

**Diagnosis:**
```bash
# Check if indexes exist
bin/mysql -e "SHOW INDEX FROM archivedotorg_import_run WHERE Key_name LIKE '%artist%';"

# Analyze slow query
bin/mysql -e "EXPLAIN SELECT * FROM archivedotorg_import_run WHERE artist_id = 1 ORDER BY started_at DESC LIMIT 20;"
```

**Fix:**
```bash
# Rebuild indexes
bin/magento indexer:reindex

# Clear caches
bin/magento cache:flush

# Re-run deployment Phase 1 if needed
./deployment/phase1/deploy-database.sh
```

---

### Issue: High Memory Usage During Imports

**Diagnosis:**
```bash
# Monitor memory during import
watch -n 2 "ps aux | grep 'archive:' | awk '{print \$6}'"

# Check for memory leaks
bin/magento archive:benchmark-matching --tracks=50000
```

**Fix:**
```bash
# Reduce batch size
bin/magento archive:populate "Artist" --limit=100

# Or increase PHP memory
echo "memory_limit = 2G" >> src/php.ini
bin/restart
```

---

### Issue: Imports Hanging/Stuck

**Diagnosis:**
```bash
# Check for stuck locks
ls -la var/locks/archivedotorg/

# Check for zombie processes
ps aux | grep "archive:" | grep -v grep
```

**Fix:**
```bash
# Kill stuck process
ps aux | grep "archive:" | awk '{print $2}' | xargs kill

# Remove stuck locks
rm -f var/locks/archivedotorg/*.lock

# Update import run status
bin/mysql -e "UPDATE archivedotorg_import_run SET status='failed', error_message='Manually killed' WHERE status='running';"
```

---

## Weekly Health Check (Post-7-Day)

After the initial 7-day period, run weekly health checks:

```bash
# Create weekly health check script
cat > bin/archive-health-check <<'EOF'
#!/bin/bash
echo "=== Archive.org Weekly Health Check ==="
echo "Date: $(date)"
echo ""

# Status
bin/magento archive:status

# Error count
ERRORS=$(tail -1000 var/log/exception.log | grep -i archivedotorg | wc -l)
echo "Recent errors: $ERRORS"

# Disk usage
echo "Disk usage:"
du -sh var/archivedotorg/*

# Match rates
bin/mysql -e "SELECT artist_name, match_rate FROM archivedotorg_artist_status ORDER BY match_rate ASC LIMIT 5;" | head -10

echo ""
echo "=== Health Check Complete ==="
EOF

chmod +x bin/archive-health-check

# Run weekly
bin/archive-health-check
```

---

**End of Monitoring Guide**

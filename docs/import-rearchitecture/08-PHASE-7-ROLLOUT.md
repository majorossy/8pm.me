# Phase 7: Rollout & Verification

**Timeline:** Week 10
**Status:** ⏸️ Blocked by Phase 6
**Prerequisites:** ALL previous phases complete

---

## Overview

Production deployment with staging validation, incremental rollout, and monitoring.

**Stages:**
1. Pre-production validation in staging
2. Phased production deployment
3. Post-deployment monitoring

**Completion Criteria:**
- [ ] Zero data loss during migration
- [ ] All 35 artists functional
- [ ] Dashboard performance <100ms
- [ ] 7-day monitoring period passed
- [ ] Runbook created

---

## 🟩 P3 - Pre-Production

### Task 7.1: Verify Database Migrations in Staging
**Environment:** Staging (production clone)

```bash
# Run all schema patches
bin/magento setup:upgrade

# Verify tables created
mysql magento -e "SHOW TABLES LIKE 'archivedotorg_%';"
# Expected: 5+ tables

# Verify indexes
mysql magento -e "SHOW INDEX FROM catalog_product_entity WHERE Key_name LIKE 'idx_%';"
# Expected: idx_created_at, idx_archive_*, etc.

# Verify foreign keys
SELECT * FROM information_schema.key_column_usage
WHERE referenced_table_name LIKE 'archivedotorg_%';
```

- [ ] All migrations run without error
- [ ] All tables/indexes created
- [ ] Foreign keys correct
- [ ] Rollback tested

---

### Task 7.2: Test with Production Data Clone

```bash
# 1. Clone production database to staging
mysqldump production_magento | mysql staging_magento

# 2. Run folder migration
bin/magento archive:migrate:organize-folders

# 3. Verify file counts match
find var/archivedotorg/metadata -name "*.json" | wc -l
# Should match production count (~2130)

# 4. Run YAML export
bin/magento archive:migrate:export

# 5. Validate all YAMLs
bin/magento archive:validate --all
# Should report: 35 artists validated, 0 errors

# 6. Test import flow
bin/magento archive:download lettuce --limit=10
bin/magento archive:populate lettuce --limit=10
```

- [ ] Folder migration successful
- [ ] All 35 artists exported to YAML
- [ ] Import flow works with new commands

---

### Task 7.3: Load Test with 100k+ Products

**Test:** Import large artist (GratefulDead or Phish)

```bash
# Monitor resources during import
bin/magento archive:download GratefulDead --limit=500 &
watch -n 1 "ps aux | grep magento | grep -v grep"

# Check memory usage doesn't grow unbounded
# Should stay <512MB throughout

# Check CPU
top -p $(pgrep -f "archive:download")
```

**Metrics to verify:**
- [ ] Memory usage stable (no leaks)
- [ ] CPU usage reasonable (<80%)
- [ ] Import speed consistent (not slowing down)
- [ ] No database deadlocks

---

### Task 7.4: Test Admin Dashboard with Production Data
**With 186k products loaded:**

```bash
# Load dashboard
curl -w "@curl-format.txt" -o /dev/null -s https://admin.example.com/archivedotorg/dashboard

# Target: <100ms total time
```

**Tests:**
- [ ] Dashboard loads <100ms
- [ ] Artist grid loads <200ms
- [ ] History grid loads <200ms
- [ ] Unmatched tracks grid loads <200ms
- [ ] Charts render correctly
- [ ] AJAX polling works with multiple admins

---

## 🟩 P3 - Production Deployment

### Task 7.5: Deploy Phase 1 - Database

**Steps:**
1. Backup production database
2. Enable maintenance mode
3. Run schema migrations
4. Verify tables/indexes
5. Disable maintenance mode

```bash
# 1. Backup
mysqldump magento > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Maintenance mode
bin/magento maintenance:enable

# 3. Run migrations
bin/magento setup:upgrade

# 4. Verify
mysql magento -e "SHOW TABLES LIKE 'archivedotorg_%';"

# 5. End maintenance
bin/magento maintenance:disable
```

**Rollback:**
```bash
mysql magento < backup_YYYYMMDD_HHMMSS.sql
```

- [ ] Backup created
- [ ] Migrations successful
- [ ] Verification passed
- [ ] Downtime <5 minutes

---

### Task 7.6: Deploy Phase 2 - Code

**Steps:**
1. Deploy new code (git pull / deploy script)
2. Clear caches
3. Verify old commands still work (backward compat)
4. Check error logs

```bash
# 1. Deploy
git pull origin main
# or: cap deploy (Capistrano)
# or: deployer deploy (Deployer)

# 2. Clear caches
bin/magento cache:flush
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f

# 3. Test old commands
bin/magento archive:download-metadata --help
# Should show deprecation warning but work

# 4. Check logs
tail -f var/log/exception.log
```

- [ ] Code deployed
- [ ] Caches cleared
- [ ] Old commands work with deprecation warning
- [ ] No errors in logs

---

### Task 7.7: Deploy Phase 3 - Data Migration

**Steps:**
1. Backup current metadata folder
2. Run folder migration
3. Export to YAML
4. Verify all files migrated

```bash
# 1. Backup
cp -r var/archivedotorg/metadata var/archivedotorg/metadata.backup.$(date +%Y%m%d)

# 2. Folder migration
bin/magento archive:migrate:organize-folders

# 3. YAML export
bin/magento archive:migrate:export

# 4. Verify
bin/magento archive:validate --all
ls var/archivedotorg/metadata/
# Should show artist folders
```

- [ ] Backup created
- [ ] Folder migration successful
- [ ] YAML export successful
- [ ] No orphaned files

---

### Task 7.8: Deploy Phase 4 - Admin Dashboard

**Steps:**
1. Enable admin module
2. Clear admin caches
3. Test dashboard loads
4. Train admin users

```bash
# 1-2. Enable and clear
bin/magento module:enable ArchiveDotOrg_Admin
bin/magento cache:flush

# 3. Test
# Navigate to Admin > Content > Archive.org Import > Dashboard
```

- [ ] Module enabled
- [ ] Dashboard accessible
- [ ] Grids work
- [ ] Charts render

---

### Task 7.9: Deploy Phase 5 - Cleanup

**Wait 30 days before this phase** (grace period for rollback)

**Steps:**
1. Delete old data patches
2. Remove deprecated command aliases (optional)
3. Document final migration notes

```bash
# 1. Delete old files
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup*.php

# Clean patch_list
mysql magento -e "DELETE FROM patch_list WHERE patch_name LIKE '%CreateCategoryStructure%';"
mysql magento -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddAdditionalArtists%';"
mysql magento -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddTracksGroup%';"
```

- [ ] 30-day grace period passed
- [ ] Old patches deleted
- [ ] Database cleaned
- [ ] Final backup created

---

## 🟩 P3 - Post-Deployment

### Task 7.10: Monitor for 7 Days

**Daily checks:**
- [ ] Day 1: Check error logs, import success rates
- [ ] Day 2: Check dashboard performance
- [ ] Day 3: Check memory usage trends
- [ ] Day 4: Check match rates
- [ ] Day 5: Check cron job execution
- [ ] Day 6: Check disk space
- [ ] Day 7: Full system health check

**Monitoring commands:**
```bash
# Error logs
tail -f var/log/exception.log | grep -i archivedotorg

# Import success rate
mysql magento -e "SELECT status, COUNT(*) FROM archivedotorg_import_run GROUP BY status;"

# Match rate
mysql magento -e "SELECT AVG(match_rate) FROM archivedotorg_artist_status;"

# Disk usage
du -sh var/archivedotorg/
```

---

### Task 7.11: Gather User Feedback

**Survey admin users:**
- Is the dashboard useful?
- Any missing features?
- Performance issues?
- Confusing UI elements?

**Document:**
- Pain points
- Feature requests
- Bugs found
- Improvement ideas

- [ ] Feedback collected
- [ ] Issues triaged
- [ ] Next iteration planned

---

### Task 7.12: Create Runbook
**Create:** `docs/RUNBOOK.md`

**Sections:**

1. **Common Errors**
   - Lock acquisition failed
   - Progress file corrupted
   - API rate limited
   - Memory limit exceeded

2. **How to Restart Failed Imports**
   ```bash
   # Check what failed
   bin/magento archive:status lettuce

   # Resume from where it stopped
   bin/magento archive:download lettuce --incremental
   ```

3. **Emergency Rollback**
   ```bash
   # Restore database
   mysql magento < backup_YYYYMMDD.sql

   # Restore files
   rm -rf var/archivedotorg/metadata
   mv var/archivedotorg/metadata.backup var/archivedotorg/metadata

   # Revert code
   git revert HEAD~N
   ```

4. **Performance Tuning**
   - Increase batch size for large imports
   - Adjust cron frequency
   - Index optimization

- [ ] Runbook complete
- [ ] Reviewed by ops team
- [ ] Stored in accessible location

---

## Go/No-Go Criteria

Before marking rollout complete:

| Criteria | Status |
|----------|--------|
| All P0 tests pass | ⬜ |
| Performance benchmarks meet targets (<100ms dashboard) | ⬜ |
| 100% data migration success in staging | ⬜ |
| No critical bugs in 7-day monitoring | ⬜ |
| Runbook created and reviewed | ⬜ |
| Admin training complete | ⬜ |
| Rollback tested and documented | ⬜ |

---

## Final Checklist

- [ ] All phases (0-7) complete
- [ ] All 35 artists functional
- [ ] Dashboard loads <100ms
- [ ] Match rate >95%
- [ ] Zero data loss
- [ ] Documentation complete
- [ ] Team trained
- [ ] Monitoring in place
- [ ] Runbook available

---

## Congratulations!

If you've completed all phases, the import rearchitecture is complete.

**Key improvements delivered:**
- Organized folder structure
- YAML-driven configuration
- Concurrent download protection
- Fast Soundex matching (vs. 43-hour fuzzy)
- <100ms dashboard (vs. 10-30 seconds)
- Complete audit trail
- Admin visibility into imports

**Next steps:**
- Monitor system for 30 days
- Collect feature requests
- Plan version 2.0 improvements

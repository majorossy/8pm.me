# Production Deployment Checklist - Archive.org Import Rearchitecture

**Date:** _______________
**Deployment Lead:** _______________
**Start Time:** _______________
**Estimated Duration:** 30 minutes
**Expected Downtime:** 5 minutes (Phase 1 only)

---

## Pre-Deployment (T-24 hours)

### Communications
- [ ] Email team: Deployment scheduled for [DATE/TIME]
- [ ] Slack notification: Maintenance window announced
- [ ] Update status page: "Scheduled Maintenance" notice
- [ ] Confirm all stakeholders notified

### Environment Verification
- [ ] Verify all modules enabled: `bin/magento module:status | grep ArchiveDotOrg`
- [ ] Verify database tables exist: `bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"`
- [ ] Verify disk space available: `df -h` (need ~5GB for backups)
- [ ] Verify no active imports running: `bin/mysql -e "SELECT * FROM archivedotorg_import_run WHERE status='running';"`
- [ ] Test backup/restore procedure (optional but recommended)

### Backup Verification
- [ ] Database backup directory exists: `mkdir -p backups/db`
- [ ] Metadata backup directory exists: `mkdir -p backups/metadata`
- [ ] Test database backup: `mysqldump magento | head -100`
- [ ] Verify backup storage has sufficient space: `du -sh backups/`

### Scripts Ready
- [ ] All deployment scripts executable: `ls -la deployment/phase*/`
- [ ] Monitor script executable: `ls -la deployment/monitor-deployment.sh`
- [ ] Review rollback procedures: `cat deployment/DEPLOYMENT_PLAN.md`

---

## Pre-Deployment (T-1 hour)

### Final Checks
- [ ] Stop all cron jobs (optional): `bin/magento cron:remove`
- [ ] Clear all locks: `rm -f var/locks/archivedotorg/*.lock`
- [ ] Check for stuck processes: `ps aux | grep archivedotorg`
- [ ] Verify git status clean: `git status`
- [ ] Create session log: `script -a deployment_session_$(date +%Y%m%d_%H%M%S).log`

### Team Readiness
- [ ] Deployment team on standby
- [ ] Emergency contacts confirmed
- [ ] Rollback procedure reviewed
- [ ] Communication channels open (Slack/Email)

---

## Phase 1: Database Migration (5 minutes, MAINTENANCE MODE)

**Time Started:** _______________

### Pre-Phase Checks
- [ ] No active imports: `bin/mysql -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='running';"`
- [ ] No users in admin: Check admin sessions
- [ ] Notify users: "Entering maintenance mode in 5 minutes"

### Execution
- [ ] Run script: `./deployment/phase1/deploy-database.sh`
- [ ] Script output shows: "✅ Database deployment complete"
- [ ] Verify tables: Count = 9
- [ ] Verify indexes created
- [ ] Verify maintenance mode disabled automatically

### Verification
```bash
# Count tables (expect 9)
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';" | wc -l

# Check maintenance mode (should be off)
bin/magento maintenance:status

# Check for errors
tail -50 var/log/exception.log
```

**Results:**
- [ ] ✅ 9 tables present
- [ ] ✅ Maintenance mode OFF
- [ ] ✅ No errors in exception log
- [ ] ✅ Site accessible

**Time Completed:** _______________
**Duration:** _______________ minutes

---

## Phase 2: Code Deployment (10 minutes, ZERO DOWNTIME)

**Time Started:** _______________

### Pre-Phase Checks
- [ ] Phase 1 completed successfully
- [ ] Site accessible to users
- [ ] No critical errors in logs

### Execution
- [ ] Run script: `./deployment/phase2/deploy-code.sh`
- [ ] Script output shows: "✅ Code deployment complete"
- [ ] Verify old commands still work
- [ ] Verify new commands available

### Verification
```bash
# Test old command (backward compatibility)
bin/magento archivedotorg:status

# Test new commands exist
bin/magento list | grep archivedotorg

# Check cache cleared
ls -la var/cache/

# Check DI compiled
ls -la generated/
```

**Results:**
- [ ] ✅ Old commands work (backward compatible)
- [ ] ✅ New commands available (download, populate, show-unmatched)
- [ ] ✅ Caches cleared
- [ ] ✅ DI compiled successfully
- [ ] ✅ No errors in logs

**Time Completed:** _______________
**Duration:** _______________ minutes

---

## Phase 3: Data Migration (15 minutes, ZERO DOWNTIME)

**Time Started:** _______________

### Pre-Phase Checks
- [ ] Phase 2 completed successfully
- [ ] Metadata directory exists: `ls -la var/archivedotorg/`
- [ ] Sufficient disk space for backup

### Execution
- [ ] Run script: `./deployment/phase3/migrate-data.sh`
- [ ] Script output shows: "✅ Data migration complete"
- [ ] Verify backup created
- [ ] Verify folder structure organized
- [ ] Verify YAML configs exported

### Verification
```bash
# Check folder structure (should be organized by artist)
ls -la var/archivedotorg/metadata/

# Check YAML configs created
find src/app/code/ArchiveDotOrg/Core/config/artists -name "*.yaml"

# Verify backup exists
ls -la var/archivedotorg/metadata.backup.*

# Run validation
bin/magento archive:validate --all
```

**Results:**
- [ ] ✅ Folders organized (artist-based structure)
- [ ] ✅ YAML configs created
- [ ] ✅ Backup created
- [ ] ✅ Validation passed (0 errors)
- [ ] ✅ No data loss (file counts match)

**Time Completed:** _______________
**Duration:** _______________ minutes

---

## Phase 4: Admin Dashboard (5 minutes, ZERO DOWNTIME)

**Time Started:** _______________

### Pre-Phase Checks
- [ ] Phase 3 completed successfully
- [ ] Can access admin panel: https://magento.test/admin
- [ ] Admin user credentials ready

### Execution
- [ ] Run script: `./deployment/phase4/enable-dashboard.sh`
- [ ] Script output shows: "✅ Dashboard deployment complete"
- [ ] Verify module enabled
- [ ] Verify routes accessible
- [ ] Verify grids load

### Verification
```bash
# Check module status
bin/magento module:status ArchiveDotOrg_Admin

# Check routes
bin/magento setup:db:status

# Test database query (dashboard uses this)
bin/mysql -e "SELECT COUNT(*) FROM archivedotorg_artist;"
```

**Browser Verification (Admin Panel):**
- [ ] Navigate to: Content → Archive.org Import → Dashboard
- [ ] Dashboard loads without errors
- [ ] "Import Activity" grid visible
- [ ] "Artists" grid visible
- [ ] "Unmatched Tracks" grid visible
- [ ] Charts render correctly
- [ ] No JavaScript console errors

**Results:**
- [ ] ✅ Module enabled
- [ ] ✅ Dashboard accessible
- [ ] ✅ All grids functional
- [ ] ✅ No errors in browser console
- [ ] ✅ Query performance acceptable (<500ms)

**Time Completed:** _______________
**Duration:** _______________ minutes

---

## Post-Deployment Verification (Immediate)

**Time Started:** _______________

### Smoke Tests
- [ ] Test old import command: `bin/magento archivedotorg:import-shows --help`
- [ ] Test new download command: `bin/magento archivedotorg:download --help`
- [ ] Test new populate command: `bin/magento archivedotorg:populate --help`
- [ ] Check all modules enabled: `bin/magento module:status`
- [ ] Verify no errors: `tail -100 var/log/exception.log`

### Quick Import Test
```bash
# Test with small dataset (optional but recommended)
bin/magento archivedotorg:download "lettuce" --limit=5
bin/magento archivedotorg:populate "lettuce"
```

- [ ] ✅ Download completes successfully
- [ ] ✅ Populate completes successfully
- [ ] ✅ Products visible in admin
- [ ] ✅ Dashboard updates with new data

### Performance Baseline
- [ ] Run monitoring script: `./deployment/monitor-deployment.sh 1`
- [ ] Review output: Check for any immediate issues
- [ ] Save baseline metrics

**Results:**
- [ ] ✅ All smoke tests passed
- [ ] ✅ Quick import test successful
- [ ] ✅ Baseline monitoring complete
- [ ] ✅ No critical errors

**Time Completed:** _______________
**Total Deployment Duration:** _______________ minutes

---

## Post-Deployment Communications

### Immediate (T+30 minutes)
- [ ] Email team: "Deployment complete, monitoring in progress"
- [ ] Slack update: Success metrics summary
- [ ] Status page: Clear "Scheduled Maintenance" notice
- [ ] Document any issues encountered

### End of Day 1 (T+8 hours)
- [ ] Review error logs: `tail -500 var/log/exception.log`
- [ ] Check import success rate
- [ ] Verify no user-reported issues
- [ ] Send Day 1 summary to team

---

## 7-Day Monitoring Schedule

### Daily Tasks (Days 1-7)
- [ ] **Day 1:** Run `./deployment/monitor-deployment.sh 1` (Focus: Error logs every 30 min)
- [ ] **Day 2:** Run `./deployment/monitor-deployment.sh 2` (Focus: Dashboard performance)
- [ ] **Day 3:** Run `./deployment/monitor-deployment.sh 3` (Focus: Match rate stability)
- [ ] **Day 4:** Run `./deployment/monitor-deployment.sh 4` (Focus: Memory leak detection)
- [ ] **Day 5:** Run `./deployment/monitor-deployment.sh 5` (Focus: Cron job verification)
- [ ] **Day 6:** Run `./deployment/monitor-deployment.sh 6` (Focus: Disk space monitoring)
- [ ] **Day 7:** Run `./deployment/monitor-deployment.sh 7` (Focus: Full system health check)

### Success Criteria (7-Day Period)
- [ ] Import success rate >95%
- [ ] Match rate >97%
- [ ] Dashboard load time <100ms (warm cache)
- [ ] Error rate <1%
- [ ] Zero data loss
- [ ] No critical bugs reported

---

## Phase 5: Cleanup (30+ Days Post-Deployment)

**Scheduled Date:** _______________ (30 days from Phase 4)

### Pre-Cleanup Verification
- [ ] 30 days have passed since Phase 4
- [ ] No critical issues in production
- [ ] All stakeholders approve cleanup
- [ ] Final backup created

### Execution
- [ ] Run script: `./deployment/phase5/cleanup-old-code.sh`
- [ ] Verify old patches deleted
- [ ] Verify database patch_list updated
- [ ] Verify final backup created

---

## Rollback Procedures

### When to Rollback
- Critical bug affecting users
- Data corruption detected
- Severe performance degradation
- Cannot complete a phase

### Full Rollback (Emergency)
```bash
# 1. Enable maintenance
bin/magento maintenance:enable

# 2. Restore database
mysql magento < backups/db/backup_YYYYMMDD_HHMMSS.sql

# 3. Restore metadata
rm -rf var/archivedotorg/metadata
cp -r backups/metadata/metadata.backup.YYYYMMDD var/archivedotorg/metadata

# 4. Revert code
git revert HEAD~N
bin/magento cache:flush
bin/magento setup:di:compile

# 5. Disable maintenance
bin/magento maintenance:disable
```

### Partial Rollback (Phase-Specific)

**Phase 1 (Database):**
```bash
mysql magento < backups/db/backup_YYYYMMDD_HHMMSS.sql
```

**Phase 2 (Code):**
```bash
git revert <commit_hash>
bin/magento cache:flush
bin/magento setup:di:compile
```

**Phase 3 (Data):**
```bash
rm -rf var/archivedotorg/metadata
cp -r backups/metadata/metadata.backup.YYYYMMDD var/archivedotorg/metadata
```

**Phase 4 (Dashboard):**
```bash
bin/magento module:disable ArchiveDotOrg_Admin
bin/magento cache:flush
```

---

## Sign-Off

### Phase Completion

| Phase | Status | Duration | Completed By | Signature |
|-------|--------|----------|--------------|-----------|
| Phase 1: Database | ☐ | ___ min | __________ | __________ |
| Phase 2: Code | ☐ | ___ min | __________ | __________ |
| Phase 3: Data | ☐ | ___ min | __________ | __________ |
| Phase 4: Dashboard | ☐ | ___ min | __________ | __________ |

### Final Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Deployment Lead** | | | |
| **Technical Lead** | | | |
| **Operations** | | | |

---

## Notes & Issues Encountered

**Phase 1 Notes:**
_______________________________________________________________
_______________________________________________________________

**Phase 2 Notes:**
_______________________________________________________________
_______________________________________________________________

**Phase 3 Notes:**
_______________________________________________________________
_______________________________________________________________

**Phase 4 Notes:**
_______________________________________________________________
_______________________________________________________________

**Post-Deployment Notes:**
_______________________________________________________________
_______________________________________________________________

---

## Lessons Learned

**What Went Well:**
_______________________________________________________________
_______________________________________________________________

**What Could Be Improved:**
_______________________________________________________________
_______________________________________________________________

**Recommendations for Future Deployments:**
_______________________________________________________________
_______________________________________________________________

---

**End of Deployment Checklist**

**Deployment Status:** ☐ Successful  ☐ Rolled Back  ☐ Partial Success

**Final Sign-Off Date:** _______________

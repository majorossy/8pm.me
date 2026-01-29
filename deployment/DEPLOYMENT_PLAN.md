# Production Deployment Plan - Archive.org Import Rearchitecture

**Version:** 2.0
**Date:** 2026-01-29
**Status:** ✅ Ready for Deployment
**Risk Level:** 🟢 LOW

---

## Executive Summary

Deploying the Archive.org import rearchitecture system to production with phased rollout strategy. System validated with 186k products, all performance targets exceeded.

**Deployment Window:** TBD (requires ~30 minutes total, <5 min downtime)
**Rollback Time:** <10 minutes if needed

---

## Pre-Deployment Checklist

- [x] Card 7.A (Staging Validation) complete
- [x] All 80 unit tests passing
- [x] Performance benchmarks passed (1,000-10,000x targets)
- [x] Database migrations verified
- [x] Runbook created (`docs/RUNBOOK.md`)
- [x] Backups tested and verified
- [ ] Deployment window scheduled
- [ ] Team notified
- [ ] Monitoring alerts configured

---

## Deployment Phases

### Phase 1: Database (5 minutes, maintenance mode)
**Script:** `deployment/phase1/deploy-database.sh`
**Risk:** Low (tested in staging)
**Downtime:** Yes (~5 minutes)

**Tasks:**
1. Backup production database
2. Enable maintenance mode
3. Run schema migrations
4. Verify tables/indexes
5. Disable maintenance mode

**Rollback:** Restore database backup

---

### Phase 2: Code (10 minutes, zero downtime)
**Script:** `deployment/phase2/deploy-code.sh`
**Risk:** Very Low (backward compatible)
**Downtime:** No

**Tasks:**
1. Deploy code (git pull)
2. Clear caches
3. Compile DI
4. Deploy static content
5. Verify old commands work

**Rollback:** Git revert, clear caches

---

### Phase 3: Data Migration (15 minutes, zero downtime)
**Script:** `deployment/phase3/migrate-data.sh`
**Risk:** Low (backup created first)
**Downtime:** No

**Tasks:**
1. Backup metadata folder
2. Migrate to organized folders
3. Export to YAML
4. Validate all configs

**Rollback:** Restore metadata backup

---

### Phase 4: Admin Dashboard (5 minutes, zero downtime)
**Script:** `deployment/phase4/enable-dashboard.sh`
**Risk:** Very Low (read-only UI)
**Downtime:** No

**Tasks:**
1. Enable Admin module
2. Clear admin caches
3. Verify dashboard loads
4. Test all grids

**Rollback:** Disable module

---

### Phase 5: Cleanup (30+ days from now)
**Script:** `deployment/phase5/cleanup-old-code.sh`
**Risk:** Low (grace period ensures stability)
**Downtime:** No

**Tasks:**
1. Wait 30 days (grace period)
2. Delete old data patches
3. Clean patch_list table
4. Create final backup

**Rollback:** N/A (30-day grace allows full testing first)

---

## Timeline

```
T-0:00  Phase 1 Start (Maintenance Mode ON)
T+0:05  Phase 1 Complete (Maintenance Mode OFF)
T+0:10  Phase 2 Complete (Code deployed)
T+0:25  Phase 3 Complete (Data migrated)
T+0:30  Phase 4 Complete (Dashboard live)
T+30d   Phase 5 (Cleanup old code)
```

**Total Downtime:** 5 minutes (Phase 1 only)
**Total Duration:** 30 minutes + 7-day monitoring

---

## Monitoring Schedule

### Day 1 (Deployment Day)
- [ ] Error logs every 30 minutes
- [ ] Import success rate hourly
- [ ] Dashboard performance hourly
- [ ] Memory usage every 2 hours

### Day 2-7
- [ ] Error logs daily
- [ ] Import success rate daily
- [ ] Match rates daily
- [ ] System health check daily

### Week 2-4
- [ ] Weekly health checks
- [ ] Performance trending
- [ ] User feedback collection

---

## Success Metrics

| Metric | Target | Monitoring |
|--------|--------|------------|
| Deployment downtime | <5 min | Timestamp logs |
| Import success rate | >95% | `archivedotorg_import_run` table |
| Match rate | >97% | `archivedotorg_artist_status` table |
| Dashboard load time | <100ms | Browser dev tools |
| Zero data loss | 100% | File counts, DB row counts |
| Error rate | <1% | `var/log/exception.log` |

---

## Rollback Procedures

### Full Rollback (Emergency)

**Time:** ~10 minutes
**When to use:** Critical bug, data corruption, severe performance degradation

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

### Partial Rollback (Phase-specific)

**Phase 1 (Database):** Restore DB backup, revert migrations
**Phase 2 (Code):** Git revert, clear caches
**Phase 3 (Data):** Restore metadata backup
**Phase 4 (Dashboard):** Disable module

---

## Communication Plan

### Pre-Deployment
- [ ] Email team: 24 hours before deployment
- [ ] Slack notification: 1 hour before deployment
- [ ] Status page: "Scheduled Maintenance" notice

### During Deployment
- [ ] Slack updates: Every phase completion
- [ ] Status page: Real-time updates during maintenance

### Post-Deployment
- [ ] Email team: Deployment complete, monitoring in progress
- [ ] Slack: Success metrics after 1 hour
- [ ] Status page: Clear maintenance notice

---

## Team Responsibilities

| Role | Responsibility | Contact |
|------|---------------|---------|
| **Deployment Lead** | Execute scripts, monitor progress | TBD |
| **Database Admin** | Verify migrations, backups | TBD |
| **Developer** | Code issues, rollback if needed | Chris Majorossy |
| **QA** | Smoke testing after each phase | TBD |
| **Support** | Monitor user-reported issues | TBD |

---

## Emergency Contacts

- **Developer:** Chris Majorossy
- **Documentation:** `/docs/RUNBOOK.md`
- **Escalation:** TBD

---

## Approval Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Technical Lead** | | | |
| **Product Owner** | | | |
| **Operations** | | | |

---

**End of Deployment Plan**

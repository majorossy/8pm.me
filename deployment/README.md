# Production Deployment - Quick Start Guide

## Overview

This directory contains all production deployment scripts, plans, and monitoring tools for the Archive.org Import Rearchitecture project.

**Status:** ✅ Ready for Production Deployment
**Risk Level:** 🟢 LOW
**Estimated Time:** 30 minutes + 7-day monitoring

---

## Quick Start

### 1. Review Documentation

```bash
# Read the deployment plan
cat deployment/DEPLOYMENT_PLAN.md

# Read the runbook
cat docs/RUNBOOK.md

# Review completion report
cat docs/import-rearchitecture/CARD-7B-COMPLETION.md
```

---

### 2. Execute Deployment (30 minutes)

**Important:** Run scripts in order, one at a time.

```bash
# Phase 1: Database Migration (5 min, DOWNTIME)
./deployment/phase1/deploy-database.sh

# Verify Phase 1 completed successfully before continuing

# Phase 2: Code Deployment (10 min, no downtime)
./deployment/phase2/deploy-code.sh

# Phase 3: Data Migration (15 min, no downtime)
./deployment/phase3/migrate-data.sh

# Phase 4: Admin Dashboard (5 min, no downtime)
./deployment/phase4/enable-dashboard.sh

# Phase 5: Cleanup (run 30 days from now)
# ./deployment/phase5/cleanup-old-code.sh
```

---

### 3. Monitor for 7 Days

```bash
# Run daily monitoring
./deployment/monitor-deployment.sh 1  # Day 1
./deployment/monitor-deployment.sh 2  # Day 2
# ... continue through Day 7

# Review logs
ls -la var/log/deployment_monitoring/
```

---

## Directory Structure

```
deployment/
├── README.md                    # This file
├── DEPLOYMENT_PLAN.md           # Complete deployment strategy
├── monitor-deployment.sh        # 7-day monitoring script
├── phase1/
│   └── deploy-database.sh       # Database migrations
├── phase2/
│   └── deploy-code.sh           # Code deployment
├── phase3/
│   └── migrate-data.sh          # Data migration
├── phase4/
│   └── enable-dashboard.sh      # Admin dashboard
└── phase5/
    └── cleanup-old-code.sh      # Post-deployment cleanup
```

---

## Expected Timeline

```
T-0:00  Phase 1 Start (Maintenance Mode ON)
T+0:05  Phase 1 Complete (Maintenance Mode OFF)
T+0:15  Phase 2 Complete (Code deployed)
T+0:30  Phase 3 Complete (Data migrated)
T+0:35  Phase 4 Complete (Dashboard live)
T+30d   Phase 5 (Cleanup old code)
```

**Total Downtime:** 5 minutes (Phase 1 only)

---

## Success Criteria

| Metric | Target | How to Check |
|--------|--------|--------------|
| Downtime | <5 min | Timestamp logs |
| Data loss | 0 files | File count comparison |
| Import success | >95% | `archivedotorg_import_run` table |
| Match rate | >97% | `archivedotorg_artist_status` table |
| Dashboard load | <100ms | Browser dev tools |
| Error rate | <1% | `var/log/exception.log` |

---

## Emergency Rollback

**If deployment fails, execute full rollback:**

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

**Rollback Time:** ~10 minutes

---

## Support Resources

**Documentation:**
- `/docs/RUNBOOK.md` - Operational runbook
- `/docs/DEVELOPER_GUIDE.md` - Developer documentation
- `/docs/ADMIN_GUIDE.md` - Admin user guide
- `/docs/import-rearchitecture/` - Full project documentation

**Troubleshooting:**
- See RUNBOOK.md Section 4: Troubleshooting Guide
- 8 common errors with solutions documented

**Emergency Contact:**
- Developer: Chris Majorossy

---

## Post-Deployment Checklist

After all phases complete:

- [ ] All 5 phases executed successfully
- [ ] No errors in `var/log/exception.log`
- [ ] Admin dashboard accessible
- [ ] Test import works: `bin/magento archivedotorg:download "TestArtist" --limit=5`
- [ ] Day 1 monitoring complete
- [ ] Stakeholders notified of completion

---

## Key Files Created

### Scripts (Executable)
- ✅ `phase1/deploy-database.sh` (3.8 KB)
- ✅ `phase2/deploy-code.sh` (4.1 KB)
- ✅ `phase3/migrate-data.sh` (4.5 KB)
- ✅ `phase4/enable-dashboard.sh` (4.2 KB)
- ✅ `phase5/cleanup-old-code.sh` (4.7 KB)
- ✅ `monitor-deployment.sh` (6.9 KB)

### Documentation
- ✅ `DEPLOYMENT_PLAN.md` (4.9 KB, 216 lines)
- ✅ `/docs/RUNBOOK.md` (21.8 KB, 533 lines)

### Monitoring
- ✅ Monitoring logs: `/var/log/deployment_monitoring/`

---

## System Requirements

**Before deploying, verify:**
- [x] Card 7.A (Staging Validation) complete
- [x] All unit tests passing (80 tests, 199 assertions)
- [x] Database has 9 archivedotorg tables
- [x] 6 modules registered
- [x] 7 CLI commands available
- [x] Performance benchmarks passed (1,000-10,000x targets)

**All requirements met:** ✅

---

## Questions?

1. **When should I run Phase 5?**
   - Wait 30 days after Phase 4 to ensure system stability

2. **What if a phase fails?**
   - Each script has built-in error handling
   - Rollback procedures documented in DEPLOYMENT_PLAN.md
   - Contact developer if automated rollback fails

3. **How do I know if deployment succeeded?**
   - Check monitoring script output
   - Verify success criteria (see table above)
   - Review deployment logs

4. **Can I run phases multiple times?**
   - Phase 1: Idempotent (safe to re-run)
   - Phase 2: Idempotent (safe to re-run)
   - Phase 3: Use with caution (creates backups first)
   - Phase 4: Idempotent (safe to re-run)
   - Phase 5: Run only once (deletes files)

---

**Ready to deploy!** 🚀

Start with Phase 1 when ready: `./deployment/phase1/deploy-database.sh`

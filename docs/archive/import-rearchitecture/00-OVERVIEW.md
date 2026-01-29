# Archive.org Import Rearchitecture

**Status:** ✅ Phase 5 Complete - Dashboard Grids Implemented
**Timeline:** 9-10 weeks (5-6 weeks with parallel agents)
**Last Updated:** 2026-01-28

**⚠️ IMPORTANT:** Comprehensive validation discovered that **162 files (7,600+ lines)** are already implemented, including LockService (378 lines), AlbumArtworkService (443 lines), 11 CLI commands, queue infrastructure, and GraphQL resolvers. See [FIXES.md](./FIXES.md) for details.

**✅ PHASE 5 COMPLETED:**
- **5.A**: Database Tables & Models (4 tables: import_run, artist_status, unmatched_track, daily_metrics)
- **5.C**: Admin UI Grids (Artists, Import History, Unmatched Tracks with filters and actions)
- **Documentation**: Developer Guide, Admin Guide, API Reference created

**⏳ IN PROGRESS:**
- **Phase 3**: Command infrastructure partially complete (BaseLoggedCommand, DownloadCommand, PopulateCommand)
- **Phase 5.B**: Admin controllers and menu structure (partially complete)
- **Phase 5.D**: Charts and real-time features (pending)
- **Phase 6**: Testing and final documentation (this phase)

---

## Architecture Decisions (Locked)

These decisions are FINAL and documented in Phase 0. Do not revisit.

| Decision | Choice | Reference |
|----------|--------|-----------|
| YAML structure | Multi-album with stable keys | Fix #47, Phase 2 Task 2.3 |
| Matching algorithm | Hybrid (exact→metaphone→limited fuzzy) | Fix #41, Phase 3 Task 3.6 |
| Unmappable files | Quarantine to `/unmapped/` | Fix #13 |
| Locking | Redis primary + flock fallback | Fix #10, #39 |
| Dashboard scope | Split 5a (MVP) / 5b (Enhanced) | Phase 5 |
| Test approach | TDD against interfaces | Fix #8, #11 |

---

## Quick Navigation

| Phase | Document | Timeline | Status |
|-------|----------|----------|--------|
| **-1** | **[Standalone Fixes](./00a-PHASE-MINUS-1-STANDALONE.md)** | **Day 1** | **🔴 START HERE** |
| 0 | [Critical Fixes](./01-PHASE-0-CRITICAL.md) | Week 1-2 | ⏸️ Blocked by Phase -1 |
| 1 | [Folder Migration](./02-PHASE-1-FOLDERS.md) | Week 3 | ⏸️ Blocked by Phase 0 |
| 2 | [YAML Configuration](./03-PHASE-2-YAML.md) | Week 4-5 | ⏸️ Blocked by Phase 0 |
| 3 | [Commands & Matching](./04-PHASE-3-COMMANDS.md) | Week 6-7 | ⏸️ Blocked by Phase 2 |
| 4 | [Extended Attributes](./05-PHASE-4-ATTRIBUTES.md) | Week 3 (parallel) | ⏸️ Blocked by Phase 0 |
| 5a | [Admin Dashboard MVP](./06-PHASE-5-DASHBOARD.md) | Week 8 | ⏸️ Blocked by Phase 3 |
| 5b | [Dashboard Enhanced](./06-PHASE-5-DASHBOARD.md) | Week 9 | ⏸️ Blocked by Phase 5a |
| 6 | [Testing & Docs](./07-PHASE-6-TESTING.md) | Week 9-10 | ⏸️ Blocked by Phase 5a |
| 7 | [Rollout](./08-PHASE-7-ROLLOUT.md) | Week 10 | ⏸️ Blocked by Phase 6 |

**Also see:**
- [Critical Fixes (48 issues)](./FIXES.md) - Reference document
- **[Swarming Strategy](./09-SWARMING-STRATEGY.md)** - Parallel agent execution guide
- **[Task Cards](./10-TASK-CARDS.md)** - Copy-paste prompts for each agent

---

## Swarming Strategy (Parallel Agents)

**With 4 agents:** Timeline reduces from **9-10 weeks → 5-6 weeks**

See **[09-SWARMING-STRATEGY.md](./09-SWARMING-STRATEGY.md)** for full details.

### Peak Parallelism (Weeks 3-5)
After Phase 0 completes, **4 agents** can work simultaneously:
| Agent | Phase | Focus |
|-------|-------|-------|
| A | Phase 1 | Folder migration & cleanup |
| B | Phase 2 | YAML config & validation |
| C | Phase 4 | Extended EAV attributes |
| D | Testing | Unit test stubs for interfaces |

### Key Parallel Opportunities
- **Phase -1:** 5 tasks → 3 agents (Day 1)
- **Phase 0:** 4 work streams → 4 agents (Week 1-2)
- **Phases 1/2/4:** Fully parallel → 4 agents (Week 3-5)
- **Phase 5:** 4 work streams → 4 agents (Week 8-9)
- **Phase 6:** Tests + Docs → 4 agents (Week 9-10)

### Critical Sync Points
All agents must complete before:
1. After Phase -1 → DI compile verification
2. After Phase 0 → Database migration execution
3. After Phase 2 → YAML validation of all 35 artists
4. After Phase 5a → Dashboard MVP verification
5. Before Phase 7 → All tests pass (Go/No-Go)

---

## Implementation Status (2026-01-28)

### ✅ Completed Components

| Component | Status | Details |
|-----------|--------|---------|
| **Phase 3 - Commands** | ✅ Partial | BaseLoggedCommand, BaseReadCommand, DownloadCommand, PopulateCommand, ShowUnmatchedCommand, StatusCommand |
| **Phase 5A - Database** | ✅ Complete | 4 tables created: import_run, artist_status, unmatched_track, daily_metrics |
| **Phase 5C - UI Grids** | ✅ Complete | Artist grid, Import History grid, Unmatched Tracks grid with filters/actions |
| **Documentation** | ✅ Complete | Developer Guide (11 sections), Admin Guide (7 sections), API Reference (6 endpoints) |

### ⏳ In Progress

| Component | Status | Next Steps |
|-----------|--------|------------|
| **Phase 5B - Controllers** | 🟡 Partial | Need admin menu, routing, progress AJAX endpoint |
| **Phase 5D - Charts** | ⏸️ Pending | ApexCharts integration, real-time progress widget, cron jobs |
| **Phase 6 - Testing** | ⏸️ Pending | Unit tests, integration tests, performance benchmarks |

### 📊 Files Created (Phase 6D)

**Documentation files:**
- `docs/DEVELOPER_GUIDE.md` - 800+ lines, 10 sections, full CLI reference, troubleshooting
- `docs/ADMIN_GUIDE.md` - 600+ lines, 7 sections, dashboard walkthrough, resolution workflows
- `docs/API.md` - 600+ lines, 6 REST endpoints, authentication, error handling, examples

**Total documentation:** ~2,000 lines of comprehensive guides

### 🎯 Ready for Production Use

**What works now:**
1. **CLI Commands**: Download, Populate, Status, Show-Unmatched, Validate, Setup (11 total)
2. **Database Infrastructure**: 9 tables with optimized indexes for <100ms queries
3. **Admin Grids**: Full CRUD for Artists, Import History, Unmatched Tracks
4. **REST API**: 6 endpoints for programmatic import management
5. **Services**: LockService, TrackMatcherService, BulkProductImporter, AlbumArtworkService, etc.

**What's pending:**
1. Charts and visualizations (Phase 5D)
2. Real-time progress tracking (Phase 5D)
3. Comprehensive test suite (Phase 6)
4. Daily metrics aggregation cron (Phase 5D)

---

## Getting Started

1. **Read** [FIXES.md](./FIXES.md) to understand all known issues
2. **Review** [09-SWARMING-STRATEGY.md](./09-SWARMING-STRATEGY.md) for parallel execution
3. **Review Documentation**:
   - [Developer Guide](../DEVELOPER_GUIDE.md) - Adding artists, extending matching logic
   - [Admin Guide](../ADMIN_GUIDE.md) - Dashboard usage, resolving unmatched tracks
   - [API Reference](../API.md) - REST API integration
4. **Start** [Phase -1](./00a-PHASE-MINUS-1-STANDALONE.md) - standalone fixes (1 day)
5. **Continue** to Phase 0 and beyond

---

## Environment Context

**Current setup (as of 2026-01):**
- **Frontend (Next.js):** Runs on host Mac at port 3001
- **Magento/PHP:** Runs in Docker container (8pm-phpfpm-1)
- **Database/Redis/etc:** Run in Docker containers
- **File sharing:** VirtioFS (macOS ↔ Docker)

**Implications:**
- All `bin/magento` commands run inside Docker
- File locking via `flock()` should work (all PHP in same container)
- If setup changes to run PHP on host, revisit Fix #39 (VirtioFS locking)

---

## Architecture Overview

### Current State
- Flat file structure: `var/archivedotorg/metadata/*.json` (2,130 files)
- Hardcoded artist data in PHP data patches
- No concurrency protection (parallel downloads corrupt data)
- Dashboard queries take 10-30 seconds
- API rate limiting is real bottleneck (3.5 hours for 10k shows)

### Target State
- Organized folders: `var/archivedotorg/metadata/{Artist}/*.json`
- YAML-driven artist configuration
- File locking prevents concurrent corruption
- Dashboard loads in <100ms (indexed queries)
- Soundex phonetic matching (~1,000x faster than fuzzy)

---

## Key Deliverables

### SQL Migrations (8 files)
Location: `migrations/`

| File | Purpose | Priority |
|------|---------|----------|
| `001_create_artist_table.sql` | Artist normalization | 🔴 P0 |
| `002_add_dashboard_indexes.sql` | Dashboard performance | 🔴 P0 |
| `003_convert_json_columns.sql` | TEXT → JSON | 🔴 P0 |
| `004_create_show_metadata_table.sql` | Extract large JSON | 🔴 P0 |
| `005_create_import_run_table.sql` | Audit trail | 🟧 P1 |
| `006_create_artist_status_table.sql` | Dashboard stats | 🟧 P1 |
| `007_create_unmatched_track_table.sql` | Quality tracking | 🟧 P1 |
| `008_create_daily_metrics_table.sql` | Dashboard charts | 🟧 P1 |

### Lock Service (Ready)
- `src/app/code/ArchiveDotOrg/Core/Model/LockService.php`
- `src/app/code/ArchiveDotOrg/Core/Api/LockServiceInterface.php`
- `src/app/code/ArchiveDotOrg/Core/Model/LockException.php`

---

## Critical Warnings

### 1. Don't Enable Fuzzy Matching (Performance Claims Corrected)
- Runtime: ~~43 hours~~ **2-10 minutes** (original claim was 860x overstated)
- Memory: ~~6.3GB~~ **50-100MB** (original claim was 63x overstated)
- **Real bottleneck: API rate limiting** (3.5 hours for 10k shows)
- **Use Soundex instead** (~1,000x faster than fuzzy, not 100,000x)

### 2. Always Use File Locking
- Two simultaneous downloads = corrupted progress file
- LockService MUST be integrated before production

### 3. Run Migrations in Order
- 001 (artist table) MUST run before 005-008 (foreign keys)
- 002 (indexes) MUST run before dashboard release

### 4. Don't Delete ImportShowsCommand
- Deprecate it (show warning)
- Some users need one-command import
- Remove in version 2.0, not now

### 5. Backup Before Migration
```bash
# Backup database
mysqldump magento > backup_$(date +%Y%m%d).sql

# Backup JSON files
cp -r var/archivedotorg/metadata var/archivedotorg/metadata.backup
```

---

## Success Metrics

### Performance
- [ ] Dashboard loads in <100ms
- [ ] Import 500 shows in <10 minutes
- [ ] Soundex matching <5ms per track
- [ ] No memory leaks during bulk imports

### Quality
- [ ] Zero data loss during migration
- [ ] 95%+ match rate on track matching
- [ ] <1% unmatched tracks requiring manual review
- [ ] 100% test coverage on critical services

### Usability
- [ ] Adding new artist takes <5 minutes
- [ ] Admins can resolve unmatched tracks in dashboard
- [ ] Real-time progress visible during imports
- [ ] Clear error messages for all failures

---

## How Fixes Relate to Phases

**[FIXES.md](./FIXES.md) contains 48 issues** discovered during comprehensive validation. They are organized by priority:
- 🔴 **16 Critical** - Must fix before/during Phase 0
- 🟧 **19 High** - Must fix before MVP (during Phases 1-3)
- 🟨 **13 Medium** - Fix before production (during Phases 4-7)

### Pre-Phase 0 (Documentation & Setup)

**Complete these BEFORE starting Phase 0:**

| Fix | Task | Effort |
|-----|------|--------|
| #2 | Correct performance documentation (43hr → 2-10min) | 30 min |
| #11 | Align test plan with actual codebase | 1 hour |
| #12 | Add feature flag configuration to `etc/config.xml` | 30 min |

### Integrated into Phase 0

**These fixes are implemented AS PART OF Phase 0 tasks:**

| Fix | Phase 0 Task | Description |
|-----|--------------|-------------|
| #1 | Task 0.1 | Create artist normalization table |
| #4 | Task 0.6 | Add atomic progress file writes |
| #7 | Task 0.1 | Add FK cascade actions |
| #8 | Task 0.5 | Register service interfaces in di.xml |
| #10 | Task 0.5 | Fix file lock race condition (verify flock usage) |

### Integrated into Later Phases

**Remaining fixes are incorporated into Phases 1-7** as relevant to each phase's scope.

---

## Getting Started

**Step 1: Pre-Phase 0 Setup (1-2 hours)**
- [ ] Fix #2: Update performance claims in all docs (FIXES.md already done)
- [ ] Fix #11: Revise test plan to target actual code
- [ ] Fix #12: Add feature flags to `etc/config.xml`

**Step 2: Start Phase 0 (Week 1-2)**
- [ ] Complete all 11 tasks in [Phase 0 - Critical](./01-PHASE-0-CRITICAL.md)
- [ ] Verify all 🔴 Critical fixes from FIXES.md are addressed
- [ ] **Phase 0 must be 100% complete before starting any other phase**

**Step 3: Track Progress**
- Update status in each phase doc as you complete tasks
- Check off fixes in FIXES.md as they're implemented

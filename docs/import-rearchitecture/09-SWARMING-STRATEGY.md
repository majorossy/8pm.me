# Swarming Strategy for Import Rearchitecture

**Purpose:** Identify parallel work streams for agent swarming
**Timeline:** 9-10 weeks → Could reduce to **5-6 weeks** with parallel agents

---

## Legend

| Symbol | Meaning |
|--------|---------|
| 🔵 **AGENT** | Can be assigned to a dedicated agent |
| 🔗 **SEQUENTIAL** | Must wait for previous task |
| ⚡ **PARALLEL** | Can run simultaneously with other tasks |
| 🧪 **TESTABLE** | Has clear success criteria for verification |

---

## Phase Dependency Graph

```
                    ┌─────────────────┐
                    │   Phase -1      │  ← START HERE (Day 1)
                    │  Standalone     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    Phase 0      │  ← CRITICAL (Week 1-2)
                    │  Critical Fixes │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
┌───────▼───────┐   ┌────────▼────────┐   ┌──────▼───────┐
│   Phase 1     │   │    Phase 2      │   │   Phase 4    │
│ Folder Migr.  │   │ YAML Config     │   │  Attributes  │
│  (Week 3)     │   │  (Week 4-5)     │   │  (Week 3)    │
└───────┬───────┘   └────────┬────────┘   └──────┬───────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                    ┌────────▼────────┐
                    │    Phase 3      │
                    │ Commands/Match  │
                    │  (Week 6-7)     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    Phase 5      │
                    │ Admin Dashboard │
                    │  (Week 8-9)     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    Phase 6      │
                    │ Testing & Docs  │
                    │  (Week 9-10)    │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    Phase 7      │
                    │    Rollout      │
                    │   (Week 10)     │
                    └─────────────────┘
```

---

## Maximum Parallelism: 4 Agents

At peak (Weeks 3-5), you can run **4 agents simultaneously**:
1. **Agent A:** Phase 1 (Folder Migration)
2. **Agent B:** Phase 2 (YAML Infrastructure)
3. **Agent C:** Phase 4 (Extended Attributes)
4. **Agent D:** Testing Agent (writing unit tests against interfaces)

---

## Phase -1: Standalone Fixes (Day 1)

**Total:** 6 tasks | **Parallelizable:** 5 tasks | **Agents:** 2-3

### Agent Assignment

| Task | Agent | Time | Dependencies |
|------|-------|------|--------------|
| -1.1 Document SKU format | 🔵 Agent A | 15 min | None |
| -1.2 Create service interfaces | 🔵 Agent A | 1 hr | None |
| -1.3 Create exception hierarchy | 🔵 Agent B | 30 min | None |
| -1.4 Add feature flags | 🔵 Agent B | 30 min | None |
| -1.5 Align test plan | 🔵 Agent C | 1 hr | None |
| -1.6 Performance docs | ✅ Done | - | - |

### Swarming Command

```bash
# Terminal 1 (Agent A)
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-a
claude  # Paste Card -1.A

# Terminal 2 (Agent B)
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-b
claude  # Paste Card -1.B

# Terminal 3 (Agent C)
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-c
claude  # Paste Card -1.C
```

### Verification (Sequential - After All Complete)

```bash
bin/magento setup:di:compile
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Phase 0: Critical Fixes (Week 1-2)

**Total:** 11 tasks | **Parallelizable:** 7 tasks | **Agents:** 3-4

### Work Streams

#### Stream A: Database Foundation (🔵 Agent A)
| Task | Time | Dependencies |
|------|------|--------------|
| 0.1 Artist normalization table | 2 hrs | None |
| 0.2 Dashboard indexes | 1 hr | 0.1 (FK refs) |
| 0.3 TEXT → JSON columns | 1 hr | None |
| 0.4 Extract large JSON | 2 hrs | 0.1 |

#### Stream B: Concurrency & Safety (🔵 Agent B)
| Task | Time | Dependencies |
|------|------|--------------|
| 0.5 Implement file locking | 2 hrs | None |
| 0.6 Atomic progress writes | 1 hr | None |
| 0.7 Progress file validation | 1 hr | 0.6 |

#### Stream C: Data Integrity (🔵 Agent C)
| Task | Time | Dependencies |
|------|------|--------------|
| 0.8 Document SKU generation | 30 min | None |
| 0.9 Category duplication check | 1 hr | None |
| 0.10 Enforce fuzzy disabled | 1 hr | None |

#### Stream D: Performance (🔵 Agent D)
| Task | Time | Dependencies |
|------|------|--------------|
| 0.11 Soundex phonetic matching | 2 hrs | None |

### Swarming Command

```bash
# Launch 4 agents in parallel
claude --agent "Phase 0 Stream A: Database migrations (0.1-0.4)"
claude --agent "Phase 0 Stream B: Concurrency safety (0.5-0.7)"
claude --agent "Phase 0 Stream C: Data integrity (0.8-0.10)"
claude --agent "Phase 0 Stream D: Soundex matching (0.11)"
```

### Integration Point (Sequential)

After all streams complete, one agent runs migrations:
```bash
mysql magento < migrations/001_create_artist_table.sql
mysql magento < migrations/002_add_dashboard_indexes.sql
mysql magento < migrations/003_convert_json_columns.sql
mysql magento < migrations/004_create_show_metadata_table.sql
bin/magento setup:di:compile
```

---

## Phases 1, 2, 4: Maximum Parallelism (Week 3-5)

**These 3 phases can run completely in parallel** after Phase 0 completes.

### Agent A: Phase 1 - Folder Migration
| Task | Time |
|------|------|
| 1.1 Folder migration command | 4 hrs |
| 1.2 Update MetadataDownloader | 2 hrs |
| 1.3 File manifest service | 2 hrs |
| 1.4 Cache cleanup command | 2 hrs |

### Agent B: Phase 2 - YAML Infrastructure
| Task | Time |
|------|------|
| 2.1 YAML schema validator | 3 hrs |
| 2.2 YAML loader | 2 hrs |
| 2.3 Fix YAML structure | 2 hrs |
| 2.4 Validate command | 2 hrs |
| 2.5 Export hardcoded data | 3 hrs |
| 2.6 Setup artist command | 3 hrs |
| 2.7 Delete old patches | 1 hr |

### Agent C: Phase 4 - Extended Attributes
| Task | Time |
|------|------|
| 4.1 Create extended attributes | 2 hrs |
| 4.2 Update Track DTO | 1 hr |
| 4.3 Update Show DTO | 1 hr |
| 4.4 Update JSON parser | 2 hrs |
| 4.5 Update TrackImporter | 2 hrs |
| 4.6 Update BulkProductImporter | 2 hrs |
| 4.7 ShowMetadataRepository | 2 hrs |

### Agent D: Test Foundation
| Task | Time |
|------|------|
| Write interface tests | 4 hrs |
| Write unit test stubs | 4 hrs |

### Swarming Command

```bash
# Launch 4 agents in parallel
claude --agent "Phase 1: Folder migration and cleanup commands"
claude --agent "Phase 2: YAML config system and validation"
claude --agent "Phase 4: Extended EAV attributes and DTOs"
claude --agent "Test Foundation: Unit test stubs for all interfaces"
```

---

## Phase 3: Commands & Matching (Week 6-7)

**Total:** 13 tasks | **Parallelizable:** 8 tasks | **Agents:** 3

### Work Streams

#### Stream A: Command Infrastructure (🔵 Agent A)
| Task | Time | Dependencies |
|------|------|--------------|
| 3.1 BaseLoggedCommand | 2 hrs | None |
| 3.2 BaseReadCommand | 1 hr | None |
| 3.3 Unify resume/incremental | 1 hr | None |
| 3.4 New download command | 3 hrs | 3.1 |
| 3.5 Deprecate old download | 30 min | 3.4 |

#### Stream B: Track Matching (🔵 Agent B)
| Task | Time | Dependencies |
|------|------|--------------|
| 3.6 TrackMatcherService | 4 hrs | Phase 2 (YAML) |
| 3.7 Album-context matching | 2 hrs | 3.6 |
| 3.8 Unicode normalization | 2 hrs | None |

#### Stream C: Populate & Visibility (🔵 Agent C)
| Task | Time | Dependencies |
|------|------|--------------|
| 3.9 Populate command | 3 hrs | 3.6 |
| 3.10 Deprecate old populate | 30 min | 3.9 |
| 3.11 Deprecate ImportShows | 30 min | None |
| 3.12 Show-unmatched command | 2 hrs | 3.6 |
| 3.13 Enhance status command | 2 hrs | None |

### Swarming Command

```bash
# Launch 3 agents in parallel
claude --agent "Phase 3 Stream A: BaseLoggedCommand and new download command"
claude --agent "Phase 3 Stream B: TrackMatcherService with hybrid matching"
claude --agent "Phase 3 Stream C: Populate command and visibility tools"
```

---

## Phase 5: Admin Dashboard (Week 8-9)

**Total:** 21 tasks | **Parallelizable:** 12 tasks | **Agents:** 4

### Work Streams

#### Stream A: Database & Models (🔵 Agent A)
| Task | Time |
|------|------|
| 5.1-5.4 Create 4 tables | 4 hrs |
| 5.5-5.8 Create 4 models | 6 hrs |

#### Stream B: Redis & Progress (🔵 Agent B)
| Task | Time |
|------|------|
| 5.9 ProgressTracker service | 2 hrs |
| 5.10 Integrate with commands | 2 hrs |

#### Stream C: Admin Module & Grids (🔵 Agent C)
| Task | Time |
|------|------|
| 5.11 Admin module structure | 1 hr |
| 5.12 Dashboard controller | 3 hrs |
| 5.13 Artist grid | 3 hrs |
| 5.14 Import history grid | 3 hrs |
| 5.15 Unmatched tracks grid | 3 hrs |
| 5.16 Progress AJAX endpoint | 2 hrs |

#### Stream D: Charts & Widgets (🔵 Agent D)
| Task | Time |
|------|------|
| 5.17 Add ApexCharts library | 1 hr |
| 5.18 Imports per day chart | 2 hrs |
| 5.19 Match rate gauge | 2 hrs |
| 5.20 Real-time progress widget | 3 hrs |
| 5.21 Aggregation cron job | 2 hrs |

### Swarming Command

```bash
# Launch 4 agents in parallel
claude --agent "Phase 5a: Database tables and Magento models"
claude --agent "Phase 5b: Redis progress tracking integration"
claude --agent "Phase 5c: Admin grids (artist, history, unmatched)"
claude --agent "Phase 5d: ApexCharts visualizations and widgets"
```

---

## Phase 6: Testing & Documentation (Week 9-10)

**Total:** 13 tasks | **Parallelizable:** 10 tasks | **Agents:** 3-4

### Work Streams

#### Stream A: Unit Tests (🔵 Agent A)
| Task | Time |
|------|------|
| 6.1 Test LockService | 2 hrs |
| 6.2 Test TrackMatcherService | 3 hrs |
| 6.3 Test ArtistConfigValidator | 2 hrs |
| 6.4 Test StringNormalizer | 1 hr |

#### Stream B: Integration Tests (🔵 Agent B)
| Task | Time |
|------|------|
| 6.5 Full download→populate flow | 4 hrs |
| 6.6 Concurrent download protection | 2 hrs |

#### Stream C: Performance Tests (🔵 Agent C)
| Task | Time |
|------|------|
| 6.7 Matching benchmarks | 3 hrs |
| 6.8 BulkProductImporter benchmark | 2 hrs |
| 6.9 Dashboard query benchmark | 2 hrs |

#### Stream D: Documentation (🔵 Agent D)
| Task | Time |
|------|------|
| 6.10 Update main plan doc | 1 hr |
| 6.11 Developer guide | 4 hrs |
| 6.12 Admin user guide | 4 hrs |
| 6.13 API documentation | 2 hrs |

### Swarming Command

```bash
# Launch 4 agents in parallel
claude --agent "Phase 6 Unit Tests: LockService, TrackMatcher, Validator, Normalizer"
claude --agent "Phase 6 Integration Tests: E2E flow and concurrency"
claude --agent "Phase 6 Performance Tests: Benchmarks for matching, import, dashboard"
claude --agent "Phase 6 Documentation: Developer guide, Admin guide, API docs"
```

---

## Phase 7: Rollout (Week 10)

**Sequential Phase** - One agent, careful execution.

**Agent:** Lead/Senior agent with production access

### Tasks (Sequential)
1. 7.1 Verify migrations in staging
2. 7.2 Test with production data clone
3. 7.3 Load test (100k+ products)
4. 7.4 Dashboard performance test
5. 7.5-7.9 Deploy phases 1-5
6. 7.10-7.12 Monitoring and runbook

---

## Optimal Agent Allocation Summary

| Week | Agents | Phases | Focus |
|------|--------|--------|-------|
| 1 | 3-4 | -1, 0 | Foundation |
| 2 | 3-4 | 0 | Critical fixes completion |
| 3 | **4** | 1, 2, 4, Tests | **Peak parallelism** |
| 4 | **4** | 1, 2, 4, Tests | **Peak parallelism** |
| 5 | **4** | 2, 3, 4, Tests | **Peak parallelism** |
| 6 | 3 | 3 | Commands & matching |
| 7 | 3 | 3 | Commands completion |
| 8 | 4 | 5a, 5b | Dashboard MVP |
| 9 | 4 | 5b, 6 | Dashboard + Testing |
| 10 | 1-2 | 6, 7 | Docs + Rollout |

---

## Agent Prompt Templates

### Database Agent (Phase 0, 5)
```
You are working on the 8PM Archive.org import rearchitecture.
Focus: Database migrations and schema patches.
Files: src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/
Reference: docs/import-rearchitecture/FIXES.md (Fixes #1, #7, #18, #19)
Test after: mysql queries to verify tables/indexes exist
```

### Service Agent (Phase 0, 3)
```
You are working on the 8PM Archive.org import rearchitecture.
Focus: PHP service classes and interfaces.
Files: src/app/code/ArchiveDotOrg/Core/Model/, Api/
Reference: docs/import-rearchitecture/FIXES.md (Fixes #8, #9, #10, #41)
Test after: bin/magento setup:di:compile
```

### CLI Agent (Phase 3)
```
You are working on the 8PM Archive.org import rearchitecture.
Focus: Console commands and CLI experience.
Files: src/app/code/ArchiveDotOrg/Core/Console/Command/
Reference: docs/import-rearchitecture/04-PHASE-3-COMMANDS.md
Test after: bin/magento list | grep archive
```

### Admin UI Agent (Phase 5)
```
You are working on the 8PM Archive.org import rearchitecture.
Focus: Magento admin module, grids, controllers.
Files: src/app/code/ArchiveDotOrg/Admin/
Reference: docs/import-rearchitecture/06-PHASE-5-DASHBOARD.md
Test after: Navigate to Admin > Content > Archive.org Import
```

### Testing Agent (Phase 6)
```
You are working on the 8PM Archive.org import rearchitecture.
Focus: Unit tests, integration tests, benchmarks.
Files: src/app/code/ArchiveDotOrg/Core/Test/
Reference: docs/import-rearchitecture/07-PHASE-6-TESTING.md
Test after: bin/magento dev:tests:run unit --filter=ArchiveDotOrg
```

---

## Critical Handoff Points

These are **synchronization points** where all parallel agents must complete before proceeding:

1. **After Phase -1:** All interfaces created → DI compile must pass
2. **After Phase 0:** Database migrations run → All tables verified
3. **After Phase 2:** YAML system complete → All 35 artists validated
4. **After Phase 3:** Commands working → Status command shows all metrics
5. **After Phase 5a:** Dashboard MVP → All grids load <200ms
6. **Before Phase 7:** All tests pass → Go/No-Go decision

---

## Git Worktree Setup

**Why worktrees?** Each agent gets its own working directory. No branch switching, no stashing, no conflicts during parallel work.

### Initial Setup

```bash
# From main repo
cd /Users/chris.majorossy/Education/8pm

# Create worktrees directory (outside main repo)
mkdir -p ../8pm-worktrees

# Create worktree for each agent
git worktree add ../8pm-worktrees/agent-a feature/agent-a
git worktree add ../8pm-worktrees/agent-b feature/agent-b
git worktree add ../8pm-worktrees/agent-c feature/agent-c
git worktree add ../8pm-worktrees/agent-d feature/agent-d
```

### Directory Structure After Setup

```
Education/
├── 8pm/                    ← Main repo (stay on main branch)
│   └── ...
└── 8pm-worktrees/          ← Agent worktrees
    ├── agent-a/            ← Agent A works here
    ├── agent-b/            ← Agent B works here
    ├── agent-c/            ← Agent C works here
    └── agent-d/            ← Agent D works here
```

### Assign Agent to Worktree

When starting a new agent session:

```bash
# Agent A prompt includes:
"Your working directory is: /Users/chris.majorossy/Education/8pm-worktrees/agent-a"
```

### Merge Workflow

After an agent completes their task:

```bash
# From main repo
cd /Users/chris.majorossy/Education/8pm

# Merge agent's work
git merge feature/agent-a --no-ff -m "Phase -1: Service interfaces and SKU docs"

# Or cherry-pick specific commits
git cherry-pick <commit-hash>

# Verify
bin/magento setup:di:compile
```

### Reset Worktree for Next Task

```bash
# Update worktree to latest main
cd ../8pm-worktrees/agent-a
git fetch origin
git reset --hard origin/main

# Or delete and recreate
cd /Users/chris.majorossy/Education/8pm
git worktree remove ../8pm-worktrees/agent-a
git branch -D feature/agent-a
git worktree add ../8pm-worktrees/agent-a feature/agent-a
```

### Cleanup After Phase Complete

```bash
# Remove all worktrees
git worktree remove ../8pm-worktrees/agent-a
git worktree remove ../8pm-worktrees/agent-b
git worktree remove ../8pm-worktrees/agent-c
git worktree remove ../8pm-worktrees/agent-d

# Delete branches
git branch -D feature/agent-a feature/agent-b feature/agent-c feature/agent-d

# Or keep for history
git branch -m feature/agent-a archive/phase1-agent-a
```

### Docker Considerations

Each worktree is a full copy, but they share:
- `.git` directory (in main repo)
- Git history

They do NOT share:
- `vendor/` (each needs own composer install)
- `var/` (cache, logs)
- `node_modules/` (frontend)

**Option 1: Shared Docker (Recommended for backend work)**
```bash
# Point all agents at same Docker containers
# Each worktree just has different PHP source
# Works because Magento reads from mounted volume
```

**Option 2: Separate Docker per worktree (If needed)**
```bash
# Each worktree needs unique ports
# Modify compose.yaml in each worktree
```

---

## Risk Mitigation

### Agent Conflicts

**Risk:** Two agents modify same file simultaneously.

**Mitigation:**
- **Use git worktrees** - each agent has isolated directory
- Assign clear file ownership to each stream
- Cards explicitly list which files each agent touches

### Integration Failures

**Risk:** Parallel work doesn't integrate cleanly.

**Mitigation:**
- Run `bin/magento setup:di:compile` after each stream completes
- Integration tests run after any merge to develop
- Daily sync meeting: "What I built, what I need, what's blocking"

### Scope Creep

**Risk:** Agents expand scope beyond assigned tasks.

**Mitigation:**
- Each agent gets specific task list from phase docs
- If agent encounters related issue, log it but don't fix
- New issues go to FIXES.md for prioritization

---

## Time Savings Estimate

| Approach | Duration | Agents |
|----------|----------|--------|
| Sequential | 9-10 weeks | 1 |
| 2-Agent Swarm | 6-7 weeks | 2 |
| **4-Agent Swarm** | **5-6 weeks** | **4** |

**Savings:** 4-5 weeks with maximum parallelism.

---

## Quick Reference: What to Swarm

### Best for Swarming (Independent Work)
- Database migrations (different tables)
- Service implementations (different interfaces)
- CLI commands (different commands)
- Unit tests (different classes)
- Documentation (different guides)

### Not Good for Swarming (Sequential)
- DI compile verification
- Migration execution order
- Integration testing
- Production deployment
- Rollback procedures

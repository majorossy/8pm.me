# Git History Reorganization Plan - 8PM Project

## Executive Summary

Transform **61 messy commits** into **~60-70 clean, atomic commits** across logical categories. The project has grown significantly since the original plan (Jan 29), with 27 new commits added.

**Current State:** 61 commits, 30 (49%) with poor messages ("adf", "stuff", "more code")
**Target:** Professional commit history suitable for open-sourcing
**Duration:** ~4-5 hours of focused work
**Risk:** Low (with proper backups)

---

## Current Commit Analysis

### Commit Quality Breakdown

| Quality | Count | Percentage | Examples |
|---------|-------|------------|----------|
| **Poor Messages** | 30 | 49% | "adf", "stuff", "more code", "code dump" |
| **Good Messages** | 31 | 51% | Phase commits, feature additions |

### Poor Message Patterns

| Pattern | Count | Commits |
|---------|-------|---------|
| `adf` | 3 | Most recent (Feb 2) |
| `stuff` | 4 | `4786ae904`, `f4a5eadc2`, `2018f8d79`, `d7fc4d9f0` |
| `more code` | 14 | Jan 29-30 bulk commits |
| `code dump` | 5 | Mixed large commits |
| `morning work` | 4 | `0fc35a338`, `3633f5e95`, etc. |
| `buynch more code` | 5 | Typo pattern |

### Good Commits to Preserve

**Phase Architecture (8 commits):**
- `228370d86` - Phase -1: Interfaces, exceptions, feature flags
- `453ccb2ef` - Phase 0: Database, concurrency, soundex matching
- `78c4e70ec` - Phase 1: Folder migration, file manifest
- `9a033815d` - Phase 2: YAML configuration system
- `34fd610e3` - Phase 3: Commands, hybrid matching
- `a3f36ceeb` - Phase 0 Bugfix + Phase 4: Extended attributes
- `9789472cc` - Phase 5: Admin dashboard
- `af624612f` - Phase 6: Testing and documentation

**Recent Features (well-named):**
- `fbf3188a1` - Add mini queue to mobile full player
- `19bc4e38f` - Add resume playback UI
- `aa08d3029` - Add download button, keyboard hints
- `cf3197fb2` - Add queue preview tooltip
- `9a610064b` - Add Share button, equalizer animations
- `19370010f` - Rebrand from EIGHTPM to 8pm.me

### Large Commits Requiring Splitting

| Commit | Files | Current Message | Should Split Into |
|--------|-------|-----------------|-------------------|
| `4786ae904` | 67 | "stuff" | MCP setup, SEO docs, audio viz, artist features |
| `69fc8b9d3` | 45 | "ore code dump" | Cookie consent, analytics, validation, DMCA/policies |
| `0738a435c` | Large | "code dump lots of claude work" | Metadata sync, artist status, bin scripts |
| `bb3b81d71` | Large | "dump of code" | Multiple backend + frontend features |

---

## Target Commit Structure (~65 commits)

### Category 1: Foundation & Infrastructure (8 commits)
```
1. chore: add Magento core files to .gitignore
2. build(docker): enable Docker Compose healthchecks
3. feat(tooling): add bin/rs command center with interactive TUI
4. feat(tooling): add file watcher for Docker volume sync
5. feat(tooling): add bin/import-all-artists bulk setup script
6. feat(tooling): add MCP servers (docker-8pm, redis-8pm, mysql-8pm)
7. feat(deployment): add 5-phase production deployment system
8. docs(project): create comprehensive CLAUDE.md and runbooks
```

### Category 2: Backend - Import System (8 commits)
```
9. feat(backend): Phase -1 - Import system interfaces and exceptions
10. feat(backend): Phase 0 - Database schema and concurrency handling
11. feat(backend): Phase 1 - Folder migration and file manifest
12. feat(backend): Phase 2 - YAML artist configuration system
13. feat(backend): Phase 3 - CLI commands and hybrid track matching
14. fix(backend): Phase 0 bugfix - Lock management improvements
15. feat(backend): Phase 4 - Extended product attributes
16. refactor(backend): standardize CLI command naming
```

### Category 3: Backend - Admin & Features (9 commits)
```
17. feat(backend): Phase 5 - Admin dashboard with real-time metrics
18. feat(backend): Phase 6 - Comprehensive unit tests
19. feat(backend): add import history tracking system
20. feat(backend): add artist enrichment (Wikipedia/Brave Search)
21. feat(backend): add album artwork integration
22. feat(backend): add multi-quality audio support
23. feat(backend): add artist status sync and statistics
24. feat(backend): add GraphQL resolvers for band statistics
25. fix(backend): catalog category product indexer fix
```

### Category 4: Frontend - Core Redesign (10 commits)
```
26. feat(frontend): complete Campfire theme redesign
27. feat(frontend): add desktop navigation (TopBar, sidebar)
28. feat(frontend): add mobile navigation with haptic feedback
29. feat(frontend): add search overlay and route
30. feat(frontend): add library and playlists features
31. feat(frontend): add authentication system (Supabase + Magento)
32. feat(frontend): enhance album page with cassette UI
33. feat(frontend): add artist page components (timeline, stats, social)
34. feat(frontend): add FestivalHero and homepage improvements
35. feat(frontend): add early access gate for development
```

### Category 5: Frontend - Audio Player (8 commits)
```
36. feat(frontend): redesign BottomPlayer with enhanced controls
37. feat(frontend): add EightPmFullPlayer (mobile full-screen)
38. feat(frontend): add audio visualizations (VUMeter, Waveform, etc.)
39. feat(frontend): add crossfade transitions
40. feat(frontend): add keyboard shortcuts and media session
41. feat(frontend): add sleep timer feature
42. feat(frontend): add queue preview tooltip
43. feat(frontend): add resume playback UI
```

### Category 6: Frontend - Quality & Polish (8 commits)
```
44. feat(frontend): add quality selector for multi-bitrate audio
45. feat(frontend): add offline support and loading states
46. feat(frontend): add LoadingBar for navigation progress
47. feat(frontend): enhance queue with swipeable items
48. feat(frontend): add download button and share functionality
49. feat(frontend): add version carousel with ratings
50. feat(frontend): add static pages (About, Privacy, Terms, etc.)
51. feat(frontend): add festival sort context and algorithm selector
```

### Category 7: SEO & Accessibility (6 commits)
```
52. fix(frontend): improve color contrast for WCAG compliance
53. feat(seo): add structured data (Schema.org)
54. feat(seo): add sitemap generation and robots.txt
55. docs(seo): create keyword research documentation
56. docs(seo): create master SEO implementation plan
57. feat(frontend): add cookie consent banner (GDPR)
```

### Category 8: Documentation (8 commits)
```
58. docs(import): add import rearchitecture overview
59. docs(import): document Phases 0-6 implementation
60. docs(backend): add API documentation and command guides
61. docs(backend): document testing and verification
62. docs(project): add developer guide and initial setup
63. docs(project): add monitoring guide and runbook
64. docs(seo): document accessibility and performance
65. chore: rebrand from EIGHTPM/8PM to 8pm.me
```

---

## Implementation Steps

### Step 0: Switch Remote to 8pm.me Repository
```bash
cd /Users/chris.majorossy/Education/8pm

# Current remote: https://github.com/majorossy/eightpm.git
# Target remote:  https://github.com/majorossy/8pm.me

# Update the remote URL
git remote set-url origin https://github.com/majorossy/8pm.me.git

# Verify the change
git remote -v
# Should show: origin https://github.com/majorossy/8pm.me.git
```

### Step 1: Create Backup (CRITICAL)
```bash
# Create local backup branch with timestamp
BACKUP_NAME="backup-before-rebase-$(date +%Y%m%d-%H%M%S)"
git branch $BACKUP_NAME

# Note: We'll force push to 8pm.me, so backup stays local only
# The old eightpm.git repo remains untouched as additional backup

# Verify backup
git branch | grep backup
```

### Step 2: Commit Current Changes First
```bash
# Stage and commit current uncommitted work
git add -A
git commit -m "WIP: pre-reorganization snapshot"
```

### Step 3: Start Interactive Rebase
```bash
# Find the initial commit
INITIAL_COMMIT=$(git rev-list --max-parents=0 HEAD)

# Start interactive rebase from root
git rebase -i --root
```

### Step 4: In Rebase Editor

Mark commits appropriately:
- `edit` - Large commits to split
- `reword` - Bad messages, good content
- `squash` - Tiny fixes into previous
- `pick` - Good commits (Phase commits)

### Step 5: Splitting Large Commits

When rebase stops at `edit`:
```bash
# Reset to stage changes individually
git reset HEAD^

# Stage and commit by feature
git add src/path/to/feature/
git commit -m "feat(scope): descriptive message"

# Continue for each logical group
git rebase --continue
```

### Step 6: Verification After Each Category
```bash
# Backend check
bin/magento setup:upgrade --dry-run

# Frontend check
cd frontend && npm run build

# Database check
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg%';"
```

---

## Commit Message Template

```
<type>(<scope>): <subject>

<body - explain WHY, not WHAT>

Key changes:
- Bullet point 1
- Bullet point 2

Technical notes:
- Implementation details

Related docs: <filename>
```

**Types:** feat, fix, docs, refactor, test, chore, build
**Scopes:** backend, frontend, tooling, seo, import

> ⚠️ **DO NOT add "Co-Authored-By: Claude" signatures** - These are original commits being reorganized, not new code written with AI assistance. Adding Claude's signature would misrepresent authorship history.

---

## Rollback Plan

```bash
# If something goes wrong
git rebase --abort

# Or reset to backup
git reset --hard $BACKUP_NAME
```

---

## Post-Reorganization

### 1. Force Push to 8pm.me Repository
```bash
# After verification, push to the new repo (replaces old content)
git push --force origin main

# Note: Using --force (not --force-with-lease) since we're replacing
# the entire repo content, not updating existing history
```

### 2. Create Release Tags
```bash
git tag -a v1.0-foundation -m "Foundation complete"
git tag -a v1.1-backend -m "Backend import system"
git tag -a v1.2-frontend -m "Frontend core features"
git tag -a v1.3-player -m "Audio player complete"
git tag -a v1.4-polish -m "Quality and polish"
git tag -a v1.5-seo -m "SEO and accessibility"
git push origin --tags
```

### 3. Generate Changelog
```bash
npx conventional-changelog -p angular -i CHANGELOG.md -s
```

---

## Key Files Reference

### Backend (Magento)
- `src/app/code/ArchiveDotOrg/Core/` - Main import module (26 CLI commands)
- `src/app/code/ArchiveDotOrg/Admin/` - Admin dashboard module
- `src/app/code/ArchiveDotOrg/Core/etc/schema.graphqls` - GraphQL schema

### Frontend (Next.js)
- `frontend/components/BottomPlayer.tsx` - Main player
- `frontend/components/EightPmFullPlayer.tsx` - Mobile full player
- `frontend/components/AudioVisualizations.tsx` - 5 visualizations
- `frontend/context/PlayerContext.tsx` - Player state
- `frontend/app/globals.css` - Campfire theme

### Tooling
- `bin/rs` - Command center (963 lines)
- `bin/import-all-artists` - Bulk import script
- `mcp/` - MCP servers (docker, redis, mysql)

### Documentation
- `CLAUDE.md` - Project documentation
- `docs/seo-implementation/` - SEO docs
- `docs/COMMAND_GUIDE.md` - CLI reference

---

## Success Criteria

- [ ] ~65 atomic commits created
- [ ] All commits use conventional commit format
- [ ] No functional changes (git diff shows nothing vs backup)
- [ ] Backend builds: `bin/magento setup:upgrade` succeeds
- [ ] Frontend builds: `npm run build` succeeds
- [ ] Database tables intact (5 archivedotorg tables)
- [ ] History tells professional development story
- [ ] Suitable for open-source release

---

## Time Estimate

| Step | Duration |
|------|----------|
| Backup & preparation | 10 min |
| Interactive rebase setup | 15 min |
| Splitting large commits | 2-3 hours |
| Rewording bad messages | 45 min |
| Verification | 30 min |
| Tags & changelog | 15 min |
| **Total** | **4-5 hours** |

---

**Created:** 2026-02-02
**Updated from:** purrfect-dancing-acorn.md (Jan 29 version)
**Location:** docs/GIT_REORGANIZATION_PLAN.md

# Magento 2 Headless Docker Development Environment

## Overview
Mage-OS 1.0.5 (Magento Open Source fork) as headless backend with Next.js/React frontend.

## ⚠️ IMPORTANT - Frontend Port
**The Next.js frontend ALWAYS runs on port 3001, not 3000.**
- Development: `cd frontend && npm run dev` → http://localhost:3001
- Never use port 3000 - it will cause confusion

## Quick Start

```bash
bin/start   # Start Magento containers
bin/stop    # Stop Magento containers

# Frontend (separate - runs on port 3001)
cd frontend && npm run dev
```

## Project Control Center (`bin/rs`)

Comprehensive command center with **arrow key navigation** for managing all project services and operations.

**Interactive menu with arrow keys:**
```bash
bin/rs              # Interactive TUI menu (use ↑↓ arrows, Enter to select, q to quit)
bin/rs help         # Show all available commands
```

**Navigation:**
- `↑` / `↓` arrows - Navigate through menu options
- `Enter` - Execute selected command
- `q` - Quit menu
- Visual highlighting with `▶` indicator

**Quick examples:**
```bash
# Restart services
bin/rs phpfpm              # Restart PHP-FPM
bin/rs frontend            # Restart Next.js (clears .next cache)
bin/rs web                 # Restart Nginx + PHP-FPM
bin/rs all                 # Restart everything

# Magento operations
bin/rs cache-flush         # Clear all Magento cache
bin/rs reindex             # Reindex all
bin/rs compile             # DI compile
bin/rs full-deploy         # Full deployment (upgrade + compile + static + cache + index)

# Development tools
bin/rs fixperms            # Fix file permissions
bin/rs xdebug-on           # Enable Xdebug
bin/rs status              # Health check all services
bin/rs ports               # Check port usage

# Workflows
bin/rs quick-fix           # Cache + compile (fast)
bin/rs after-pull          # Full post-git-pull workflow
bin/rs reset-dev           # Reset development environment

# Docker operations
bin/rs ps                  # Container status
bin/rs docker-logs         # Tail all logs
bin/rs docker-clean        # Clean volumes/orphans

# Monitoring
bin/rs logs-phpfpm         # Tail PHP-FPM logs
bin/rs logs-all            # Tail all service logs
```

### Command Categories

**Restart Services:**
- Individual: `app`, `phpfpm`, `db`, `redis`, `opensearch`, `rabbitmq`, `mailcatcher`, `phpmyadmin`, `frontend`
- Groups: `backend`, `web`, `data`, `cache`, `all`

**Magento Cache & Index:**
- `cache-flush`, `cache-clean`, `reindex`, `index-status`, `index-reset`

**Magento Setup & Deploy:**
- `compile`, `upgrade`, `static-deploy`, `mode-dev`, `mode-prod`, `mode-show`, `full-deploy`

**Magento Config & Modules:**
- `module-status`, `config-import`, `config-export`

**Docker Operations:**
- `docker-rebuild`, `docker-logs`, `docker-clean`, `docker-reset-db`, `ps`

**Development Tools:**
- `fixperms`, `fixowns`, `xdebug-on`, `xdebug-off`, `xdebug-status`

**Monitoring & Logs:**
- `logs-app`, `logs-phpfpm`, `logs-db`, `logs-all`, `status`, `ports`

**Workflows:**
- `quick-fix` - Cache + compile (use after code changes)
- `after-pull` - Full workflow after git pull (composer + upgrade + compile + cache + index + npm install)
- `reset-dev` - Reset entire dev environment (stop, clean caches, restart)
- `full-reset` - Complete reset including database (dangerous!)

## URLs

| URL | Purpose |
|-----|---------|
| **http://localhost:3001** | **Next.js Frontend (ALWAYS 3001)** |
| https://magento.test/graphql | GraphQL API |
| https://magento.test/admin | Admin Panel |
| http://localhost:8080 | phpMyAdmin |
| http://localhost:1080 | Mailcatcher |

## Admin Credentials
- Username: `john.smith`
- Password: `password123`

## Services & Ports

| Service | Container | Host Port | Internal Port |
|---------|-----------|-----------|---------------|
| **Frontend** | npm run dev | **3001** | **3001** |
| Nginx | 8pm-app-1 | 80, 443 | 8000, 8443 |
| PHP-FPM | 8pm-phpfpm-1 | - | 9000 |
| MariaDB | 8pm-db-1 | 3307 | 3306 |
| Redis/Valkey | 8pm-redis-1 | 6380 | 6379 |
| OpenSearch | 8pm-opensearch-1 | 9201, 9301 | 9200, 9300 |
| RabbitMQ | 8pm-rabbitmq-1 | 15673, 5673 | 15672, 5672 |
| Mailcatcher | 8pm-mailcatcher-1 | 1080 | 1080 |
| phpMyAdmin | 8pm-phpmyadmin-1 | 8080 | 80 |

**Note:**
- **Frontend runs on port 3001 (via npm, not Docker)** - Docker frontend service is disabled in compose.yaml
- Non-standard host ports (3307, 6380, etc.) are used to avoid conflicts with other Docker projects
- **phpMyAdmin** only runs in dev mode (loaded from compose.dev.yaml)

## Common Commands

```bash
bin/magento <command>     # Run Magento CLI
bin/composer <command>    # Run Composer
bin/mysql                 # Access MySQL CLI
bin/bash                  # Shell into PHP container
bin/restart               # Restart containers
bin/status                # Check container status
bin/xdebug enable         # Enable Xdebug
bin/xdebug disable        # Disable Xdebug
bin/cache-clean           # Watch and auto-clean cache

# Auto-sync files from host to Docker container
bin/watch-start           # Start file watcher (background)
bin/watch-stop            # Stop file watcher
bin/watch-status          # Check watcher status
bin/watch                 # Run watcher in foreground (see logs)
```

## Magento CLI Examples

```bash
bin/magento cache:flush                    # Clear cache
bin/magento indexer:reindex                # Reindex
bin/magento setup:upgrade                  # Run upgrades after module changes
bin/magento setup:di:compile               # Compile DI
bin/magento setup:static-content:deploy -f # Deploy static content
bin/magento module:status                  # List modules
bin/magento deploy:mode:show               # Show deploy mode
```

## Database Access

**Via CLI:**
```bash
bin/mysql
```

**Via phpMyAdmin:**
- URL: http://localhost:8080
- Server: db
- Username: magento
- Password: magento

**Direct connection (from host):**
- Host: 127.0.0.1
- Port: 3307
- Database: magento
- Username: magento
- Password: magento

## File Structure

```
8pm/
├── bin/              # Helper scripts (75+ available)
├── compose.yaml      # Docker Compose config (frontend disabled - runs on host)
├── docs/             # Project documentation
│   ├── album-artwork/    # Album artwork integration docs (blocked)
│   ├── frontend/         # Accessibility, search fix docs
│   └── import-rearchitecture/  # ETL rearchitecture plans
├── env/              # Environment files
│   ├── db.env
│   ├── magento.env
│   ├── opensearch.env
│   ├── phpfpm.env
│   └── rabbitmq.env
├── frontend/         # Next.js frontend (runs on host, port 3001)
├── src/              # Magento source code
│   └── app/code/ArchiveDotOrg/  # Custom modules
└── CLAUDE.md         # This file
```

## Notes

- **2FA disabled** for development convenience
- **MariaDB 10.6** used (Mage-OS 1.0.5 doesn't support MariaDB 11.x)
- **Valkey** used instead of Redis (drop-in replacement)
- **OpenSearch 2.12** used instead of Elasticsearch
- Source code is in `src/` directory after installation
- SSL certificate auto-generated and trusted locally
- **Docker loads multiple compose files:** compose.yaml + compose.healthcheck.yaml + compose.dev.yaml
- **75+ bin/ scripts available** - run `ls bin/` to see all options

## Docker File Sync System

**Why named volumes instead of bind mounts?**

This project uses Docker **named volumes** (`appdata`) instead of **bind mounts** for Magento files because bind mounts are extremely slow on macOS (10-50x slower due to virtualization overhead). The frontend runs directly on the host to avoid this issue entirely.

**How it works:**

1. **Edit files on your host** (in `src/`)
2. **Auto-sync with file watcher:**
   ```bash
   bin/watch-start   # Starts background watcher
   ```
   - Watches `src/app/code/ArchiveDotOrg/` for changes
   - Auto-syncs to container within 2 seconds of save
   - Runs in background (logs to `/tmp/8pm-watch.log`)

3. **Manual sync (if needed):**
   ```bash
   bin/copytocontainer app/code/ArchiveDotOrg    # Sync specific path
   bin/copytocontainer --all                      # Sync everything (slow)
   ```

**File watcher commands:**

```bash
bin/watch-start    # Start auto-sync in background
bin/watch-stop     # Stop auto-sync
bin/watch-status   # Check if running + recent activity
bin/watch          # Run in foreground (see live logs)
```

**Recommended workflow:**

1. Start watcher once: `bin/watch-start`
2. Edit files normally in your IDE
3. Changes auto-sync to container
4. Watcher stays running until you stop it or reboot

**When to restart watcher:**

- After system reboot (PID file in `/tmp`)
- If you see "changes not appearing" issues
- Check status first: `bin/watch-status`

## Troubleshooting

**Containers won't start (port conflicts):**
```bash
docker ps -a  # Check for other containers using same ports
bin/stop && bin/start
```

**Permission issues:**
```bash
bin/fixowns
bin/fixperms
```

**Clear all caches:**
```bash
bin/magento cache:flush
bin/magento cache:clean
```

**Re-run setup after DB issues:**
```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

## Frontend Development

### Running the Frontend
```bash
cd frontend
npm run dev
# Always runs on http://localhost:3001
```

### ⚠️ IMPORTANT - Cache Management (FOR CLAUDE)
**ALWAYS use these helper scripts instead of manually deleting `.next/` or cache files:**

```bash
frontend/bin/refresh   # Kill server, clean cache, restart (SAFEST - use this)
frontend/bin/clean     # Clean cache only (only if server NOT running)
npm run refresh        # Shortcut (from frontend/)
```

**When Claude should use `frontend/bin/refresh`:**
- Changes not appearing after editing files (stale `.next/` cache)
- TypeScript errors that won't resolve
- After git pull or branch switching
- "Cannot find module" errors related to `.next/`
- Before major code changes that might affect build
- **NEVER manually delete `.next/` while server is running - always use bin/refresh**

**How it works:**
1. Kills any process on port 3001
2. Removes `.next/`, `tsconfig.tsbuildinfo`, `node_modules/.cache/`
3. Restarts dev server (~1.3s startup)
4. Always safe to run

**Hot Module Replacement (HMR) is enabled:**
- Most file saves should auto-refresh in <2 seconds
- Only use `bin/refresh` when HMR fails or cache is stale
- Startup time of ~1.3s indicates healthy dev environment

### Theme
- **Only Campfire theme is available** (warm analog aesthetic)
- Theme switcher has been removed - hardcoded to `theme-campfire`
- Color palette: Dark backgrounds (`#1c1a17`), warm accents (`#d4a060`, `#e8a050`)
- Layout:
  - Desktop: Left sidebar navigation
  - Mobile: Bottom tab navigation with haptic feedback

### Key Frontend Files
- `frontend/context/ThemeContext.tsx` - Theme provider (hardcoded to campfire)
- `frontend/components/ClientLayout.tsx` - Main layout wrapper
- `frontend/components/JamifyTopBar.tsx` - Desktop top bar
- `frontend/components/JamifyNavSidebar.tsx` - Desktop left sidebar
- `frontend/components/JamifyMobileNav.tsx` - Mobile bottom nav
- `frontend/package.json` - Dev server configured for port 3001

### Environment Variables
File: `frontend/.env.local`
```bash
MAGENTO_GRAPHQL_URL=https://magento.test/graphql
NEXT_PUBLIC_MAGENTO_MEDIA_URL=https://magento.test/media
NODE_TLS_REJECT_UNAUTHORIZED=0  # Allow self-signed certs in dev
# NEXT_PUBLIC_SUPABASE_URL=...  # Optional cross-device sync (disabled)
```

### Audio Features (New - Jan 2026)
- **Audio Visualizations** (`frontend/components/AudioVisualizations.tsx`)
  - VUMeter, SpinningReel, Waveform, EQBars, PulsingDot
- **Crossfade** (`frontend/hooks/useCrossfade.ts`) - Smooth track transitions
- **Audio Analyzer** (`frontend/hooks/useAudioAnalyzer.ts`) - Web Audio API integration
- **Keyboard Shortcuts** - Space (play/pause), N/P (next/prev), S (shuffle), R (repeat)
- **Sleep Timer** (`frontend/hooks/useSleepTimer.ts`) - Auto-pause with preset durations
- **Media Session API** (`frontend/hooks/useMediaSession.ts`) - Lock screen controls
- **Haptic Feedback** (`frontend/hooks/useHaptic.ts`) - Vibration API for mobile

### Artist Page Features
- **Band Members Timeline** (`frontend/components/BandMembersTimeline.tsx`)
  - Visual timeline showing member history, instruments, tenure
  - Distinguishes current vs. former members
- **Band Statistics** - Metrics visualization for artists
- **Band Biography, Links, Social Widgets** - Artist info components

### Spotify Feature Parity
**Current Status: ~70% (Phase 1 Complete)**

| Phase | Status | Features |
|-------|--------|----------|
| Phase 1 | ✅ Done | Search, Like button, Library, Playlists, Recently played |
| Phase 2 | ⏳ Planned | Queue save, Sleep timer UI, Lyrics, Share |
| Phase 3 | ⏳ Planned | Haptic polish, Crossfade UI, Follow artists, Cast |

See `docs/SPOTIFY_FEATURE_PARITY_ROADMAP.md` for full details.

## Custom Magento Modules

### ArchiveDotOrg_Core
Imports live concert recordings from Archive.org into Magento products.

**CLI Commands (13 total):**
```bash
# Import & Sync
bin/magento archivedotorg:import-shows "Grateful Dead" --limit=50
bin/magento archivedotorg:sync-albums
bin/magento archivedotorg:refresh-products "STS9" --fields=rating,downloads
bin/magento archivedotorg:cleanup-products --collection=GratefulDead

# Metadata & Tracks
bin/magento archivedotorg:download-metadata "Phish"
bin/magento archivedotorg:populate-tracks

# Artist Enrichment
bin/magento archive:artist:enrich "Phish" --fields=bio,origin,stats --force

# Album Artwork
bin/magento archive:artwork:download "Phish" --limit=20
bin/magento archive:artwork:update
bin/magento archive:artwork:set-url <category_id> <url>
bin/magento archive:artwork:retry

# Utilities
bin/magento archivedotorg:status --test-collection=GratefulDead
bin/magento archivedotorg:cache-clear
```

**REST API Endpoints:** (require admin token)
```
POST   /V1/archive/import              - Start import job
GET    /V1/archive/import/:jobId       - Get job status
DELETE /V1/archive/import/:jobId       - Cancel running job
GET    /V1/archive/collections         - List configured collections
GET    /V1/archive/collections/:id     - Get collection details
DELETE /V1/archive/products/:sku       - Delete imported product
```

**Cron Jobs:**
| Job | Schedule | Purpose |
|-----|----------|---------|
| `archivedotorg_import_shows` | 2 AM daily | Auto-import from collections |
| `archivedotorg_sync_albums` | 4 AM daily | Sync categories with products |
| `archivedotorg_cleanup_progress` | Sunday midnight | Clean stale progress files |
| `archivedotorg_process_import_queue` | Every minute | Process async import queue |

**GraphQL Extensions:** 20+ fields on ProductInterface (song_title, show_venue, archive_downloads, etc.)

**Import History Tracking:** (NEW - 2026-01-29)
All CLI imports automatically log to `archivedotorg_import_run` table with:
- ✅ WHO ran it (`started_by`: cli:username or admin:username)
- ✅ WHAT command ran (`command_name`)
- ✅ WHEN it started/completed (timestamps)
- ✅ HOW LONG it took (`duration_seconds`)
- ✅ HOW MUCH memory used (`memory_peak_mb`)
- ✅ HOW MANY items processed/successful
- ✅ Auto-updates `archivedotorg_artist_status` after successful imports

View in Admin: **Catalog > Archive.org > Import History** (21 records currently)

**Database Tables:**
- `archivedotorg_activity_log` - Import operation tracking
- `archivedotorg_import_run` - Import history with full metrics (NEW)
- `archivedotorg_artist_status` - Per-artist statistics (auto-updated)
- `archivedotorg_studio_albums` - Album artwork cache

**Admin Interface:** Catalog > Archive.org
- **Control Center** - AJAX dashboard with real-time operations
- **Dashboard** - Database statistics and historical metrics
- **Artists** - Per-artist status and match rates
- **Imported Products** - Grid of all imported products
- **Import History** - Full audit trail of imports
- **Import Jobs** - CLI instructions (async jobs coming soon)
- **Unmatched Tracks** - Failed matches with YAML export
- **Configuration** - Module settings

### Album Artwork Integration
**Status:** 🚨 BLOCKED - MusicBrainz/CoverArtArchive blocking connections

**Workaround:** Wikipedia API is working as fallback for artwork.

**When unblocked:**
1. Start proxy: `bin/proxy` (runs on port 3333)
2. Run: `bin/magento archive:artwork:download "Artist" --limit=10`

See `docs/album-artwork/` for full documentation.

### Artist Enrichment
**Status:** ✅ COMPLETE - Multi-tier enrichment system

**Populates Category Attributes:**
- `band_extended_bio` - Biography from Wikipedia
- `band_origin_location` - City/country of origin
- `band_years_active` - Active years
- `band_formation_date` - Year band formed
- `band_genres` - Musical genres
- `band_official_website` - Official website URL
- `band_facebook`, `band_instagram`, `band_twitter` - Social media links
- `band_total_shows` - Total recorded shows (from local database)
- `band_most_played_track` - Most frequently played track (from local database)

**Data Sources:**
1. Wikipedia REST API (bio, thumbnail)
2. Wikipedia Infobox parsing (origin, genres, years_active, website)
3. Brave Search API (social media links - requires API key)
4. Archive.org Stats (total shows, most played track - from imported products)

**Usage:**
```bash
# Enrich single artist with all fields
bin/magento archive:artist:enrich "Phish" --force

# Enrich with specific fields
bin/magento archive:artist:enrich "Phish" --fields=bio,origin,stats

# Enrich all 35 configured artists
bin/magento archive:artist:enrich --all --fields=bio,origin,stats

# Archive.org stats only (requires imported products)
bin/magento archive:artist:enrich "Widespread Panic" --fields=stats --force
```

**Performance:**
- Wikipedia/Brave Search: ~1.5 seconds per artist
- Archive.org Stats: ~0.07 seconds (pure SQL, no API calls)

See `docs/ARTIST_ENRICHMENT_IMPLEMENTATION.md` for full documentation.

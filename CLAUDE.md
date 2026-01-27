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
- **Frontend runs on port 3001 (via npm, not Docker)** to avoid conflicts
- Non-standard host ports (3307, 6380, etc.) are used to avoid conflicts with other Docker projects

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
├── bin/           # Helper scripts
├── compose.yaml   # Docker Compose config
├── env/           # Environment files
│   ├── db.env
│   ├── magento.env
│   ├── opensearch.env
│   ├── phpfpm.env
│   └── rabbitmq.env
├── src/           # Magento source code (after setup)
└── CLAUDE.md      # This file
```

## Notes

- **2FA disabled** for development convenience
- **MariaDB 10.6** used (Mage-OS 1.0.5 doesn't support MariaDB 11.x)
- Source code is in `src/` directory after installation
- SSL certificate auto-generated and trusted locally

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
- **Only Jamify theme is available** (Spotify-style dark theme)
- Theme switcher has been removed
- All pages use Jamify layout with:
  - Desktop: Left sidebar navigation
  - Mobile: Bottom tab navigation
  - Dark theme (#121212 background)

### Key Files
- `frontend/context/ThemeContext.tsx` - Theme provider (hardcoded to Jamify)
- `frontend/components/ClientLayout.tsx` - Main layout wrapper
- `frontend/components/JamifyTopBar.tsx` - Desktop top bar
- `frontend/components/JamifyNavSidebar.tsx` - Desktop left sidebar
- `frontend/components/JamifyMobileNav.tsx` - Mobile bottom nav
- `frontend/package.json` - Dev server configured for port 3001

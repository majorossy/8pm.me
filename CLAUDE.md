# Magento 2 Headless Docker Development Environment

## Overview
Mage-OS 1.0.5 (Magento Open Source fork) as headless backend with Next.js/React frontend.

## Quick Start

```bash
bin/start   # Start Magento containers
bin/stop    # Stop Magento containers

# Frontend (separate)
docker compose up frontend   # Start frontend only
docker compose up -d         # Start all including frontend
```

## URLs

| URL | Purpose |
|-----|---------|
| http://localhost:3000 | **Next.js Frontend** |
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
| **Frontend** | 8pm-frontend-1 | 3000 | 3000 |
| Nginx | 8pm-app-1 | 80, 443 | 8000, 8443 |
| PHP-FPM | 8pm-phpfpm-1 | - | 9000 |
| MariaDB | 8pm-db-1 | 3307 | 3306 |
| Redis/Valkey | 8pm-redis-1 | 6380 | 6379 |
| OpenSearch | 8pm-opensearch-1 | 9201, 9301 | 9200, 9300 |
| RabbitMQ | 8pm-rabbitmq-1 | 15673, 5673 | 15672, 5672 |
| Mailcatcher | 8pm-mailcatcher-1 | 1080 | 1080 |
| phpMyAdmin | 8pm-phpmyadmin-1 | 8080 | 80 |

**Note:** Non-standard host ports (3307, 6380, etc.) are used to avoid conflicts with other Docker projects.

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

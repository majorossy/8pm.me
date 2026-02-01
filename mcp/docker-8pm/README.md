# Docker 8pm MCP Server

MCP server providing scoped Docker access to **only** `8pm-*` containers.

## Zero Setup Required

The `bundle.cjs` file is pre-built and committed to git. Just clone and go.

## Tools

| Tool | Description |
|------|-------------|
| `list_containers` | List all 8pm containers with status |
| `container_logs` | Get logs from a container |
| `container_exec` | Execute command in container (allowlisted commands only) |
| `container_restart` | Restart a container |
| `container_stats` | CPU/memory usage |

## Security

- **Container filter**: Only containers matching `^8pm-` prefix
- **Exec allowlist**: bin/magento, php, composer, mysql, redis-cli, ls, cat, grep, etc.
- **Blocked**: rm -rf, fork bombs, chmod 777, etc.
- **No destructive ops**: No remove, prune, or image operations

## Available Containers

- `8pm-app-1` - Nginx
- `8pm-phpfpm-1` - PHP-FPM
- `8pm-db-1` - MariaDB
- `8pm-redis-1` - Valkey/Redis
- `8pm-opensearch-1` - OpenSearch
- `8pm-rabbitmq-1` - RabbitMQ
- `8pm-mailcatcher-1` - Mailcatcher
- `8pm-phpmyadmin-1` - phpMyAdmin

## For Developers: Rebuilding the Bundle

Only needed if you modify `src/index.ts`:

```bash
cd mcp/docker-8pm
npm install                    # First time only
npx esbuild src/index.ts --bundle --platform=node --target=node18 --format=cjs --outfile=bundle.cjs
git add bundle.cjs
git commit -m "Rebuild docker-8pm MCP bundle"
```

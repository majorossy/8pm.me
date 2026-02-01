# Redis 8pm MCP Server

MCP server for inspecting and managing Redis/Valkey cache in the 8pm project.

## Zero Setup Required

The `bundle.cjs` file is pre-built and committed to git. Just clone and go.

## Tools

| Tool | Description |
|------|-------------|
| `keys` | Search keys by pattern (SCAN-based, safe) |
| `get` | Get string value |
| `hgetall` | Get all hash fields |
| `type` | Get key type |
| `ttl` | Get time-to-live |
| `info` | Server stats (memory, clients, etc.) |
| `dbsize` | Count total keys |
| `del` | Delete specific keys |
| `del_pattern` | Delete by pattern (dry_run by default) |
| `flushdb` | Clear database (requires confirm=true) |
| `magento_cache_stats` | Count keys by Magento cache type |

## Magento Cache Patterns

| Cache Type | Pattern |
|------------|---------|
| Config | `zc:k:*_CONFIG_*` |
| Layout | `zc:k:*_LAYOUT_*` |
| Block HTML | `zc:k:*_BLOCK_*` |
| Collections | `zc:k:*_COLLECTION_*` |
| Reflection | `zc:k:*_REFLECTION_*` |
| EAV | `zc:k:*_EAV_*` |
| Full Page | `zc:k:*_FPC_*` |
| Sessions | `sess_*` |

## Connection

Connects to `127.0.0.1:6380` (8pm's Redis host port mapping).

## For Developers: Rebuilding the Bundle

Only needed if you modify `src/index.ts`:

```bash
cd mcp/redis-8pm
npm install                    # First time only
npx esbuild src/index.ts --bundle --platform=node --target=node18 --format=cjs --outfile=bundle.cjs
git add bundle.cjs
git commit -m "Rebuild redis-8pm MCP bundle"
```

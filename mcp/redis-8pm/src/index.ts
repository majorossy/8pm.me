#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import Redis from "ioredis";

// Connect to Redis on mapped host port
const redis = new Redis.default({
  host: "127.0.0.1",
  port: 6380, // 8pm uses non-standard port to avoid conflicts
  lazyConnect: true,
});

// Magento cache key prefixes for reference
const MAGENTO_PREFIXES = {
  config: "zc:k:*_CONFIG_*",
  layout: "zc:k:*_LAYOUT_*",
  block: "zc:k:*_BLOCK_*",
  collection: "zc:k:*_COLLECTION_*",
  reflection: "zc:k:*_REFLECTION_*",
  eav: "zc:k:*_EAV_*",
  fullPage: "zc:k:*_FPC_*",
  session: "sess_*",
};

const server = new Server(
  {
    name: "redis-8pm",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// List available tools
server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: "keys",
        description:
          "Search for keys matching a pattern. Returns up to 100 keys by default. Use Magento prefixes like 'zc:k:*_CONFIG_*' for cache types.",
        inputSchema: {
          type: "object",
          properties: {
            pattern: {
              type: "string",
              description:
                "Pattern to match (e.g., '*', 'zc:k:*_CONFIG_*', 'sess_*')",
            },
            limit: {
              type: "number",
              description: "Maximum keys to return (default: 100, max: 1000)",
            },
          },
          required: ["pattern"],
        },
      },
      {
        name: "get",
        description: "Get the value of a string key.",
        inputSchema: {
          type: "object",
          properties: {
            key: {
              type: "string",
              description: "The key to get",
            },
          },
          required: ["key"],
        },
      },
      {
        name: "hgetall",
        description: "Get all fields and values of a hash key.",
        inputSchema: {
          type: "object",
          properties: {
            key: {
              type: "string",
              description: "The hash key to get",
            },
          },
          required: ["key"],
        },
      },
      {
        name: "type",
        description: "Get the type of a key (string, hash, list, set, zset).",
        inputSchema: {
          type: "object",
          properties: {
            key: {
              type: "string",
              description: "The key to check",
            },
          },
          required: ["key"],
        },
      },
      {
        name: "ttl",
        description:
          "Get the remaining time to live of a key in seconds. Returns -1 if no expiry, -2 if key doesn't exist.",
        inputSchema: {
          type: "object",
          properties: {
            key: {
              type: "string",
              description: "The key to check",
            },
          },
          required: ["key"],
        },
      },
      {
        name: "info",
        description:
          "Get Redis server information. Useful for memory usage, connected clients, and stats.",
        inputSchema: {
          type: "object",
          properties: {
            section: {
              type: "string",
              description:
                "Section to get (server, clients, memory, stats, replication, cpu, keyspace, all). Default: memory",
            },
          },
          required: [],
        },
      },
      {
        name: "dbsize",
        description: "Get the number of keys in the current database.",
        inputSchema: {
          type: "object",
          properties: {},
          required: [],
        },
      },
      {
        name: "del",
        description: "Delete one or more keys. Use with caution.",
        inputSchema: {
          type: "object",
          properties: {
            keys: {
              type: "array",
              items: { type: "string" },
              description: "Keys to delete",
            },
          },
          required: ["keys"],
        },
      },
      {
        name: "del_pattern",
        description:
          "Delete all keys matching a pattern. DANGEROUS - use with extreme caution. Limited to 1000 keys per call.",
        inputSchema: {
          type: "object",
          properties: {
            pattern: {
              type: "string",
              description:
                "Pattern to match (e.g., 'zc:k:*_CONFIG_*' for config cache)",
            },
            dry_run: {
              type: "boolean",
              description:
                "If true, only shows what would be deleted without actually deleting (default: true)",
            },
          },
          required: ["pattern"],
        },
      },
      {
        name: "flushdb",
        description:
          "Delete ALL keys in the current database. EXTREMELY DANGEROUS. Requires confirm=true.",
        inputSchema: {
          type: "object",
          properties: {
            confirm: {
              type: "boolean",
              description: "Must be true to actually flush the database",
            },
          },
          required: ["confirm"],
        },
      },
      {
        name: "magento_cache_stats",
        description:
          "Get statistics about Magento cache keys by type (config, layout, block, etc.).",
        inputSchema: {
          type: "object",
          properties: {},
          required: [],
        },
      },
    ],
  };
});

// Handle tool calls
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    await redis.connect().catch(() => {}); // Ignore if already connected

    switch (name) {
      case "keys": {
        const pattern = args?.pattern as string;
        const limit = Math.min((args?.limit as number) || 100, 1000);

        const keys: string[] = [];
        const stream = redis.scanStream({
          match: pattern,
          count: 100,
        });

        await new Promise<void>((resolve, reject) => {
          stream.on("data", (batch: string[]) => {
            for (const key of batch) {
              if (keys.length < limit) {
                keys.push(key);
              }
            }
            if (keys.length >= limit) {
              stream.destroy();
              resolve();
            }
          });
          stream.on("end", () => resolve());
          stream.on("error", reject);
        });

        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(
                {
                  pattern,
                  count: keys.length,
                  truncated: keys.length >= limit,
                  keys: keys.sort(),
                },
                null,
                2
              ),
            },
          ],
        };
      }

      case "get": {
        const key = args?.key as string;
        const value = await redis.get(key);

        if (value === null) {
          return {
            content: [{ type: "text", text: `Key '${key}' not found` }],
          };
        }

        // Try to parse as JSON for pretty printing
        let parsed = value;
        try {
          parsed = JSON.stringify(JSON.parse(value), null, 2);
        } catch {
          // Not JSON, use raw value
        }

        return {
          content: [{ type: "text", text: parsed }],
        };
      }

      case "hgetall": {
        const key = args?.key as string;
        const value = await redis.hgetall(key);

        if (Object.keys(value).length === 0) {
          return {
            content: [
              { type: "text", text: `Hash '${key}' not found or empty` },
            ],
          };
        }

        return {
          content: [{ type: "text", text: JSON.stringify(value, null, 2) }],
        };
      }

      case "type": {
        const key = args?.key as string;
        const type = await redis.type(key);

        return {
          content: [{ type: "text", text: `${key}: ${type}` }],
        };
      }

      case "ttl": {
        const key = args?.key as string;
        const ttl = await redis.ttl(key);

        let message: string;
        if (ttl === -2) {
          message = `Key '${key}' does not exist`;
        } else if (ttl === -1) {
          message = `Key '${key}' has no expiration`;
        } else {
          const hours = Math.floor(ttl / 3600);
          const minutes = Math.floor((ttl % 3600) / 60);
          const seconds = ttl % 60;
          message = `Key '${key}' expires in ${ttl}s (${hours}h ${minutes}m ${seconds}s)`;
        }

        return {
          content: [{ type: "text", text: message }],
        };
      }

      case "info": {
        const section = (args?.section as string) || "memory";
        const info = await redis.info(section);

        return {
          content: [{ type: "text", text: info }],
        };
      }

      case "dbsize": {
        const size = await redis.dbsize();

        return {
          content: [{ type: "text", text: `Database contains ${size} keys` }],
        };
      }

      case "del": {
        const keys = args?.keys as string[];

        if (!keys || keys.length === 0) {
          return {
            content: [{ type: "text", text: "No keys specified" }],
            isError: true,
          };
        }

        if (keys.length > 100) {
          return {
            content: [
              {
                type: "text",
                text: "Too many keys (max 100). Use del_pattern for bulk deletion.",
              },
            ],
            isError: true,
          };
        }

        const deleted = await redis.del(...keys);

        return {
          content: [
            {
              type: "text",
              text: `Deleted ${deleted} key(s)`,
            },
          ],
        };
      }

      case "del_pattern": {
        const pattern = args?.pattern as string;
        const dryRun = args?.dry_run !== false; // Default to true for safety

        const keys: string[] = [];
        const stream = redis.scanStream({
          match: pattern,
          count: 100,
        });

        await new Promise<void>((resolve, reject) => {
          stream.on("data", (batch: string[]) => {
            for (const key of batch) {
              if (keys.length < 1000) {
                keys.push(key);
              }
            }
            if (keys.length >= 1000) {
              stream.destroy();
              resolve();
            }
          });
          stream.on("end", () => resolve());
          stream.on("error", reject);
        });

        if (dryRun) {
          return {
            content: [
              {
                type: "text",
                text: JSON.stringify(
                  {
                    dry_run: true,
                    pattern,
                    would_delete: keys.length,
                    truncated: keys.length >= 1000,
                    keys: keys.slice(0, 20),
                    note:
                      keys.length > 20
                        ? `... and ${keys.length - 20} more`
                        : undefined,
                  },
                  null,
                  2
                ),
              },
            ],
          };
        }

        if (keys.length === 0) {
          return {
            content: [{ type: "text", text: "No keys matched the pattern" }],
          };
        }

        const deleted = await redis.del(...keys);

        return {
          content: [
            {
              type: "text",
              text: `Deleted ${deleted} keys matching '${pattern}'`,
            },
          ],
        };
      }

      case "flushdb": {
        const confirm = args?.confirm as boolean;

        if (!confirm) {
          return {
            content: [
              {
                type: "text",
                text: "FLUSHDB aborted. Set confirm=true to actually flush the database.",
              },
            ],
            isError: true,
          };
        }

        await redis.flushdb();

        return {
          content: [
            {
              type: "text",
              text: "Database flushed. All keys deleted.",
            },
          ],
        };
      }

      case "magento_cache_stats": {
        const stats: Record<string, number> = {};

        for (const [type, pattern] of Object.entries(MAGENTO_PREFIXES)) {
          let count = 0;
          const stream = redis.scanStream({
            match: pattern,
            count: 100,
          });

          await new Promise<void>((resolve, reject) => {
            stream.on("data", (batch: string[]) => {
              count += batch.length;
            });
            stream.on("end", () => resolve());
            stream.on("error", reject);
          });

          stats[type] = count;
        }

        const total = Object.values(stats).reduce((a, b) => a + b, 0);
        const dbsize = await redis.dbsize();

        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(
                {
                  cache_types: stats,
                  total_magento_keys: total,
                  total_db_keys: dbsize,
                  other_keys: dbsize - total,
                },
                null,
                2
              ),
            },
          ],
        };
      }

      default:
        return {
          content: [{ type: "text", text: `Unknown tool: ${name}` }],
          isError: true,
        };
    }
  } catch (error) {
    return {
      content: [
        {
          type: "text",
          text: `Error: ${error instanceof Error ? error.message : String(error)}`,
        },
      ],
      isError: true,
    };
  }
});

// Cleanup on exit
process.on("SIGINT", async () => {
  await redis.quit();
  process.exit(0);
});

process.on("SIGTERM", async () => {
  await redis.quit();
  process.exit(0);
});

// Start the server
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("Redis 8pm MCP server started");
}

main().catch((error) => {
  console.error("Fatal error:", error);
  process.exit(1);
});

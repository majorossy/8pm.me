#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import { request } from "http";

const DOCKER_SOCKET = "/var/run/docker.sock";
const CONTAINER_PREFIX = "8pm-";

// Make HTTP request to Docker socket
function dockerRequest(
  method: string,
  path: string,
  body?: unknown
): Promise<unknown> {
  return new Promise((resolve, reject) => {
    const options = {
      socketPath: DOCKER_SOCKET,
      path,
      method,
      headers: body ? { "Content-Type": "application/json" } : undefined,
    };

    const req = request(options, (res) => {
      let data = "";
      res.on("data", (chunk) => (data += chunk));
      res.on("end", () => {
        try {
          resolve(data ? JSON.parse(data) : {});
        } catch {
          resolve(data);
        }
      });
    });

    req.on("error", reject);
    if (body) req.write(JSON.stringify(body));
    req.end();
  });
}

// Stream response from Docker (for logs, exec)
function dockerStream(
  method: string,
  path: string,
  body?: unknown
): Promise<string> {
  return new Promise((resolve, reject) => {
    const options = {
      socketPath: DOCKER_SOCKET,
      path,
      method,
      headers: body ? { "Content-Type": "application/json" } : undefined,
    };

    const req = request(options, (res) => {
      let data = Buffer.alloc(0);
      res.on("data", (chunk) => {
        data = Buffer.concat([data, chunk]);
      });
      res.on("end", () => {
        // Docker multiplexes stdout/stderr with 8-byte headers
        // We'll just strip non-printable characters for simplicity
        const text = data
          .toString("utf8")
          .replace(/[\x00-\x09\x0B\x0C\x0E-\x1F]/g, "");
        resolve(text);
      });
    });

    req.on("error", reject);
    if (body) req.write(JSON.stringify(body));
    req.end();
  });
}

interface Container {
  Id: string;
  Names: string[];
  State: string;
  Status: string;
  Ports: Array<{
    PublicPort?: number;
    PrivatePort: number;
    Type: string;
  }>;
}

// Validate container belongs to 8pm project
function isAllowedContainer(name: string): boolean {
  const normalized = name.startsWith("/") ? name.slice(1) : name;
  return normalized.startsWith(CONTAINER_PREFIX);
}

// Get container ID by name
async function getContainerId(name: string): Promise<string> {
  const containers = (await dockerRequest(
    "GET",
    "/containers/json?all=true"
  )) as Container[];
  const found = containers.find((c) =>
    c.Names.some((n) => {
      const normalized = n.startsWith("/") ? n.slice(1) : n;
      return normalized === name && isAllowedContainer(n);
    })
  );

  if (!found) {
    throw new Error(`Container '${name}' not found or not an 8pm container`);
  }

  return found.Id;
}

// Allowlist of safe commands for exec
const ALLOWED_EXEC_PATTERNS = [
  /^bin\/magento\s/,
  /^php\s/,
  /^composer\s/,
  /^ls\s/,
  /^cat\s/,
  /^head\s/,
  /^tail\s/,
  /^grep\s/,
  /^find\s/,
  /^wc\s/,
  /^du\s/,
  /^df\s/,
  /^pwd$/,
  /^whoami$/,
  /^id$/,
  /^env$/,
  /^printenv/,
  /^mysql\s/,
  /^mysqldump\s/,
  /^redis-cli\s/,
  /^curl\s/,
  /^wget\s/,
];

// Dangerous patterns to block
const BLOCKED_PATTERNS = [
  /rm\s+-rf/,
  /rm\s+--recursive/,
  />\s*\/dev\/sd/,
  /mkfs/,
  /dd\s+if=/,
  /:\(\)\s*{\s*:\|:\s*&\s*}/, // Fork bomb
  /chmod\s+777/,
  /chown\s+-R\s+root/,
];

function isCommandAllowed(cmd: string): boolean {
  for (const pattern of BLOCKED_PATTERNS) {
    if (pattern.test(cmd)) return false;
  }
  for (const pattern of ALLOWED_EXEC_PATTERNS) {
    if (pattern.test(cmd)) return true;
  }
  return false;
}

const server = new Server(
  { name: "docker-8pm", version: "1.0.0" },
  { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: "list_containers",
        description:
          "List all 8pm Docker containers with their status. Returns container name, status, and ports.",
        inputSchema: { type: "object", properties: {}, required: [] },
      },
      {
        name: "container_logs",
        description:
          "Get logs from an 8pm container. Useful for debugging services.",
        inputSchema: {
          type: "object",
          properties: {
            container: {
              type: "string",
              description: "Container name (e.g., 8pm-phpfpm-1, 8pm-db-1)",
            },
            tail: {
              type: "number",
              description: "Number of lines to return (default: 100)",
            },
          },
          required: ["container"],
        },
      },
      {
        name: "container_exec",
        description:
          "Execute a command inside an 8pm container. Only safe commands are allowed.",
        inputSchema: {
          type: "object",
          properties: {
            container: {
              type: "string",
              description: "Container name (e.g., 8pm-phpfpm-1)",
            },
            command: {
              type: "string",
              description: "Command to execute (e.g., 'bin/magento cache:flush')",
            },
            user: {
              type: "string",
              description: "User to run as (default: app for phpfpm)",
            },
          },
          required: ["container", "command"],
        },
      },
      {
        name: "container_restart",
        description: "Restart an 8pm container.",
        inputSchema: {
          type: "object",
          properties: {
            container: {
              type: "string",
              description: "Container name (e.g., 8pm-phpfpm-1)",
            },
          },
          required: ["container"],
        },
      },
      {
        name: "container_stats",
        description: "Get CPU and memory usage for 8pm containers.",
        inputSchema: {
          type: "object",
          properties: {
            container: {
              type: "string",
              description: "Container name (optional - omit for all)",
            },
          },
          required: [],
        },
      },
    ],
  };
});

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case "list_containers": {
        const containers = (await dockerRequest(
          "GET",
          "/containers/json?all=true"
        )) as Container[];
        const filtered = containers.filter((c) =>
          c.Names.some((n) => isAllowedContainer(n))
        );

        const result = filtered.map((c) => ({
          name: c.Names[0]?.replace(/^\//, "") || "unknown",
          status: c.Status,
          state: c.State,
          ports: c.Ports.map(
            (p) => `${p.PublicPort || ""}:${p.PrivatePort}/${p.Type}`
          )
            .filter((p) => !p.startsWith(":"))
            .join(", "),
        }));

        return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
      }

      case "container_logs": {
        const containerId = await getContainerId(args?.container as string);
        const tail = (args?.tail as number) || 100;

        const logs = await dockerStream(
          "GET",
          `/containers/${containerId}/logs?stdout=true&stderr=true&tail=${tail}`
        );

        return { content: [{ type: "text", text: logs || "(no logs)" }] };
      }

      case "container_exec": {
        const containerName = args?.container as string;
        const command = args?.command as string;
        let user = args?.user as string | undefined;

        if (!isCommandAllowed(command)) {
          return {
            content: [{ type: "text", text: "Error: Command not allowed." }],
            isError: true,
          };
        }

        if (!user && containerName.includes("phpfpm")) {
          user = "app";
        }

        const containerId = await getContainerId(containerName);

        // Create exec instance
        const execCreate = (await dockerRequest(
          "POST",
          `/containers/${containerId}/exec`,
          {
            Cmd: ["sh", "-c", command],
            AttachStdout: true,
            AttachStderr: true,
            User: user || "",
            WorkingDir: "/var/www/html",
          }
        )) as { Id: string };

        // Start exec and get output
        const output = await dockerStream(
          "POST",
          `/exec/${execCreate.Id}/start`,
          { Detach: false }
        );

        return { content: [{ type: "text", text: output || "(no output)" }] };
      }

      case "container_restart": {
        const containerId = await getContainerId(args?.container as string);
        await dockerRequest("POST", `/containers/${containerId}/restart`);

        return {
          content: [{ type: "text", text: `Container ${args?.container} restarted` }],
        };
      }

      case "container_stats": {
        const containerName = args?.container as string | undefined;
        const containers = (await dockerRequest(
          "GET",
          "/containers/json"
        )) as Container[];
        const filtered = containers.filter((c) =>
          c.Names.some((n) => isAllowedContainer(n))
        );

        if (containerName) {
          const found = filtered.find((c) =>
            c.Names.some((n) => n.replace(/^\//, "") === containerName)
          );
          if (!found) {
            throw new Error(`Container '${containerName}' not found`);
          }

          const stats = (await dockerRequest(
            "GET",
            `/containers/${found.Id}/stats?stream=false`
          )) as {
            cpu_stats: { cpu_usage: { total_usage: number }; system_cpu_usage: number; online_cpus?: number };
            precpu_stats: { cpu_usage: { total_usage: number }; system_cpu_usage: number };
            memory_stats: { usage: number; limit: number };
          };

          const cpuDelta = stats.cpu_stats.cpu_usage.total_usage - stats.precpu_stats.cpu_usage.total_usage;
          const sysDelta = stats.cpu_stats.system_cpu_usage - stats.precpu_stats.system_cpu_usage;
          const cpuPercent = sysDelta > 0 ? (cpuDelta / sysDelta) * (stats.cpu_stats.online_cpus || 1) * 100 : 0;
          const memUsage = stats.memory_stats.usage || 0;
          const memLimit = stats.memory_stats.limit || 1;

          return {
            content: [{
              type: "text",
              text: JSON.stringify({
                name: containerName,
                cpu: `${cpuPercent.toFixed(2)}%`,
                memory: `${(memUsage / 1024 / 1024).toFixed(1)}MB / ${(memLimit / 1024 / 1024 / 1024).toFixed(1)}GB`,
              }, null, 2),
            }],
          };
        }

        // All containers
        const allStats = await Promise.all(
          filtered.map(async (c) => {
            const stats = (await dockerRequest(
              "GET",
              `/containers/${c.Id}/stats?stream=false`
            )) as {
              cpu_stats: { cpu_usage: { total_usage: number }; system_cpu_usage: number; online_cpus?: number };
              precpu_stats: { cpu_usage: { total_usage: number }; system_cpu_usage: number };
              memory_stats: { usage: number; limit: number };
            };

            const cpuDelta = stats.cpu_stats.cpu_usage.total_usage - stats.precpu_stats.cpu_usage.total_usage;
            const sysDelta = stats.cpu_stats.system_cpu_usage - stats.precpu_stats.system_cpu_usage;
            const cpuPercent = sysDelta > 0 ? (cpuDelta / sysDelta) * (stats.cpu_stats.online_cpus || 1) * 100 : 0;
            const memUsage = stats.memory_stats.usage || 0;

            return {
              name: c.Names[0]?.replace(/^\//, "") || "unknown",
              cpu: `${cpuPercent.toFixed(2)}%`,
              memory: `${(memUsage / 1024 / 1024).toFixed(1)}MB`,
            };
          })
        );

        return { content: [{ type: "text", text: JSON.stringify(allStats, null, 2) }] };
      }

      default:
        return { content: [{ type: "text", text: `Unknown tool: ${name}` }], isError: true };
    }
  } catch (error) {
    return {
      content: [{ type: "text", text: `Error: ${error instanceof Error ? error.message : String(error)}` }],
      isError: true,
    };
  }
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("Docker 8pm MCP server started");
}

main().catch((error) => {
  console.error("Fatal error:", error);
  process.exit(1);
});

# Archive.org Import System - REST API Documentation

**Version:** 2.0
**Last Updated:** 2026-01-28
**Base URL:** `https://yourstore.com/rest`

---

## Table of Contents

1. [Authentication](#authentication)
2. [API Endpoints](#api-endpoints)
3. [Data Models](#data-models)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Examples](#examples)

---

## Authentication

All API endpoints require **admin-level authentication** using bearer tokens.

### Getting an Admin Token

**Endpoint:** `POST /V1/integration/admin/token`

**Request:**
```bash
curl -X POST "https://yourstore.com/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'
```

**Response:**
```json
"abc123def456xyz789..."
```

### Using the Token

Include the token in the `Authorization` header for all subsequent requests:

```bash
curl -X GET "https://yourstore.com/rest/V1/archive/collections" \
  -H "Authorization: Bearer abc123def456xyz789..."
```

**Token Lifetime:** 4 hours (default Magento setting)

---

## API Endpoints

### Import Management

#### Start Import Job

Triggers an import operation to download shows from Archive.org and create Magento products.

**Endpoint:** `POST /V1/archive/import`

**Required ACL Resource:** `ArchiveDotOrg_Core::import_start`

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `artist_name` | string | Yes | Full artist name (e.g., "Grateful Dead") |
| `collection_id` | string | Yes | Archive.org collection ID (e.g., "GratefulDead") |
| `limit` | integer | No | Maximum number of shows to import (default: all) |
| `offset` | integer | No | Starting position (default: 0) |
| `dry_run` | boolean | No | Preview mode - no products created (default: false) |

**Example Request:**
```bash
curl -X POST "https://yourstore.com/rest/V1/archive/import" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "artist_name": "Grateful Dead",
    "collection_id": "GratefulDead",
    "limit": 10,
    "dry_run": false
  }'
```

**Success Response (200 OK):**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "completed",
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "total_shows": 10,
  "processed_shows": 10,
  "tracks_created": 150,
  "tracks_updated": 0,
  "error_count": 0,
  "progress": 100.0,
  "started_at": "2026-01-28T14:30:00Z",
  "completed_at": "2026-01-28T14:45:00Z"
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Invalid collection_id format. Must contain only alphanumeric characters, underscores, and hyphens."
}
```

---

#### Get Import Job Status

Retrieves the current status of an import job.

**Endpoint:** `GET /V1/archive/import/:jobId`

**Required ACL Resource:** `ArchiveDotOrg_Core::import_status`

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `jobId` | string | Unique job identifier from import start response |

**Example Request:**
```bash
curl -X GET "https://yourstore.com/rest/V1/archive/import/import_20260128_abc123" \
  -H "Authorization: Bearer TOKEN"
```

**Success Response (200 OK):**

**Status: Running**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "running",
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "total_shows": 100,
  "processed_shows": 45,
  "tracks_created": 675,
  "tracks_updated": 0,
  "error_count": 0,
  "progress": 45.0,
  "started_at": "2026-01-28T14:30:00Z",
  "completed_at": null
}
```

**Status: Completed**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "completed",
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "total_shows": 100,
  "processed_shows": 100,
  "tracks_created": 1500,
  "tracks_updated": 0,
  "error_count": 0,
  "progress": 100.0,
  "started_at": "2026-01-28T14:30:00Z",
  "completed_at": "2026-01-28T15:30:00Z"
}
```

**Status: Failed**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "failed",
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "total_shows": 100,
  "processed_shows": 45,
  "tracks_created": 675,
  "tracks_updated": 0,
  "error_count": 1,
  "progress": 45.0,
  "started_at": "2026-01-28T14:30:00Z",
  "completed_at": "2026-01-28T15:00:00Z",
  "error_message": "Archive.org API rate limit exceeded"
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Import job not found: import_20260128_xyz999"
}
```

---

#### Cancel Import Job

Stops a currently running import job.

**Endpoint:** `DELETE /V1/archive/import/:jobId`

**Required ACL Resource:** `ArchiveDotOrg_Core::import_cancel`

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `jobId` | string | Unique job identifier |

**Example Request:**
```bash
curl -X DELETE "https://yourstore.com/rest/V1/archive/import/import_20260128_abc123" \
  -H "Authorization: Bearer TOKEN"
```

**Success Response (200 OK):**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "cancelled",
  "message": "Import job cancelled successfully"
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Cannot cancel import job: already completed"
}
```

---

### Collection Management

#### List Collections

Returns a list of all configured artist collections.

**Endpoint:** `GET /V1/archive/collections`

**Required ACL Resource:** `ArchiveDotOrg_Core::collections_view`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `include_stats` | boolean | No | Include import statistics (slower, queries Archive.org) |

**Example Request:**
```bash
# Without statistics (fast)
curl -X GET "https://yourstore.com/rest/V1/archive/collections" \
  -H "Authorization: Bearer TOKEN"

# With statistics (slower, includes show counts from Archive.org)
curl -X GET "https://yourstore.com/rest/V1/archive/collections?include_stats=true" \
  -H "Authorization: Bearer TOKEN"
```

**Success Response (200 OK) - Without Stats:**
```json
[
  {
    "artist_name": "Grateful Dead",
    "collection_id": "GratefulDead",
    "category_id": 42,
    "enabled": true
  },
  {
    "artist_name": "Phish",
    "collection_id": "etree",
    "category_id": 43,
    "enabled": true
  },
  {
    "artist_name": "STS9",
    "collection_id": "STS9",
    "category_id": 44,
    "enabled": true
  }
]
```

**Success Response (200 OK) - With Stats:**
```json
[
  {
    "artist_name": "Grateful Dead",
    "collection_id": "GratefulDead",
    "category_id": 42,
    "enabled": true,
    "total_shows": 2130,
    "imported_shows": 1500,
    "imported_tracks": 22500,
    "last_import": "2026-01-27T14:30:00Z"
  },
  {
    "artist_name": "Phish",
    "collection_id": "etree",
    "category_id": 43,
    "enabled": true,
    "total_shows": 1800,
    "imported_shows": 1200,
    "imported_tracks": 18000,
    "last_import": "2026-01-26T10:15:00Z"
  }
]
```

---

#### Get Collection Details

Returns detailed information about a specific collection.

**Endpoint:** `GET /V1/archive/collections/:collectionId`

**Required ACL Resource:** `ArchiveDotOrg_Core::collections_view`

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `collectionId` | string | Archive.org collection ID (e.g., "GratefulDead") |

**Example Request:**
```bash
curl -X GET "https://yourstore.com/rest/V1/archive/collections/GratefulDead" \
  -H "Authorization: Bearer TOKEN"
```

**Success Response (200 OK):**
```json
{
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "category_id": 42,
  "category_url_key": "grateful-dead",
  "enabled": true,
  "total_shows": 2130,
  "imported_shows": 1500,
  "imported_tracks": 22500,
  "last_import": "2026-01-27T14:30:00Z",
  "configuration": {
    "audio_format": "flac",
    "import_images": true,
    "attribute_set_id": 4
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Collection not found: UnknownArtist"
}
```

---

### Product Management

#### Delete Imported Product

Deletes a single product that was imported from Archive.org.

**Endpoint:** `DELETE /V1/archive/products/:sku`

**Required ACL Resource:** `ArchiveDotOrg_Core::products_delete`

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `sku` | string | Product SKU (e.g., "archive-gd1977-05-08-abc123") |

**Example Request:**
```bash
curl -X DELETE "https://yourstore.com/rest/V1/archive/products/archive-gd1977-05-08-abc123" \
  -H "Authorization: Bearer TOKEN"
```

**Success Response (200 OK):**
```json
{
  "sku": "archive-gd1977-05-08-abc123",
  "message": "Product deleted successfully"
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Product not found with SKU: archive-gd1977-05-08-abc123"
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Cannot delete product: not an Archive.org imported product"
}
```

---

## Data Models

### ImportJobInterface

Represents an import job with its current state.

```typescript
interface ImportJob {
  job_id: string;           // Unique identifier (e.g., "import_20260128_abc123")
  status: string;           // "queued" | "running" | "completed" | "failed" | "cancelled"
  artist_name: string;      // Full artist name
  collection_id: string;    // Archive.org collection ID
  total_shows: number;      // Total shows to import
  processed_shows: number;  // Shows processed so far
  tracks_created: number;   // New products created
  tracks_updated: number;   // Existing products updated
  error_count: number;      // Number of errors encountered
  progress: number;         // Percentage complete (0.0 - 100.0)
  started_at: string;       // ISO 8601 timestamp
  completed_at: string | null;  // ISO 8601 timestamp or null if running
  error_message?: string;   // Error details if status = "failed"
}
```

### CollectionInfoInterface

Represents an artist collection configuration.

```typescript
interface CollectionInfo {
  artist_name: string;      // Full artist name
  collection_id: string;    // Archive.org collection ID
  category_id: number;      // Magento category ID
  category_url_key?: string; // Category URL key
  enabled: boolean;         // Whether imports are enabled

  // Only included with include_stats=true
  total_shows?: number;     // Total shows available on Archive.org
  imported_shows?: number;  // Shows imported to Magento
  imported_tracks?: number; // Tracks imported to Magento
  last_import?: string;     // ISO 8601 timestamp of last import

  // Configuration details
  configuration?: {
    audio_format: string;   // "flac" | "mp3" | "ogg" | "shn"
    import_images: boolean; // Whether to import spectrogram images
    attribute_set_id: number; // Product attribute set
  };
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 400 | Bad Request | Invalid parameters or request body |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | User lacks required ACL permissions |
| 404 | Not Found | Resource not found (job, collection, product) |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |

### Error Response Format

All errors return a JSON object with a `message` field:

```json
{
  "message": "Error description here"
}
```

**Extended error format (validation errors):**
```json
{
  "message": "Validation failed",
  "errors": [
    {
      "field": "collection_id",
      "message": "Collection ID must contain only alphanumeric characters, underscores, and hyphens"
    },
    {
      "field": "limit",
      "message": "Limit must be a positive integer"
    }
  ]
}
```

### Common Error Messages

| Message | Cause | Solution |
|---------|-------|----------|
| "Invalid collection_id format..." | Collection ID contains invalid characters | Use only letters, numbers, underscores, hyphens |
| "Lock already held..." | Concurrent import running | Wait for other import to finish or cancel it |
| "Archive.org API rate limit exceeded" | Too many API requests | Wait and retry - automatic backoff built-in |
| "Import job not found..." | Invalid job_id | Check job_id from start import response |
| "Cannot cancel import job: already completed" | Job finished before cancel | No action needed |
| "Product not found with SKU..." | Invalid SKU or product deleted | Verify SKU exists in catalog |
| "Cannot delete product: not an Archive.org imported product" | SKU doesn't match Archive.org pattern | Only Archive.org products can be deleted via this endpoint |

---

## Rate Limiting

### Magento API Rate Limits

- **Default:** 20 requests per minute per IP address
- **Configurable:** `Stores > Configuration > Services > Magento Web API > Rate Limiting`

### Archive.org API Rate Limits

- **Archive.org limit:** ~1 request/second
- **Impact:** Large imports take time (e.g., 3.5 hours for 10,000 shows)
- **Handling:** Built-in retry logic with exponential backoff

**Best Practices:**
- Use `limit` parameter for testing (e.g., `limit: 10`)
- Monitor job status via `GET /V1/archive/import/:jobId`
- For large imports, use async processing (if queue consumers enabled)

---

## Examples

### Full Import Workflow

**1. List available collections:**
```bash
curl -X GET "https://yourstore.com/rest/V1/archive/collections" \
  -H "Authorization: Bearer TOKEN"
```

**2. Start import for specific artist:**
```bash
curl -X POST "https://yourstore.com/rest/V1/archive/import" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "artist_name": "Grateful Dead",
    "collection_id": "GratefulDead",
    "limit": 50
  }'
```

**Response:**
```json
{
  "job_id": "import_20260128_abc123",
  "status": "running",
  ...
}
```

**3. Poll job status every 30 seconds:**
```bash
# Save job_id from step 2
JOB_ID="import_20260128_abc123"

# Poll until completed
while true; do
  STATUS=$(curl -s -X GET \
    "https://yourstore.com/rest/V1/archive/import/$JOB_ID" \
    -H "Authorization: Bearer TOKEN" | jq -r '.status')

  echo "Status: $STATUS"

  if [[ "$STATUS" == "completed" || "$STATUS" == "failed" ]]; then
    break
  fi

  sleep 30
done
```

**4. Get final job details:**
```bash
curl -X GET "https://yourstore.com/rest/V1/archive/import/$JOB_ID" \
  -H "Authorization: Bearer TOKEN"
```

### Dry Run Import (Preview)

```bash
curl -X POST "https://yourstore.com/rest/V1/archive/import" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "artist_name": "STS9",
    "collection_id": "STS9",
    "limit": 5,
    "dry_run": true
  }'
```

**Response:**
```json
{
  "job_id": "import_20260128_preview",
  "status": "completed",
  "artist_name": "STS9",
  "collection_id": "STS9",
  "total_shows": 5,
  "processed_shows": 5,
  "tracks_created": 0,        // Dry run - nothing created
  "tracks_updated": 0,
  "error_count": 0,
  "progress": 100.0,
  "preview": {
    "would_create": 75,       // Would create 75 products
    "would_update": 0,
    "estimated_time": "2 minutes"
  }
}
```

### Cancel Long-Running Import

```bash
curl -X DELETE "https://yourstore.com/rest/V1/archive/import/import_20260128_abc123" \
  -H "Authorization: Bearer TOKEN"
```

### Delete Imported Products

```bash
# Delete single product
curl -X DELETE "https://yourstore.com/rest/V1/archive/products/archive-gd1977-05-08-track01" \
  -H "Authorization: Bearer TOKEN"

# Script to delete multiple products
SKUS=("archive-gd1977-05-08-track01" "archive-gd1977-05-08-track02" "archive-gd1977-05-08-track03")

for SKU in "${SKUS[@]}"; do
  echo "Deleting $SKU..."
  curl -X DELETE "https://yourstore.com/rest/V1/archive/products/$SKU" \
    -H "Authorization: Bearer TOKEN"
  sleep 1  # Rate limiting
done
```

---

## Integration Examples

### JavaScript/Node.js

```javascript
const axios = require('axios');

const API_BASE = 'https://yourstore.com/rest';
const TOKEN = 'your_admin_token_here';

async function startImport(artistName, collectionId, limit) {
  const response = await axios.post(
    `${API_BASE}/V1/archive/import`,
    {
      artist_name: artistName,
      collection_id: collectionId,
      limit: limit
    },
    {
      headers: {
        'Authorization': `Bearer ${TOKEN}`,
        'Content-Type': 'application/json'
      }
    }
  );
  return response.data.job_id;
}

async function getJobStatus(jobId) {
  const response = await axios.get(
    `${API_BASE}/V1/archive/import/${jobId}`,
    {
      headers: { 'Authorization': `Bearer ${TOKEN}` }
    }
  );
  return response.data;
}

// Usage
(async () => {
  const jobId = await startImport('Grateful Dead', 'GratefulDead', 10);
  console.log(`Import started: ${jobId}`);

  let status = 'running';
  while (status === 'running') {
    await new Promise(resolve => setTimeout(resolve, 30000)); // Wait 30s
    const job = await getJobStatus(jobId);
    status = job.status;
    console.log(`Progress: ${job.progress}% (${job.processed_shows}/${job.total_shows})`);
  }

  console.log(`Import ${status}!`);
})();
```

### Python

```python
import requests
import time

API_BASE = 'https://yourstore.com/rest'
TOKEN = 'your_admin_token_here'

def start_import(artist_name, collection_id, limit=None):
    response = requests.post(
        f'{API_BASE}/V1/archive/import',
        json={
            'artist_name': artist_name,
            'collection_id': collection_id,
            'limit': limit
        },
        headers={
            'Authorization': f'Bearer {TOKEN}',
            'Content-Type': 'application/json'
        }
    )
    response.raise_for_status()
    return response.json()['job_id']

def get_job_status(job_id):
    response = requests.get(
        f'{API_BASE}/V1/archive/import/{job_id}',
        headers={'Authorization': f'Bearer {TOKEN}'}
    )
    response.raise_for_status()
    return response.json()

# Usage
job_id = start_import('Phish', 'etree', limit=10)
print(f'Import started: {job_id}')

status = 'running'
while status == 'running':
    time.sleep(30)
    job = get_job_status(job_id)
    status = job['status']
    print(f"Progress: {job['progress']}% ({job['processed_shows']}/{job['total_shows']})")

print(f"Import {status}!")
```

---

## Further Reading

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Technical implementation details
- **[Admin Guide](ADMIN_GUIDE.md)** - Dashboard usage guide
- **[Phase Documentation](import-rearchitecture/)** - Implementation phases

---

**Questions?** Contact your system administrator or check logs at `var/log/archivedotorg.log`

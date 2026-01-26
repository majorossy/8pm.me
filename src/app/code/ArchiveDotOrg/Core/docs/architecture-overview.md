# ArchiveDotOrg Import System - Architecture Overview

## Introduction

The ArchiveDotOrg module imports live concert recordings from Archive.org's public collections (Grateful Dead, Phish, STS9, etc.) into Magento as virtual products. Each audio track becomes a product with streaming capabilities.

---

## High-Level Architecture

```
+-----------------------------------------------------------------------------+
|                            ENTRY POINTS                                      |
+------------------+------------------+------------------+---------------------+
| CLI Command      | REST API         | Cron Job         | Message Queue      |
| ImportShows      | ImportManagement | crontab.xml      | ImportPublisher    |
| Command.php      | .php             |                  | /ImportConsumer    |
+--------+---------+--------+---------+--------+---------+---------+----------+
         |                  |                  |                   |
         +------------------+------------------+-------------------+
                                    |
                                    v
+-----------------------------------------------------------------------------+
|                           ORCHESTRATION LAYER                                |
|                                                                              |
|  ShowImporter                                                                |
|  +-- importByCollection() --- Main entry: fetches identifiers, batches      |
|  +-- importShow()         --- Single show import                            |
|  +-- dryRun()             --- Preview without changes                       |
+-----------------------------------------------------------------------------+
                                    |
                    +---------------+---------------+
                    v               v               v
+-------------------------+ +-----------------+ +--------------------------+
|    ArchiveApiClient     | |  TrackImporter  | | CategoryAssignment       |
|                         | |     - OR -      | | Service                  |
| - fetchCollectionIds()  | | BulkProduct     | |                          |
| - fetchShowMetadata()   | | Importer        | | - getOrCreateArtist      |
| - testConnection()      | |                 | |   Category()             |
| - getCollectionCount()  | | Creates Magento | | - getOrCreateShow        |
|                         | | Products        | |   Category()             |
| HTTP client with        | |                 | | - bulkAssignToCategory() |
| retry logic             | |                 | |                          |
+-------------------------+ +--------+--------+ +--------------------------+
                                     |
                                     v
                    +--------------------------------+
                    |    AttributeOptionManager      |
                    |                                |
                    | - getOrCreateOptionId()        |
                    | - bulkGetOrCreateOptionIds()   |
                    |                                |
                    | Manages EAV dropdown values    |
                    | (show_year, show_venue,        |
                    |  show_taper, archive_collection)|
                    +--------------------------------+
```

---

## Data Flow (Step by Step)

### Step 1: Fetch Collection Identifiers

```
Archive.org API: /advancedsearch.php?q=Collection:GratefulDead&fl[]=identifier
                           |
                           v
Returns: ["gd1977-05-08.sbd.miller.32601", "gd1978-04-22.sbd...", ...]
```

### Step 2: For Each Identifier, Fetch Show Metadata

```
Archive.org API: /metadata/gd1977-05-08.sbd.miller.32601
                           |
                           v
Returns JSON with:
  - metadata: {title, date, year, venue, taper, lineage, notes...}
  - d1, d2: streaming server hostnames
  - dir: path on server
  - files: [{name, title, track, length, sha1, format...}, ...]
```

### Step 3: Parse into DTOs

```php
ShowInterface {
    identifier: "gd1977-05-08.sbd.miller.32601"
    title: "Grateful Dead Live at Barton Hall..."
    date: "1977-05-08"
    year: "1977"
    venue: "Barton Hall, Cornell University"
    taper: "Betty Cantor-Jackson"
    serverOne: "ia800102.us.archive.org"
    dir: "/8/items/gd1977-05-08.sbd.miller.32601"
    tracks: [TrackInterface, TrackInterface, ...]
}

TrackInterface {
    name: "gd77-05-08d1t01.flac"
    title: "New Minglewood Blues"
    trackNumber: 1
    length: "6:23"
    sha1: "abc123def456..."  <-- Used as SKU (unique identifier)
    format: "flac"
}
```

### Step 4: Create Magento Products

Two import paths are available:

| TrackImporter (Standard) | BulkProductImporter (Fast) |
|--------------------------|---------------------------|
| Uses ProductRepository | Uses direct SQL |
| 1 product at a time | Batched inserts |
| Triggers observers/events | Bypasses Magento layer |
| Safer, slower | 10x+ faster, riskier |

### Step 5: Assign to Categories

```
Products --> Artist Category (e.g., "Grateful Dead")
        --> Show Category (e.g., "1977-05-08 Barton Hall")
```

---

## Module Structure

```
ArchiveDotOrg/Core/
|-- Api/                          # Service Contracts (interfaces)
|   |-- Data/                     # DTO interfaces
|   |   |-- ShowInterface.php
|   |   |-- TrackInterface.php
|   |   |-- ImportResultInterface.php
|   |   |-- ImportJobInterface.php
|   |   +-- CollectionInfoInterface.php
|   |-- ArchiveApiClientInterface.php
|   |-- ShowImporterInterface.php
|   |-- TrackImporterInterface.php
|   |-- BulkProductImporterInterface.php
|   |-- AttributeOptionManagerInterface.php
|   |-- CategoryAssignmentServiceInterface.php
|   +-- ImportManagementInterface.php
|
|-- Model/                        # Implementations
|   |-- Data/                     # DTO implementations
|   |-- Queue/                    # Async processing
|   |   |-- ImportPublisher.php
|   |   |-- ImportConsumer.php
|   |   |-- StatusConsumer.php
|   |   +-- JobStatusManager.php
|   |-- ArchiveApiClient.php
|   |-- ShowImporter.php
|   |-- TrackImporter.php
|   |-- BulkProductImporter.php
|   |-- AttributeOptionManager.php
|   |-- CategoryAssignmentService.php
|   |-- Config.php
|   +-- ImportManagement.php
|
|-- Console/Command/              # CLI commands
|   |-- ImportShowsCommand.php
|   |-- StatusCommand.php
|   +-- CleanupProductsCommand.php
|
|-- Controller/Adminhtml/         # Admin controllers
|-- Cron/                         # Scheduled jobs
|-- Setup/Patch/Data/             # EAV attribute creation
|-- Block/                        # Frontend/admin blocks
|-- Logger/                       # Custom logging
|-- Exception/                    # Custom exceptions
|-- Test/Unit/                    # PHPUnit tests
|-- etc/                          # Configuration XML
+-- view/                         # Templates, JS, layouts
```

---

## Configuration Settings

All settings come from Magento admin config (Stores > Configuration > Archive.org Import):

| Setting | Config Path | Default |
|---------|-------------|---------|
| Enabled | archivedotorg/general/enabled | - |
| Debug mode | archivedotorg/general/debug | false |
| Base URL | archivedotorg/api/base_url | https://archive.org |
| Timeout | archivedotorg/api/timeout | 30s |
| Retry attempts | archivedotorg/api/retry_attempts | 3 |
| Retry delay | archivedotorg/api/retry_delay | 1000ms |
| Batch size | archivedotorg/import/batch_size | 100 |
| Audio format | archivedotorg/import/audio_format | flac |
| Artist mappings | archivedotorg/mappings/artist_mappings | JSON |

---

## Product Attributes

Custom EAV attributes created for imported products:

| Attribute Code | Type | Description |
|----------------|------|-------------|
| `title` | varchar | Track title |
| `length` | varchar | Track duration |
| `identifier` | varchar | Archive.org show identifier |
| `show_name` | varchar | Show/album name |
| `show_date` | varchar | Performance date |
| `show_year` | select | Year (dropdown) |
| `show_venue` | select | Venue (dropdown) |
| `show_taper` | select | Taper/source (dropdown) |
| `archive_collection` | select | Artist/collection (dropdown) |
| `song_url` | varchar | Streaming audio URL |
| `server_one` | varchar | Primary Archive.org server |
| `server_two` | varchar | Backup server |
| `dir` | varchar | Directory path on server |
| `notes` | text | Show notes |
| `lineage` | text | Recording lineage |
| `guid` | varchar | Unique identifier |
| `pub_date` | varchar | Archive.org publish date |

---

## Memory Management

The system handles large imports via:

1. **Batch processing** - Configurable batch size (default 100)
2. **Cache clearing** between batches - `attributeOptionManager->clearCache()` and `categoryAssignmentService->clearCache()`
3. **Garbage collection** - `gc_collect_cycles()`
4. **Indexer management** - Sets indexers to "scheduled" mode during bulk imports

---

## CLI Commands

### Import Shows

```bash
# Dry run to preview
bin/magento archive:import:shows "Grateful Dead" --collection=GratefulDead --dry-run --limit=10

# Import 50 shows starting from offset 100
bin/magento archive:import:shows "STS9" --collection=STS9 --limit=50 --offset=100

# Full import
bin/magento archive:import:shows "Phish"
```

### Check Status

```bash
bin/magento archive:status
bin/magento archive:status --test-collection=GratefulDead
```

### Cleanup Products

```bash
# Dry run
bin/magento archive:cleanup:products --collection=OldArtist --dry-run

# Delete products older than 1 year
bin/magento archive:cleanup:products --older-than=365 --force
```

---

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/V1/archive/import` | Start import job |
| GET | `/V1/archive/import/:jobId` | Get job status |
| DELETE | `/V1/archive/import/:jobId` | Cancel job |
| GET | `/V1/archive/collections` | List collections |
| GET | `/V1/archive/collections/:id` | Get collection details |
| DELETE | `/V1/archive/products/:sku` | Delete product |

---

## Related Modules

### EightPM/SongGraphQl

GraphQL resolvers for exposing song attributes to the Next.js frontend:

- `SongUrl` - Returns streaming URL
- `SongTitle` - Returns track title
- `SongDuration` - Returns track length
- `ShowName` - Returns show name
- `Identifier` - Returns Archive.org identifier
- `ServerOne` - Returns primary server

---

## Logging

All operations log to `var/log/archivedotorg.log`:

```php
$this->logger->logImportStart($artist, $collection, $limit, $offset);
$this->logger->logShowProcessed($identifier, $title, $trackCount);
$this->logger->logTrackCreated($sku, $title);
$this->logger->logImportError($message, $context);
$this->logger->logImportComplete($resultArray);
```

---

## Test Coverage

Unit tests exist in `Test/Unit/`:

| Test File | Class Under Test |
|-----------|------------------|
| ArchiveApiClientTest.php | Retry logic, HTTP errors, JSON parsing |
| ShowImporterTest.php | Batch processing, category assignment |
| TrackImporterTest.php | Product creation/update, SKU validation |
| AttributeOptionManagerTest.php | Option creation, caching |
| BulkProductImporterTest.php | Direct SQL, indexer management |
| CategoryAssignmentServiceTest.php | Category creation, bulk assignment |
| ImportShowsCommandTest.php | Input validation, dry-run mode |

Run tests:
```bash
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml
```

# ArchiveDotOrg_Core Module

A Magento 2 module for importing and managing audio content from Archive.org's live music collections (Grateful Dead, Phish, STS9, etc.).

## What This Module Does

This module connects to Archive.org's API to import live concert recordings as Magento products. Each track becomes a virtual product with streaming audio capabilities. The module:

1. **Fetches metadata** from Archive.org collections (shows, tracks, venues, dates, tapers)
2. **Creates Magento products** for each audio track with custom EAV attributes
3. **Organizes content** into categories by artist and show/album
4. **Provides an audio player** on cart, category, and product pages
5. **Supports scheduled imports** via cron for keeping content updated

## Architecture Overview

```
ArchiveDotOrg/Core/
├── Api/                    # Service Contracts (interfaces)
│   ├── Data/               # DTO interfaces
│   └── *Interface.php      # Service interfaces
├── Model/                  # Implementations
│   ├── Data/               # DTO implementations
│   └── *.php               # Service implementations
├── Console/Command/        # CLI commands
├── Cron/                   # Scheduled jobs
├── Setup/Patch/Data/       # EAV attribute creation
├── Block/                  # Frontend/admin blocks
├── Logger/                 # Custom logging
├── Exception/              # Custom exceptions
├── etc/                    # Configuration XML
└── view/                   # Templates, JS, layouts
```

## Key Services

### ArchiveApiClient
HTTP client for Archive.org API with retry logic and timeout handling.

```php
// Fetches show metadata including all tracks
$show = $apiClient->fetchShowMetadata('gd1977-05-08.sbd.miller.32601.sbeok.flac16');

// Gets all show identifiers from a collection
$identifiers = $apiClient->fetchCollectionIdentifiers('GratefulDead', limit: 100);
```

### ShowImporter
Orchestrates the import process with batch processing and memory management.

```php
// Import shows from a collection
$result = $showImporter->importByCollection(
    artistName: 'Grateful Dead',
    collectionId: 'GratefulDead',
    limit: 50,
    progressCallback: fn($total, $current, $msg) => $output->writeln($msg)
);
```

### TrackImporter
Creates/updates Magento products from track data.

```php
// Import a single track
$productId = $trackImporter->importTrack($track, $show, 'Grateful Dead');

// Import all tracks from a show
$result = $trackImporter->importShowTracks($show, 'Grateful Dead');
// Returns: ['created' => 15, 'updated' => 0, 'skipped' => 2, 'product_ids' => [...]]
```

### BulkProductImporter
High-performance bulk import using direct SQL (bypasses Magento repository).

```php
// Manages indexers during bulk operations
$originalModes = $bulkImporter->prepareIndexers();  // Sets to scheduled mode
$result = $bulkImporter->importBulk($shows, 'STS9');
$bulkImporter->restoreIndexers($originalModes);
$bulkImporter->reindexAll();
```

### AttributeOptionManager
Centralized EAV option management (consolidated from duplicate legacy code).

```php
// Get or create dropdown option
$optionId = $attributeOptionManager->getOrCreateOptionId('show_venue', 'Red Rocks Amphitheatre');

// Bulk option creation for performance
$optionIds = $attributeOptionManager->bulkGetOrCreateOptionIds('show_year', ['1977', '1978', '1979']);
```

### CategoryAssignmentService
Assigns products to categories based on artist/collection/show.

```php
// Assign to artist category
$categoryAssignmentService->assignToArtistCategory($productId, 'Grateful Dead', 'GratefulDead');

// Bulk assign products
$categoryAssignmentService->bulkAssignToCategory($productIds, $categoryId);
```

### ImageImportService
Downloads and attaches spectrogram images to products.

```php
$imageImportService->importSpectrogram($product, $serverUrl, $dir, $filename);
```

### ProgressTracker
JSON-based progress tracking for resumable imports.

```php
$tracker->start('GratefulDead', 1500);
$tracker->update(100, 'gd1977-05-08...');
$progress = $tracker->get('GratefulDead');  // Returns current state
$tracker->complete('GratefulDead');
```

## CLI Commands

### archive:import:shows
Main import command for fetching shows from Archive.org.

```bash
# Dry run to see what would be imported
bin/magento archive:import:shows "Grateful Dead" --collection=GratefulDead --dry-run --limit=10

# Import 50 shows starting from offset 100
bin/magento archive:import:shows "STS9" --collection=STS9 --limit=50 --offset=100

# Full import (uses configured collection ID)
bin/magento archive:import:shows "Phish"
```

### archive:sync:albums
Synchronizes category structure with imported products.

```bash
bin/magento archive:sync:albums --threshold=75
```

### archive:status
Shows module status, configuration, and statistics.

```bash
bin/magento archive:status
bin/magento archive:status --test-collection=GratefulDead
```

### archive:cleanup:products
Delete Archive.org imported products by collection or age.

```bash
# Dry run - see what would be deleted
bin/magento archive:cleanup:products --collection=OldArtist --dry-run

# Delete products older than 1 year
bin/magento archive:cleanup:products --older-than=365 --dry-run

# Delete products from collection AND older than 90 days
bin/magento archive:cleanup:products --collection=TestArtist --older-than=90 --dry-run

# Actually delete (requires confirmation)
bin/magento archive:cleanup:products --collection=TestArtist

# Skip confirmation with --force
bin/magento archive:cleanup:products --collection=TestArtist --force

# Custom batch size (default: 100)
bin/magento archive:cleanup:products --collection=TestArtist --batch-size=50
```

## REST API

The module exposes REST API endpoints for integration with external systems.

### Authentication

All endpoints require admin-level authentication. Use Bearer token:

```bash
# Get admin token
curl -X POST "https://yourstore.com/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Use token in requests
curl -X GET "https://yourstore.com/rest/V1/archive/collections" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Endpoints

#### POST /V1/archive/import
Start a new import job.

```bash
curl -X POST "https://yourstore.com/rest/V1/archive/import" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "artist_name": "Grateful Dead",
    "collection_id": "GratefulDead",
    "limit": 10,
    "dry_run": true
  }'
```

Response:
```json
{
  "job_id": "import_20240115_abc123",
  "status": "completed",
  "artist_name": "Grateful Dead",
  "collection_id": "GratefulDead",
  "total_shows": 10,
  "processed_shows": 10,
  "tracks_created": 150,
  "tracks_updated": 0,
  "error_count": 0,
  "progress": 100.0
}
```

#### GET /V1/archive/import/:jobId
Get import job status.

```bash
curl -X GET "https://yourstore.com/rest/V1/archive/import/import_20240115_abc123" \
  -H "Authorization: Bearer TOKEN"
```

#### DELETE /V1/archive/import/:jobId
Cancel a running import job.

```bash
curl -X DELETE "https://yourstore.com/rest/V1/archive/import/import_20240115_abc123" \
  -H "Authorization: Bearer TOKEN"
```

#### GET /V1/archive/collections
List configured collections.

```bash
# Without stats
curl -X GET "https://yourstore.com/rest/V1/archive/collections" \
  -H "Authorization: Bearer TOKEN"

# With import statistics (slower - queries Archive.org)
curl -X GET "https://yourstore.com/rest/V1/archive/collections?include_stats=true" \
  -H "Authorization: Bearer TOKEN"
```

#### GET /V1/archive/collections/:collectionId
Get details for a specific collection.

```bash
curl -X GET "https://yourstore.com/rest/V1/archive/collections/GratefulDead" \
  -H "Authorization: Bearer TOKEN"
```

#### DELETE /V1/archive/products/:sku
Delete an imported product.

```bash
curl -X DELETE "https://yourstore.com/rest/V1/archive/products/archive-abc123def456" \
  -H "Authorization: Bearer TOKEN"
```

### ACL Resources

| Resource | Description |
|----------|-------------|
| `ArchiveDotOrg_Core::import_start` | Start import jobs |
| `ArchiveDotOrg_Core::import_status` | View import job status |
| `ArchiveDotOrg_Core::import_cancel` | Cancel import jobs |
| `ArchiveDotOrg_Core::collections_view` | View collections |
| `ArchiveDotOrg_Core::products_delete` | Delete products |

## Product Attributes

The module creates these product EAV attributes:

| Attribute | Type | Description |
|-----------|------|-------------|
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

## Category Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `is_artist` | boolean | Marks category as artist container |
| `is_album` | boolean | Marks category as album/show |
| `is_song` | boolean | Marks category as song |
| `archive_collection_id` | varchar | Archive.org collection ID |
| `track_number` | select | Track position in album |

## Admin Configuration

Navigate to: **Stores > Configuration > Archive.org Import**

### General Settings
- **Enable Module**: Toggle module functionality
- **Debug Mode**: Verbose logging to `var/log/archivedotorg.log`

### API Settings
- **Base URL**: Archive.org API endpoint (default: https://archive.org)
- **Timeout**: HTTP request timeout in seconds
- **Retry Attempts**: Number of retries for failed API calls
- **Retry Delay**: Milliseconds between retries

### Import Settings
- **Batch Size**: Products to process before clearing caches
- **Audio Format**: Preferred format (FLAC, MP3, OGG, SHN)
- **Import Images**: Enable spectrogram image import
- **Attribute Set ID**: Product attribute set for imports
- **Default Website ID**: Website to assign products to

### Artist Mappings
Dynamic grid to map artist names to Archive.org collection IDs and Magento categories.

### Scheduled Import
- **Enable Cron**: Toggle scheduled imports
- **Cron Schedule**: Cron expression (default: `0 2 * * *` = 2 AM daily)

## Cron Jobs

| Job | Schedule | Description |
|-----|----------|-------------|
| `archivedotorg_import_shows` | Configurable | Imports new shows from configured collections |
| `archivedotorg_sync_albums` | Daily 3 AM | Syncs category structure |
| `archivedotorg_cleanup_progress` | Daily 4 AM | Cleans up stale progress files |

## Audio Player

The module includes a jPlayer-based audio player that appears on:

- **Cart page**: Plays tracks added to cart
- **Category pages**: Plays all tracks in artist/album categories
- **Product pages**: Plays the single track

The player supports:
- Play/pause, next/previous
- Shuffle and repeat modes
- Volume control
- Playlist display

## Data Flow

```
Archive.org API
      │
      ▼
ArchiveApiClient (HTTP with retry)
      │
      ▼
ShowImporter (orchestration, batching)
      │
      ├──▶ TrackImporter ──▶ Magento Products
      │
      ├──▶ CategoryAssignmentService ──▶ Category Links
      │
      └──▶ ImageImportService ──▶ Product Images
```

## Logging

All operations log to `var/log/archivedotorg.log`:

```php
$this->logger->logImportStart($artist, $collection, $limit, $offset);
$this->logger->logShowProcessed($identifier, $title, $trackCount);
$this->logger->logTrackCreated($sku, $title);
$this->logger->logImportError($message, $context);
$this->logger->logImportComplete($resultArray);
```

## Error Handling

Custom exceptions provide context for debugging:

```php
// API errors
throw ApiException::httpError($url, $statusCode, $responseBody);
throw ApiException::timeout($url, $timeout);
throw ApiException::parseError($url, $parseError);

// Import errors
throw ImportException::trackFailed($sku, $reason);
throw ImportException::showFailed($identifier, $reason);

// Configuration errors
throw ConfigurationException::missingRequired('collection_id');
throw ConfigurationException::invalidValue('batch_size', $value);
```

## Performance Considerations

1. **Batch Processing**: Imports process in configurable batch sizes with cache clearing between batches
2. **Indexer Management**: BulkProductImporter sets indexers to "scheduled" mode during large imports
3. **Memory Management**: Explicit `gc_collect_cycles()` calls and cache clearing prevent memory leaks
4. **Direct SQL**: BulkProductImporter bypasses Magento's repository layer for 10x+ faster imports
5. **Caching**: AttributeOptionManager and CategoryAssignmentService cache lookups

## Async Import Queue

The module supports background processing of imports via Magento's message queue system.

### Starting an Async Import

```php
use ArchiveDotOrg\Core\Api\ImportPublisherInterface;

// Inject ImportPublisherInterface
$job = $this->importPublisher->publish(
    artistName: 'Grateful Dead',
    collectionId: 'GratefulDead',
    limit: 100,
    offset: 0,
    dryRun: false
);

echo "Job ID: " . $job->getJobId();
```

### Queue Consumers

Start the consumers to process queued jobs:

```bash
# Process import jobs
bin/magento queue:consumers:start archivedotorg.import.job.consumer

# Process status updates (for future dashboard features)
bin/magento queue:consumers:start archivedotorg.import.status.consumer
```

### Job Status Management

Jobs are persisted in `var/archivedotorg/jobs/` as JSON files:

```php
use ArchiveDotOrg\Core\Model\Queue\JobStatusManager;

// Get job status
$job = $jobStatusManager->getJob($jobId);
echo $job->getStatus(); // queued, running, completed, failed, cancelled

// List all jobs
$jobs = $jobStatusManager->getJobs(status: 'running', limit: 10);

// Cancel a job
$importPublisher->cancel($jobId);

// Cleanup old jobs
$cleaned = $jobStatusManager->cleanupOldJobs(olderThanDays: 7);
```

### Queue Topics

| Topic | Description |
|-------|-------------|
| `archivedotorg.import.job` | Import job requests |
| `archivedotorg.import.status` | Status update notifications |

## Audio Player Features

The enhanced audio player includes:

### Playback Controls
- Play/pause, stop, previous/next
- Shuffle mode (randomizes playlist order)
- Repeat modes: none, all (loop playlist), one (loop track)

### Volume
- Mute toggle
- Volume slider (click to set level)

### Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Space` / `K` | Play/Pause |
| `←` / `→` | Seek 10 seconds |
| `Shift+←` / `Shift+→` | Previous/Next track |
| `↑` / `↓` | Volume up/down |
| `M` | Mute |
| `S` | Toggle shuffle |
| `R` | Cycle repeat mode |
| `0`-`9` | Jump to 0-90% of track |

### Persistence

Player state is saved to localStorage:
- Current track position
- Shuffle on/off
- Repeat mode
- Volume level

### Accessibility

- ARIA roles and labels on all controls
- Keyboard navigation in playlist
- Screen reader announcements for state changes
- Focus indicators
- Reduced motion support

### Responsive Design

- Mobile-first CSS with breakpoints at 480px and 768px
- Dark mode support via `prefers-color-scheme`
- High contrast mode support
- Touch-friendly controls (44px minimum touch targets)

## Testing

### Unit Tests

The module includes a comprehensive PHPUnit test suite covering all core services.

```bash
# Run all unit tests
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml

# Run tests with coverage
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml --coverage-html coverage-report

# Run specific test file
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml --filter ArchiveApiClientTest

# Run tests with verbose output
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml --testdox
```

**Test Coverage:**

| Test File | Class Under Test | Tests |
|-----------|------------------|-------|
| `ArchiveApiClientTest.php` | ArchiveApiClient | Retry logic, timeout, HTTP errors, JSON parsing |
| `ShowImporterTest.php` | ShowImporter | Batch processing, category assignment, progress callback, dry-run |
| `TrackImporterTest.php` | TrackImporter | Product creation/update, SKU validation, attribute mapping |
| `AttributeOptionManagerTest.php` | AttributeOptionManager | Option creation, caching, bulk operations |
| `BulkProductImporterTest.php` | BulkProductImporter | Direct SQL, indexer management, batch processing |
| `CategoryAssignmentServiceTest.php` | CategoryAssignmentService | Category creation, bulk assignment, caching |
| `ImportShowsCommandTest.php` | ImportShowsCommand | Input validation, argument parsing, dry-run mode |

### Manual Testing

```bash
# Check module status
bin/magento archive:status

# Test API connectivity
bin/magento archive:status --test-collection=GratefulDead

# Dry run import
bin/magento archive:import:shows "Test Artist" --collection=TestCollection --dry-run --limit=5

# Small import test
bin/magento archive:import:shows "STS9" --collection=STS9 --limit=10
```

## Security

### Input Validation (ImportShowsCommand)

The CLI command validates all inputs before processing:
- **Artist name**: Must be non-empty string (whitespace is trimmed)
- **Collection ID**: Must match pattern `^[a-zA-Z0-9_-]+$` (alphanumeric, underscores, hyphens only)
- **Limit**: Must be a positive integer (> 0)
- **Offset**: Must be a non-negative integer (>= 0)

### JavaScript Escaping (Player Block)

The Player block uses secure JSON encoding for JavaScript output:
- Uses `json_encode()` with `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP`
- Prevents XSS attacks from malicious track titles containing quotes or HTML
- Fallback escaping for encoding failures

## Admin UI

### Imported Products Grid

Navigate to: **Catalog > Archive.org > Imported Products**

The admin grid displays all products imported from Archive.org with:

**Columns:**
- ID, SKU, Name, Track Title
- Artist/Collection (dropdown filter)
- Year (dropdown filter)
- Venue (dropdown filter)
- Show Identifier (hidden by default)
- Imported date (date range filter)
- Status

**Row Actions:**
- **Edit**: Open product in catalog editor
- **Delete**: Remove single product
- **Re-import**: Refresh metadata from Archive.org
- **View on Archive.org**: Opens show page in new tab

**Mass Actions:**
- Bulk delete selected products

### Import Jobs (Placeholder)

Navigate to: **Catalog > Archive.org > Import Jobs**

This page provides CLI instructions for running imports. A full import job management interface will be added in a future update with async queue processing.

## Migration from Legacy Modules

This module replaces:
- `ArchiveDotOrg_Shell`
- `ArchiveDotOrg_ProductAttributes`
- `ArchiveDotOrg_CategoryWork`
- `ArchiveDotOrg_Player`

The `MigrateFromLegacyModule` data patch verifies existing data compatibility. Legacy modules can remain installed during transition - disable them after verifying the new module works correctly.

## File Count Summary

- **PHP Files**: 70 (including 7 test files, 5 admin controllers, 5 UI components, 5 queue classes)
- **XML Config**: 20 (including phpunit.xml, webapi.xml, admin routes/menu/layouts, UI component, queue config)
- **Frontend Assets**: 4 (phtml, js - enhanced)
- **Admin Templates**: 1 (placeholder.phtml)
- **Translations**: 1 (123 strings)
- **Total**: ~96 files

### New in Phase 2

```
Api/
├── ImportManagementInterface.php       # REST API service contract
└── Data/
    ├── ImportJobInterface.php          # Import job DTO
    └── CollectionInfoInterface.php     # Collection info DTO

Model/
├── ImportManagement.php                # REST API implementation
└── Data/
    ├── ImportJob.php                   # Import job DTO implementation
    └── CollectionInfo.php              # Collection info DTO implementation

Console/Command/
└── CleanupProductsCommand.php          # Product cleanup CLI command

etc/
└── webapi.xml                          # REST API routes
```

### New in Phase 3

```
Controller/Adminhtml/
├── Product/
│   ├── Index.php          # Products grid page
│   ├── Delete.php         # Single product delete
│   ├── MassDelete.php     # Bulk delete with Filter
│   └── Reimport.php       # Re-import from Archive.org
└── Import/
    └── Index.php          # Import jobs placeholder page

Ui/Component/
├── DataProvider/
│   └── Product/
│       └── Listing.php    # Grid data provider
└── Listing/
    └── Column/
        ├── ProductActions.php     # Actions column (Edit, Delete, Reimport, View)
        ├── CollectionOptions.php  # Artist/Collection filter options
        ├── YearOptions.php        # Year filter options
        └── VenueOptions.php       # Venue filter options

etc/adminhtml/
├── routes.xml             # Admin route registration
└── menu.xml               # Admin menu entries

view/adminhtml/
├── layout/
│   ├── archivedotorg_product_index.xml   # Product grid layout
│   └── archivedotorg_import_index.xml    # Import jobs layout
├── ui_component/
│   └── archivedotorg_product_listing.xml # UI Component grid definition
└── templates/
    └── import/
        └── placeholder.phtml              # Import jobs placeholder
```

### New in Phase 4

```
Api/
└── ImportPublisherInterface.php       # Async queue publisher interface

Model/Queue/
├── ImportPublisher.php                # Publishes import jobs to message queue
├── ImportConsumer.php                 # Processes import jobs from queue
├── StatusConsumer.php                 # Processes status update messages
└── JobStatusManager.php               # Job status persistence (JSON files)

etc/
├── communication.xml                  # Message queue topics
├── queue_topology.xml                 # Exchange and queue bindings
├── queue_publisher.xml                # Publisher configuration
└── queue_consumer.xml                 # Consumer configuration

view/frontend/
├── web/js/player.js                   # Enhanced with shuffle, repeat, keyboard, localStorage
└── templates/player.phtml             # Mobile-first, dark mode, ARIA accessibility
```

### Test Files Structure

```
Test/
├── phpunit.xml
└── Unit/
    ├── Block/
    ├── Console/
    │   └── Command/
    │       └── ImportShowsCommandTest.php
    └── Model/
        ├── ArchiveApiClientTest.php
        ├── AttributeOptionManagerTest.php
        ├── BulkProductImporterTest.php
        ├── CategoryAssignmentServiceTest.php
        ├── ShowImporterTest.php
        └── TrackImporterTest.php
```

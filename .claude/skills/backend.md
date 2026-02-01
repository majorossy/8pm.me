# Magento 2 Backend Developer - 8PM Project

You are a Magento 2 / PHP specialist for the 8PM live music archive backend.

## Critical Knowledge

**Module Location:** `src/app/code/ArchiveDotOrg/`
**Pattern:** Service Contract (Interface in `Api/`, Implementation in `Model/`)
**Logging:** Custom logger writes to `var/log/archivedotorg.log`
**CLI Base:** Extend `BaseLoggedCommand` for automatic database logging
**After Code Changes:** Run `bin/magento setup:di:compile` (required!)

## Module Structure

```
src/app/code/ArchiveDotOrg/
├── Core/                   # Main module (imports, API, CLI)
│   ├── Api/               # Service interfaces
│   ├── Console/Command/   # 25+ CLI commands
│   ├── Controller/        # REST API controllers
│   ├── Cron/              # Scheduled jobs
│   ├── Model/             # Service implementations
│   ├── etc/               # Configuration (di.xml, schema.graphqls)
│   └── config/artists/    # Artist YAML configs
├── Admin/                  # Admin UI module
├── Shell/                  # Shell utilities
├── Player/                 # Audio player integration
├── ProductAttributes/      # Custom product attributes
└── CategoryWork/           # Category management
```

## Critical Files

| File | Purpose |
|------|---------|
| `Core/etc/di.xml` | Dependency injection configuration |
| `Core/etc/schema.graphqls` | GraphQL schema (20+ custom fields) |
| `Core/etc/db_schema.xml` | Custom database tables |
| `Core/etc/crontab.xml` | Cron job schedules |
| `Core/Model/ArchiveApiClient.php` | Archive.org HTTP client |
| `Core/Model/ShowImporter.php` | Main import orchestration |
| `Core/Model/MetadataDownloader.php` | Download with correlation ID |
| `Core/Model/TrackPopulatorService.php` | Hybrid track matching |
| `Core/Model/ArtistEnrichmentService.php` | Wikipedia/Brave enrichment |
| `Core/Console/Command/BaseLoggedCommand.php` | CLI base with DB logging |

## CLI Commands

```bash
# Import & Sync
bin/magento archive:download "Artist"           # Download metadata
bin/magento archive:download "Artist" --incremental  # Resume download
bin/magento archive:populate "Artist"           # Create products
bin/magento archive:populate "Artist" --force   # Re-import existing
bin/magento archive:populate "Artist" --dry-run # Preview without changes
bin/magento archive:import:shows "Artist" --limit=50
bin/magento archive:sync:albums
bin/magento archive:refresh:products "Artist"

# Artist Enrichment
bin/magento archive:artist:enrich "Artist" --fields=bio,origin,stats
bin/magento archive:artist:enrich "Artist" --dry-run

# Album Artwork
bin/magento archive:artwork:download "Artist" --limit=20
bin/magento archive:artwork:update
bin/magento archive:artwork:retry

# Setup & Validation
bin/magento archive:setup                       # Setup from YAML
bin/magento archive:validate                    # Validate config
bin/magento archive:show-unmatched "Artist"     # Unmatched tracks
bin/magento archive:show-unmatched "Artist" --export=yaml

# Status & Cleanup
bin/magento archive:status
bin/magento archive:cleanup:products --collection=ArtistName
bin/magento archive:cleanup:products --collection=ArtistName --dry-run

# Benchmarks
bin/magento archive:benchmark:dashboard
bin/magento archive:benchmark:import
```

## Service Contract Pattern

```php
// 1. Interface in Api/
namespace ArchiveDotOrg\Core\Api;

interface ShowImporterInterface
{
    /**
     * @param string $artistName
     * @param int $limit
     * @return \ArchiveDotOrg\Core\Api\Data\ImportResultInterface
     */
    public function import(string $artistName, int $limit = 100): ImportResultInterface;
}

// 2. Implementation in Model/
namespace ArchiveDotOrg\Core\Model;

class ShowImporter implements \ArchiveDotOrg\Core\Api\ShowImporterInterface
{
    public function __construct(
        private readonly ArchiveApiClient $apiClient,
        private readonly ProductFactory $productFactory,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {}

    public function import(string $artistName, int $limit = 100): ImportResultInterface
    {
        $this->logger->info('Import started', ['artist' => $artistName]);
        // Implementation...
    }
}

// 3. Register in etc/di.xml
<preference for="ArchiveDotOrg\Core\Api\ShowImporterInterface"
            type="ArchiveDotOrg\Core\Model\ShowImporter" />
```

## Creating CLI Commands

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends BaseLoggedCommand
{
    protected function configure(): void
    {
        $this->setName('archive:mycommand')
             ->setDescription('My command description')
             ->addArgument('artist', InputArgument::REQUIRED, 'Artist name')
             ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit', 100)
             ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only');
        parent::configure();
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $artist = $input->getArgument('artist');
        $limit = (int) $input->getOption('limit');
        $dryRun = $input->getOption('dry-run');

        // BaseLoggedCommand automatically handles:
        // - Import run logging to database
        // - Memory peak tracking
        // - Duration calculation
        // - Signal handling (SIGTERM/SIGINT)

        $output->writeln("Processing {$artist}...");

        return self::SUCCESS;
    }
}
```

Register in `etc/di.xml`:
```xml
<type name="Magento\Framework\Console\CommandList">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="archiveMyCommand" xsi:type="object">
                ArchiveDotOrg\Core\Console\Command\MyCommand
            </item>
        </argument>
    </arguments>
</type>
```

**After adding:** Run `bin/magento setup:di:compile`

## Adding GraphQL Fields

### 1. Add to schema.graphqls
```graphql
# etc/schema.graphqls
interface ProductInterface {
    my_custom_field: String @resolver(class: "ArchiveDotOrg\\Core\\Model\\Resolver\\MyCustomField")
}
```

### 2. Create Resolver
```php
<?php
namespace ArchiveDotOrg\Core\Model\Resolver;

use Magento\Framework\GraphQL\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQL\Config\Element\Field;
use Magento\Framework\GraphQL\Query\ResolverInterface;

class MyCustomField implements ResolverInterface
{
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            return null;
        }

        $product = $value['model'];
        return $product->getData('my_custom_attribute');
    }
}
```

### 3. Compile and Deploy
```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

## REST API Endpoints

```
POST   /V1/archive/import              # Start import job
GET    /V1/archive/import/:jobId       # Get job status
DELETE /V1/archive/import/:jobId       # Cancel job
GET    /V1/archive/collections         # List collections
GET    /V1/archive/collections/:id     # Collection details
DELETE /V1/archive/products/:sku       # Delete product
```

## Cron Jobs (5 total)

| Job | Schedule | Purpose |
|-----|----------|---------|
| `archivedotorg_import_shows` | Configurable (Admin) | Auto-import from collections |
| `archivedotorg_sync_albums` | 0 4 * * * (4 AM) | Sync categories with products |
| `archivedotorg_cleanup_progress` | 0 0 * * 0 (Sunday) | Clean stale progress files |
| `archivedotorg_process_import_queue` | * * * * * (every min) | Process async import queue |
| `archivedotorg_enrich_artist_stats` | 0 3 1 * * (1st of month) | Update artist statistics |

## Database Tables (2 in db_schema.xml)

| Table | Purpose |
|-------|---------|
| `archivedotorg_activity_log` | Operation tracking |
| `archivedotorg_studio_albums` | Album artwork cache |

**Note:** Additional tables (`archivedotorg_artist`, etc.) created via Schema Patches.

## Logging

```php
// Inject LoggerInterface
public function __construct(
    private readonly \Psr\Log\LoggerInterface $logger
) {}

// Use with context arrays for structured logging
$this->logger->info('Import started', [
    'artist' => $artistName,
    'correlation_id' => $this->correlationId
]);

$this->logger->error('Import failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);

// Writes to: var/log/archivedotorg.log
```

## Memory Management for Large Operations

```php
// For imports with 1000+ products
public function importLarge(array $items): void
{
    $batchSize = 100;
    $batches = array_chunk($items, $batchSize);

    foreach ($batches as $batch) {
        $this->processBatch($batch);

        // Clear entity manager to free memory
        $this->entityManager->clear();

        // Force garbage collection
        gc_collect_cycles();
    }
}
```

## Exception Handling

```php
use ArchiveDotOrg\Core\Exception\ImportException;
use ArchiveDotOrg\Core\Exception\ApiException;
use ArchiveDotOrg\Core\Exception\ConfigurationException;

try {
    $result = $this->importer->import($artist);
} catch (ApiException $e) {
    // Archive.org API error - may be transient
    $this->logger->warning('API error, will retry', ['error' => $e->getMessage()]);
    throw $e;
} catch (ConfigurationException $e) {
    // YAML config error - needs manual fix
    $this->logger->error('Config error', ['error' => $e->getMessage()]);
    throw $e;
} catch (ImportException $e) {
    // Import-specific error
    $this->logger->error('Import failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

## Common Commands

```bash
# Cache management
bin/magento cache:flush                # Clear all cache
bin/magento cache:clean                # Clean specific caches

# After code changes (REQUIRED)
bin/magento setup:di:compile           # Compile DI

# After schema.graphqls changes
bin/magento setup:static-content:deploy -f

# After db_schema.xml changes
bin/magento setup:upgrade
bin/magento setup:db-schema:upgrade

# Module management
bin/magento module:status              # List modules
bin/magento module:enable Module_Name
bin/magento module:disable Module_Name

# Mode
bin/magento deploy:mode:show
bin/magento deploy:mode:set developer
```

## Troubleshooting

### Common Errors & Solutions

| Error | Cause | Fix |
|-------|-------|-----|
| "Class not found" | DI not compiled | `bin/magento setup:di:compile` |
| "Command not found" | New command not registered | Check di.xml, run setup:di:compile |
| GraphQL field returns null | Resolver not registered | Check schema.graphqls, recompile |
| "Lock service timeout" | Concurrent import running | Wait or kill other process |
| "Correlation ID missing" | Not extending BaseLoggedCommand | Extend BaseLoggedCommand |
| Memory exhausted | Large import without batching | Use --limit flag, add gc_collect_cycles() |

### Debugging

```bash
# Enable Xdebug for CLI
bin/xdebug enable
bin/magento archive:download "Test" --limit=1

# Tail logs
tail -f var/log/archivedotorg.log
tail -f var/log/system.log
tail -f var/log/exception.log

# Check for PHP errors
tail -f var/log/php-fpm-error.log
```

## Production Deployment Checklist

```bash
# Pre-deployment
bin/magento maintenance:enable

# Deploy code changes
bin/magento setup:upgrade              # Run migrations
bin/magento setup:di:compile           # Compile DI
bin/magento setup:static-content:deploy -f  # Static files
bin/magento cache:flush                # Clear cache
bin/magento indexer:reindex            # Reindex

# Verify
bin/magento module:status              # Check modules
bin/magento deploy:mode:show           # Should be 'production'

# Start queue consumers
bin/magento queue:consumers:start archivedotorg.import.job.consumer &

# Post-deployment
bin/magento maintenance:disable
```

## Queue Consumers

For background import processing:

```bash
# Start consumer (runs indefinitely)
bin/magento queue:consumers:start archivedotorg.import.job.consumer

# List all consumers
bin/magento queue:consumers:list

# Check queue status
bin/magento queue:consumers:list --all
```

## Reference

See main `CLAUDE.md` for:
- Full Docker setup
- Database access
- Admin credentials
- Import workflow

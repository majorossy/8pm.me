<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\ShowInterface;

/**
 * Bulk Product Importer Interface
 *
 * High-performance bulk import using direct resource model operations
 */
interface BulkProductImporterInterface
{
    /**
     * Import multiple tracks as products in bulk
     *
     * @param ShowInterface[] $shows
     * @param string $artistName
     * @param callable|null $progressCallback
     * @return array{created: int, updated: int, skipped: int, errors: array}
     */
    public function importBulk(
        array $shows,
        string $artistName,
        ?callable $progressCallback = null
    ): array;

    /**
     * Prepare indexers for bulk import (set to scheduled mode)
     *
     * @return array Original indexer modes keyed by indexer ID
     */
    public function prepareIndexers(): array;

    /**
     * Restore indexer modes after bulk import
     *
     * @param array $originalModes
     * @return void
     */
    public function restoreIndexers(array $originalModes): void;

    /**
     * Reindex all relevant indexes
     *
     * @return void
     */
    public function reindexAll(): void;
}

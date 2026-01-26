<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\ImportResultInterface;

/**
 * Show Importer Interface
 *
 * Orchestrates the import of shows and tracks from Archive.org
 */
interface ShowImporterInterface
{
    /**
     * Import shows by collection ID
     *
     * @param string $artistName
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @param callable|null $progressCallback Callback: function(int $total, int $current, string $message)
     * @return ImportResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importByCollection(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null,
        ?callable $progressCallback = null
    ): ImportResultInterface;

    /**
     * Import a single show by identifier
     *
     * @param string $identifier
     * @param string $artistName
     * @return ImportResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importShow(string $identifier, string $artistName): ImportResultInterface;

    /**
     * Perform a dry run without saving products
     *
     * @param string $artistName
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @return ImportResultInterface
     */
    public function dryRun(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null
    ): ImportResultInterface;
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\ShowInterface;

/**
 * Archive.org API Client Interface
 *
 * Handles all HTTP communication with Archive.org
 */
interface ArchiveApiClientInterface
{
    /**
     * Fetch all identifiers from a collection
     *
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @return string[] Array of identifier strings
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetchCollectionIdentifiers(
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Fetch show metadata by identifier
     *
     * @param string $identifier
     * @return ShowInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetchShowMetadata(string $identifier): ShowInterface;

    /**
     * Test API connectivity
     *
     * @return bool
     */
    public function testConnection(): bool;

    /**
     * Get the count of items in a collection
     *
     * @param string $collectionId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCollectionCount(string $collectionId): int;

    /**
     * Fetch ratings and download stats for multiple identifiers in a single API call
     *
     * Uses Archive.org's advanced search API to batch-fetch stats for up to 100 identifiers.
     * This is much more efficient than fetching metadata for each show individually.
     *
     * @param array $identifiers List of show identifiers (max 100 recommended)
     * @return array Associative array [identifier => [
     *     'avg_rating' => float|null,
     *     'num_reviews' => int|null,
     *     'downloads' => int|null,
     *     'downloads_week' => int|null,
     *     'downloads_month' => int|null
     * ]]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetchBatchStats(array $identifiers): array;
}

<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Service for downloading and caching show metadata from Archive.org
 *
 * Handles:
 * - Fetching show identifiers from collections
 * - Best version selection (SBD > rating > reviews > downloads)
 * - Local caching of metadata JSON files
 * - Progress tracking and resumability
 * - Incremental updates
 */
interface MetadataDownloaderInterface
{
    /**
     * Download metadata for all shows in a collection
     *
     * @param string $collectionId Archive.org collection ID (e.g., 'GratefulDead')
     * @param int|null $limit Maximum shows to download (null = all)
     * @param bool $force Force re-download even if cached
     * @param bool $incremental Only fetch shows added since last run
     * @param string|null $since Only fetch shows added since this date (YYYY-MM-DD)
     * @param callable|null $progressCallback fn(int $total, int $current, string $message)
     * @return array{
     *     total_recordings: int,
     *     unique_shows: int,
     *     downloaded: int,
     *     cached: int,
     *     failed: int,
     *     failed_identifiers: array<string>
     * }
     */
    public function download(
        string $collectionId,
        ?int $limit = null,
        bool $force = false,
        bool $incremental = false,
        ?string $since = null,
        ?callable $progressCallback = null
    ): array;

    /**
     * Get progress for a collection download
     *
     * @param string $collectionId
     * @return array{
     *     status: string,
     *     started_at: string|null,
     *     completed_at: string|null,
     *     last_full_sync: string|null,
     *     last_incremental: string|null,
     *     total_recordings: int,
     *     unique_shows: int,
     *     downloaded: int,
     *     failed: int,
     *     failed_identifiers: array<string>
     * }|null
     */
    public function getProgress(string $collectionId): ?array;

    /**
     * Get cached metadata for a show
     *
     * @param string $identifier Archive.org show identifier
     * @return array|null Show metadata or null if not cached
     */
    public function getCachedMetadata(string $identifier): ?array;

    /**
     * Check if metadata is cached for a show
     *
     * @param string $identifier
     * @return bool
     */
    public function isCached(string $identifier): bool;

    /**
     * Get list of downloaded identifiers for a collection
     *
     * @param string $collectionId
     * @return array<string>
     */
    public function getDownloadedIdentifiers(string $collectionId): array;

    /**
     * Select best recording per show date from search results
     *
     * Ranking: SBD > rating > reviews > downloads
     *
     * @param array $searchResults Raw results from Archive.org search API
     * @return array<string> List of winning identifiers (one per show date)
     */
    public function selectBestRecordings(array $searchResults): array;

    /**
     * Retry downloading failed identifiers
     *
     * @param string $collectionId
     * @param callable|null $progressCallback
     * @return array{downloaded: int, still_failed: int}
     */
    public function retryFailed(string $collectionId, ?callable $progressCallback = null): array;

    /**
     * Get all configured collections
     *
     * @return array<string, array{artist_name: string, identifier_pattern: string}>
     */
    public function getAllCollections(): array;
}

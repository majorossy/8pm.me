<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Service for populating track categories with products from cached metadata
 *
 * Reads cached show metadata (from MetadataDownloader) and creates
 * products for each track, assigning them to matching track categories.
 */
interface TrackPopulatorServiceInterface
{
    /**
     * Populate track categories with products for an artist
     *
     * @param string $artistName Artist name as it appears in Magento categories
     * @param string $collectionId Archive.org collection ID
     * @param int|null $limit Maximum shows to process (null = all)
     * @param bool $dryRun If true, don't create products, just report what would happen
     * @param callable|null $progressCallback fn(int $total, int $current, string $message)
     * @return array{
     *     shows_processed: int,
     *     products_created: int,
     *     products_skipped: int,
     *     tracks_matched: int,
     *     tracks_unmatched: int,
     *     categories_populated: int,
     *     categories_empty: int,
     *     errors: array<string>
     * }
     */
    public function populate(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array;

    /**
     * Get track categories for an artist
     *
     * @param string $artistName
     * @return array<int, array{id: int, name: string, path: string}>
     */
    public function getTrackCategoriesForArtist(string $artistName): array;

    /**
     * Normalize a track title for matching
     *
     * @param string|null $title
     * @return string
     */
    public function normalizeTitle(?string $title): string;

    /**
     * Build a title lookup map for matching
     *
     * @param array $categories Array of track categories
     * @return array<string, array<int>> normalized_title => [category_ids]
     */
    public function buildTitleLookupMap(array $categories): array;
}

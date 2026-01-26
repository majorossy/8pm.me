<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;

/**
 * Track Importer Interface
 *
 * Handles creation and updating of Magento products from Archive.org track data
 */
interface TrackImporterInterface
{
    /**
     * Import a single track as a Magento product
     *
     * @param TrackInterface $track
     * @param ShowInterface $show
     * @param string $artistName
     * @param int|null $existingProductId If provided, skips the product existence check (optimization for bulk imports)
     * @return int Product ID
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importTrack(
        TrackInterface $track,
        ShowInterface $show,
        string $artistName,
        ?int $existingProductId = null
    ): int;

    /**
     * Import all tracks from a show
     *
     * @param ShowInterface $show
     * @param string $artistName
     * @return array{created: int, updated: int, skipped: int, product_ids: int[]}
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importShowTracks(ShowInterface $show, string $artistName): array;

    /**
     * Check if a product exists for the given SKU
     *
     * @param string $sku
     * @return bool
     */
    public function productExists(string $sku): bool;

    /**
     * Get product ID by SKU
     *
     * @param string $sku
     * @return int|null
     */
    public function getProductIdBySku(string $sku): ?int;
}

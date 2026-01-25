<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Category Assignment Service Interface
 *
 * Handles product-to-category assignment during imports
 */
interface CategoryAssignmentServiceInterface
{
    /**
     * Assign a product to categories based on artist/collection
     *
     * @param int $productId
     * @param string $artistName
     * @param string|null $collectionId
     * @return bool Success status
     */
    public function assignToArtistCategory(
        int $productId,
        string $artistName,
        ?string $collectionId = null
    ): bool;

    /**
     * Assign a product to a show/album category
     *
     * @param int $productId
     * @param string $showIdentifier Archive.org show identifier
     * @param string $showTitle
     * @param int|null $parentCategoryId Artist category ID
     * @return int|null Created or found category ID
     */
    public function assignToShowCategory(
        int $productId,
        string $showIdentifier,
        string $showTitle,
        ?int $parentCategoryId = null
    ): ?int;

    /**
     * Get or create category for an artist
     *
     * @param string $artistName
     * @param string|null $collectionId
     * @return int|null Category ID
     */
    public function getOrCreateArtistCategory(
        string $artistName,
        ?string $collectionId = null
    ): ?int;

    /**
     * Get or create category for a show/album
     *
     * @param string $showIdentifier
     * @param string $showTitle
     * @param int $parentCategoryId
     * @return int|null Category ID
     */
    public function getOrCreateShowCategory(
        string $showIdentifier,
        string $showTitle,
        int $parentCategoryId
    ): ?int;

    /**
     * Get category ID by collection ID
     *
     * @param string $collectionId
     * @return int|null Category ID or null if not found
     */
    public function getCategoryByCollectionId(string $collectionId): ?int;

    /**
     * Bulk assign products to category
     *
     * @param int[] $productIds
     * @param int $categoryId
     * @return int Number of assignments made
     */
    public function bulkAssignToCategory(array $productIds, int $categoryId): int;

    /**
     * Clear internal caches
     *
     * @return void
     */
    public function clearCache(): void;
}

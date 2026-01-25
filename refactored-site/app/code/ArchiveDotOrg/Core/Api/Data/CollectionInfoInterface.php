<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Collection Info Interface
 *
 * Represents information about a configured Archive.org collection
 */
interface CollectionInfoInterface
{
    /**
     * Get artist name
     *
     * @return string
     */
    public function getArtistName(): string;

    /**
     * Set artist name
     *
     * @param string $artistName
     * @return $this
     */
    public function setArtistName(string $artistName): self;

    /**
     * Get collection ID
     *
     * @return string
     */
    public function getCollectionId(): string;

    /**
     * Set collection ID
     *
     * @param string $collectionId
     * @return $this
     */
    public function setCollectionId(string $collectionId): self;

    /**
     * Get category ID
     *
     * @return int|null
     */
    public function getCategoryId(): ?int;

    /**
     * Set category ID
     *
     * @param int|null $categoryId
     * @return $this
     */
    public function setCategoryId(?int $categoryId): self;

    /**
     * Get total items in collection (from Archive.org)
     *
     * @return int|null
     */
    public function getTotalItems(): ?int;

    /**
     * Set total items
     *
     * @param int|null $totalItems
     * @return $this
     */
    public function setTotalItems(?int $totalItems): self;

    /**
     * Get imported product count
     *
     * @return int
     */
    public function getImportedCount(): int;

    /**
     * Set imported count
     *
     * @param int $importedCount
     * @return $this
     */
    public function setImportedCount(int $importedCount): self;

    /**
     * Check if collection is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Set enabled status
     *
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self;
}

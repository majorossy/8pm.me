<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\CollectionInfoInterface;

/**
 * Collection Info DTO Implementation
 */
class CollectionInfo implements CollectionInfoInterface
{
    private string $artistName = '';
    private string $collectionId = '';
    private ?int $categoryId = null;
    private ?int $totalItems = null;
    private int $importedCount = 0;
    private bool $enabled = true;

    /**
     * @inheritDoc
     */
    public function getArtistName(): string
    {
        return $this->artistName;
    }

    /**
     * @inheritDoc
     */
    public function setArtistName(string $artistName): CollectionInfoInterface
    {
        $this->artistName = $artistName;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCollectionId(): string
    {
        return $this->collectionId;
    }

    /**
     * @inheritDoc
     */
    public function setCollectionId(string $collectionId): CollectionInfoInterface
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId(?int $categoryId): CollectionInfoInterface
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }

    /**
     * @inheritDoc
     */
    public function setTotalItems(?int $totalItems): CollectionInfoInterface
    {
        $this->totalItems = $totalItems;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @inheritDoc
     */
    public function setImportedCount(int $importedCount): CollectionInfoInterface
    {
        $this->importedCount = $importedCount;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function setEnabled(bool $enabled): CollectionInfoInterface
    {
        $this->enabled = $enabled;
        return $this;
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\TrackInterface;

/**
 * Track Data Transfer Object Implementation
 */
class Track implements TrackInterface
{
    private string $name = '';
    private string $title = '';
    private ?int $trackNumber = null;
    private ?string $length = null;
    private ?string $sha1 = null;
    private string $format = 'flac';
    private ?string $source = null;
    private ?int $fileSize = null;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): TrackInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): TrackInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTrackNumber(): ?int
    {
        return $this->trackNumber;
    }

    /**
     * @inheritDoc
     */
    public function setTrackNumber(?int $trackNumber): TrackInterface
    {
        $this->trackNumber = $trackNumber;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * @inheritDoc
     */
    public function setLength(?string $length): TrackInterface
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSha1(): ?string
    {
        return $this->sha1;
    }

    /**
     * @inheritDoc
     */
    public function setSha1(?string $sha1): TrackInterface
    {
        $this->sha1 = $sha1;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @inheritDoc
     */
    public function setFormat(string $format): TrackInterface
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @inheritDoc
     */
    public function setSource(?string $source): TrackInterface
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @inheritDoc
     */
    public function setFileSize(?int $fileSize): TrackInterface
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function generateSku(): string
    {
        // Use SHA1 as SKU (same as legacy implementation)
        return $this->sha1 ?? '';
    }

    /**
     * @inheritDoc
     */
    public function generateUrlKey(): string
    {
        $sku = $this->generateSku();
        // Sanitize to lowercase alphanumeric with hyphens, max 64 chars
        $urlKey = strtolower(preg_replace('#[^0-9a-z]+#i', '-', $sku) ?? '');
        return mb_substr(rtrim($urlKey, '-'), 0, 64);
    }
}

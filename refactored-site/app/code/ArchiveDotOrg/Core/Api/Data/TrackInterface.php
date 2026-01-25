<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Track Data Transfer Object Interface
 *
 * Represents a single track/song from an Archive.org show
 */
interface TrackInterface
{
    public const NAME = 'name';
    public const TITLE = 'title';
    public const TRACK_NUMBER = 'track_number';
    public const LENGTH = 'length';
    public const SHA1 = 'sha1';
    public const FORMAT = 'format';
    public const SOURCE = 'source';
    public const FILE_SIZE = 'file_size';

    /**
     * Get file name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set file name
     *
     * @param string $name
     * @return TrackInterface
     */
    public function setName(string $name): TrackInterface;

    /**
     * Get track title
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set track title
     *
     * @param string $title
     * @return TrackInterface
     */
    public function setTitle(string $title): TrackInterface;

    /**
     * Get track number
     *
     * @return int|null
     */
    public function getTrackNumber(): ?int;

    /**
     * Set track number
     *
     * @param int|null $trackNumber
     * @return TrackInterface
     */
    public function setTrackNumber(?int $trackNumber): TrackInterface;

    /**
     * Get track length (duration in seconds or formatted string)
     *
     * @return string|null
     */
    public function getLength(): ?string;

    /**
     * Set track length
     *
     * @param string|null $length
     * @return TrackInterface
     */
    public function setLength(?string $length): TrackInterface;

    /**
     * Get SHA1 hash
     *
     * @return string|null
     */
    public function getSha1(): ?string;

    /**
     * Set SHA1 hash
     *
     * @param string|null $sha1
     * @return TrackInterface
     */
    public function setSha1(?string $sha1): TrackInterface;

    /**
     * Get audio format (flac, mp3, etc.)
     *
     * @return string
     */
    public function getFormat(): string;

    /**
     * Set audio format
     *
     * @param string $format
     * @return TrackInterface
     */
    public function setFormat(string $format): TrackInterface;

    /**
     * Get source description
     *
     * @return string|null
     */
    public function getSource(): ?string;

    /**
     * Set source description
     *
     * @param string|null $source
     * @return TrackInterface
     */
    public function setSource(?string $source): TrackInterface;

    /**
     * Get file size in bytes
     *
     * @return int|null
     */
    public function getFileSize(): ?int;

    /**
     * Set file size in bytes
     *
     * @param int|null $fileSize
     * @return TrackInterface
     */
    public function setFileSize(?int $fileSize): TrackInterface;

    /**
     * Generate SKU from SHA1 hash
     *
     * @return string
     */
    public function generateSku(): string;

    /**
     * Generate URL key from SKU
     *
     * @return string
     */
    public function generateUrlKey(): string;
}

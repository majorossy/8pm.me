<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Import Result Data Transfer Object Interface
 *
 * Contains statistics and error information from an import operation
 */
interface ImportResultInterface
{
    public const SHOWS_PROCESSED = 'shows_processed';
    public const TRACKS_CREATED = 'tracks_created';
    public const TRACKS_UPDATED = 'tracks_updated';
    public const TRACKS_SKIPPED = 'tracks_skipped';
    public const ERRORS = 'errors';
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const ARTIST_NAME = 'artist_name';
    public const COLLECTION_ID = 'collection_id';

    /**
     * Get number of shows processed
     *
     * @return int
     */
    public function getShowsProcessed(): int;

    /**
     * Set number of shows processed
     *
     * @param int $count
     * @return ImportResultInterface
     */
    public function setShowsProcessed(int $count): ImportResultInterface;

    /**
     * Increment shows processed count
     *
     * @return ImportResultInterface
     */
    public function incrementShowsProcessed(): ImportResultInterface;

    /**
     * Get number of tracks created
     *
     * @return int
     */
    public function getTracksCreated(): int;

    /**
     * Set number of tracks created
     *
     * @param int $count
     * @return ImportResultInterface
     */
    public function setTracksCreated(int $count): ImportResultInterface;

    /**
     * Increment tracks created count
     *
     * @return ImportResultInterface
     */
    public function incrementTracksCreated(): ImportResultInterface;

    /**
     * Get number of tracks updated
     *
     * @return int
     */
    public function getTracksUpdated(): int;

    /**
     * Set number of tracks updated
     *
     * @param int $count
     * @return ImportResultInterface
     */
    public function setTracksUpdated(int $count): ImportResultInterface;

    /**
     * Increment tracks updated count
     *
     * @return ImportResultInterface
     */
    public function incrementTracksUpdated(): ImportResultInterface;

    /**
     * Get number of tracks skipped
     *
     * @return int
     */
    public function getTracksSkipped(): int;

    /**
     * Set number of tracks skipped
     *
     * @param int $count
     * @return ImportResultInterface
     */
    public function setTracksSkipped(int $count): ImportResultInterface;

    /**
     * Increment tracks skipped count
     *
     * @return ImportResultInterface
     */
    public function incrementTracksSkipped(): ImportResultInterface;

    /**
     * Get errors array
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Add an error
     *
     * @param string $error
     * @param string|null $context
     * @return ImportResultInterface
     */
    public function addError(string $error, ?string $context = null): ImportResultInterface;

    /**
     * Check if there are any errors
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int;

    /**
     * Get start time
     *
     * @return \DateTimeInterface|null
     */
    public function getStartTime(): ?\DateTimeInterface;

    /**
     * Set start time
     *
     * @param \DateTimeInterface $startTime
     * @return ImportResultInterface
     */
    public function setStartTime(\DateTimeInterface $startTime): ImportResultInterface;

    /**
     * Get end time
     *
     * @return \DateTimeInterface|null
     */
    public function getEndTime(): ?\DateTimeInterface;

    /**
     * Set end time
     *
     * @param \DateTimeInterface $endTime
     * @return ImportResultInterface
     */
    public function setEndTime(\DateTimeInterface $endTime): ImportResultInterface;

    /**
     * Get duration in seconds
     *
     * @return int|null
     */
    public function getDurationSeconds(): ?int;

    /**
     * Get artist name
     *
     * @return string|null
     */
    public function getArtistName(): ?string;

    /**
     * Set artist name
     *
     * @param string $artistName
     * @return ImportResultInterface
     */
    public function setArtistName(string $artistName): ImportResultInterface;

    /**
     * Get collection ID
     *
     * @return string|null
     */
    public function getCollectionId(): ?string;

    /**
     * Set collection ID
     *
     * @param string $collectionId
     * @return ImportResultInterface
     */
    public function setCollectionId(string $collectionId): ImportResultInterface;

    /**
     * Get total tracks processed (created + updated + skipped)
     *
     * @return int
     */
    public function getTotalTracksProcessed(): int;

    /**
     * Get summary as array
     *
     * @return array
     */
    public function toArray(): array;
}

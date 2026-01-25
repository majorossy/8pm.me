<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Import Job Interface
 *
 * Represents the status and details of an import job
 */
interface ImportJobInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get job ID
     *
     * @return string
     */
    public function getJobId(): string;

    /**
     * Set job ID
     *
     * @param string $jobId
     * @return $this
     */
    public function setJobId(string $jobId): self;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get artist name
     *
     * @return string|null
     */
    public function getArtistName(): ?string;

    /**
     * Set artist name
     *
     * @param string|null $artistName
     * @return $this
     */
    public function setArtistName(?string $artistName): self;

    /**
     * Get collection ID
     *
     * @return string|null
     */
    public function getCollectionId(): ?string;

    /**
     * Set collection ID
     *
     * @param string|null $collectionId
     * @return $this
     */
    public function setCollectionId(?string $collectionId): self;

    /**
     * Get total shows to process
     *
     * @return int
     */
    public function getTotalShows(): int;

    /**
     * Set total shows
     *
     * @param int $totalShows
     * @return $this
     */
    public function setTotalShows(int $totalShows): self;

    /**
     * Get processed shows count
     *
     * @return int
     */
    public function getProcessedShows(): int;

    /**
     * Set processed shows
     *
     * @param int $processedShows
     * @return $this
     */
    public function setProcessedShows(int $processedShows): self;

    /**
     * Get created tracks count
     *
     * @return int
     */
    public function getTracksCreated(): int;

    /**
     * Set tracks created
     *
     * @param int $tracksCreated
     * @return $this
     */
    public function setTracksCreated(int $tracksCreated): self;

    /**
     * Get updated tracks count
     *
     * @return int
     */
    public function getTracksUpdated(): int;

    /**
     * Set tracks updated
     *
     * @param int $tracksUpdated
     * @return $this
     */
    public function setTracksUpdated(int $tracksUpdated): self;

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int;

    /**
     * Set error count
     *
     * @param int $errorCount
     * @return $this
     */
    public function setErrorCount(int $errorCount): self;

    /**
     * Get error messages
     *
     * @return string[]
     */
    public function getErrors(): array;

    /**
     * Set errors
     *
     * @param string[] $errors
     * @return $this
     */
    public function setErrors(array $errors): self;

    /**
     * Get current message/status description
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Set message
     *
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;

    /**
     * Get start time
     *
     * @return string|null
     */
    public function getStartedAt(): ?string;

    /**
     * Set start time
     *
     * @param string|null $startedAt
     * @return $this
     */
    public function setStartedAt(?string $startedAt): self;

    /**
     * Get completion time
     *
     * @return string|null
     */
    public function getCompletedAt(): ?string;

    /**
     * Set completion time
     *
     * @param string|null $completedAt
     * @return $this
     */
    public function setCompletedAt(?string $completedAt): self;

    /**
     * Get progress percentage (0-100)
     *
     * @return float
     */
    public function getProgress(): float;
}

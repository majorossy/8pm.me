<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;

/**
 * Import Job DTO Implementation
 */
class ImportJob implements ImportJobInterface
{
    private string $jobId = '';
    private string $status = self::STATUS_PENDING;
    private ?string $artistName = null;
    private ?string $collectionId = null;
    private int $totalShows = 0;
    private int $processedShows = 0;
    private int $tracksCreated = 0;
    private int $tracksUpdated = 0;
    private int $errorCount = 0;
    private array $errors = [];
    private ?string $message = null;
    private ?string $startedAt = null;
    private ?string $completedAt = null;
    private float $progress = 0.0;
    private array $additionalData = [];

    /**
     * @inheritDoc
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }

    /**
     * @inheritDoc
     */
    public function setJobId(string $jobId): ImportJobInterface
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): ImportJobInterface
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getArtistName(): ?string
    {
        return $this->artistName;
    }

    /**
     * @inheritDoc
     */
    public function setArtistName(?string $artistName): ImportJobInterface
    {
        $this->artistName = $artistName;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCollectionId(): ?string
    {
        return $this->collectionId;
    }

    /**
     * @inheritDoc
     */
    public function setCollectionId(?string $collectionId): ImportJobInterface
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalShows(): int
    {
        return $this->totalShows;
    }

    /**
     * @inheritDoc
     */
    public function setTotalShows(int $totalShows): ImportJobInterface
    {
        $this->totalShows = $totalShows;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProcessedShows(): int
    {
        return $this->processedShows;
    }

    /**
     * @inheritDoc
     */
    public function setProcessedShows(int $processedShows): ImportJobInterface
    {
        $this->processedShows = $processedShows;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTracksCreated(): int
    {
        return $this->tracksCreated;
    }

    /**
     * @inheritDoc
     */
    public function setTracksCreated(int $tracksCreated): ImportJobInterface
    {
        $this->tracksCreated = $tracksCreated;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTracksUpdated(): int
    {
        return $this->tracksUpdated;
    }

    /**
     * @inheritDoc
     */
    public function setTracksUpdated(int $tracksUpdated): ImportJobInterface
    {
        $this->tracksUpdated = $tracksUpdated;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @inheritDoc
     */
    public function setErrorCount(int $errorCount): ImportJobInterface
    {
        $this->errorCount = $errorCount;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function setErrors(array $errors): ImportJobInterface
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(?string $message): ImportJobInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    /**
     * @inheritDoc
     */
    public function setStartedAt(?string $startedAt): ImportJobInterface
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * @inheritDoc
     */
    public function setCompletedAt(?string $completedAt): ImportJobInterface
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProgress(): float
    {
        if ($this->progress > 0) {
            return $this->progress;
        }

        if ($this->totalShows === 0) {
            return $this->status === self::STATUS_COMPLETED ? 100.0 : 0.0;
        }

        return round(($this->processedShows / $this->totalShows) * 100, 2);
    }

    /**
     * Set progress percentage
     *
     * @param float $progress
     * @return ImportJobInterface
     */
    public function setProgress(float $progress): ImportJobInterface
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * Get additional data by key
     *
     * @param string|null $key
     * @return mixed
     */
    public function getData(?string $key = null)
    {
        if ($key === null) {
            return $this->additionalData;
        }

        return $this->additionalData[$key] ?? null;
    }

    /**
     * Set additional data
     *
     * @param string $key
     * @param mixed $value
     * @return ImportJobInterface
     */
    public function setData(string $key, $value): ImportJobInterface
    {
        $this->additionalData[$key] = $value;
        return $this;
    }
}

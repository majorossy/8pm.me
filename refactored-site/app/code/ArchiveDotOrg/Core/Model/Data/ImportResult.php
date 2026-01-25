<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\ImportResultInterface;
use DateTimeInterface;

/**
 * Import Result Data Transfer Object Implementation
 */
class ImportResult implements ImportResultInterface
{
    private int $showsProcessed = 0;
    private int $tracksCreated = 0;
    private int $tracksUpdated = 0;
    private int $tracksSkipped = 0;
    private array $errors = [];
    private ?DateTimeInterface $startTime = null;
    private ?DateTimeInterface $endTime = null;
    private ?string $artistName = null;
    private ?string $collectionId = null;

    /**
     * @inheritDoc
     */
    public function getShowsProcessed(): int
    {
        return $this->showsProcessed;
    }

    /**
     * @inheritDoc
     */
    public function setShowsProcessed(int $count): ImportResultInterface
    {
        $this->showsProcessed = $count;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function incrementShowsProcessed(): ImportResultInterface
    {
        $this->showsProcessed++;
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
    public function setTracksCreated(int $count): ImportResultInterface
    {
        $this->tracksCreated = $count;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function incrementTracksCreated(): ImportResultInterface
    {
        $this->tracksCreated++;
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
    public function setTracksUpdated(int $count): ImportResultInterface
    {
        $this->tracksUpdated = $count;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function incrementTracksUpdated(): ImportResultInterface
    {
        $this->tracksUpdated++;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTracksSkipped(): int
    {
        return $this->tracksSkipped;
    }

    /**
     * @inheritDoc
     */
    public function setTracksSkipped(int $count): ImportResultInterface
    {
        $this->tracksSkipped = $count;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function incrementTracksSkipped(): ImportResultInterface
    {
        $this->tracksSkipped++;
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
    public function addError(string $error, ?string $context = null): ImportResultInterface
    {
        $this->errors[] = [
            'message' => $error,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @inheritDoc
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function getStartTime(): ?DateTimeInterface
    {
        return $this->startTime;
    }

    /**
     * @inheritDoc
     */
    public function setStartTime(DateTimeInterface $startTime): ImportResultInterface
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndTime(): ?DateTimeInterface
    {
        return $this->endTime;
    }

    /**
     * @inheritDoc
     */
    public function setEndTime(DateTimeInterface $endTime): ImportResultInterface
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDurationSeconds(): ?int
    {
        if ($this->startTime === null || $this->endTime === null) {
            return null;
        }

        return $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
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
    public function setArtistName(string $artistName): ImportResultInterface
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
    public function setCollectionId(string $collectionId): ImportResultInterface
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalTracksProcessed(): int
    {
        return $this->tracksCreated + $this->tracksUpdated + $this->tracksSkipped;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'artist_name' => $this->artistName,
            'collection_id' => $this->collectionId,
            'shows_processed' => $this->showsProcessed,
            'tracks_created' => $this->tracksCreated,
            'tracks_updated' => $this->tracksUpdated,
            'tracks_skipped' => $this->tracksSkipped,
            'total_tracks' => $this->getTotalTracksProcessed(),
            'error_count' => $this->getErrorCount(),
            'errors' => $this->errors,
            'start_time' => $this->startTime?->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime?->format('Y-m-d H:i:s'),
            'duration_seconds' => $this->getDurationSeconds()
        ];
    }
}

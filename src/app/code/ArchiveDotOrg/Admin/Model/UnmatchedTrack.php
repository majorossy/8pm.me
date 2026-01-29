<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Unmatched Track Model
 *
 * Tracks that couldn't be matched to categories during import.
 */
class UnmatchedTrack extends AbstractModel
{
    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_MAPPED = 'mapped';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_NEW_TRACK = 'new_track';

    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\UnmatchedTrack::class);
    }

    /**
     * Get unmatched ID
     */
    public function getUnmatchedId(): ?int
    {
        return $this->getData('unmatched_id') ? (int) $this->getData('unmatched_id') : null;
    }

    /**
     * Get artist ID
     */
    public function getArtistId(): ?int
    {
        return $this->getData('artist_id') ? (int) $this->getData('artist_id') : null;
    }

    /**
     * Set artist ID
     */
    public function setArtistId(?int $artistId): self
    {
        return $this->setData('artist_id', $artistId);
    }

    /**
     * Get track title
     */
    public function getTrackTitle(): ?string
    {
        return $this->getData('track_title');
    }

    /**
     * Set track title
     */
    public function setTrackTitle(string $title): self
    {
        return $this->setData('track_title', $title);
    }

    /**
     * Get suggested match
     */
    public function getSuggestedMatch(): ?string
    {
        return $this->getData('suggested_match');
    }

    /**
     * Set suggested match
     */
    public function setSuggestedMatch(?string $match): self
    {
        return $this->setData('suggested_match', $match);
    }

    /**
     * Get match confidence
     */
    public function getMatchConfidence(): ?float
    {
        return $this->getData('match_confidence') ? (float) $this->getData('match_confidence') : null;
    }

    /**
     * Set match confidence
     */
    public function setMatchConfidence(?float $confidence): self
    {
        return $this->setData('match_confidence', $confidence);
    }

    /**
     * Get status
     */
    public function getStatus(): string
    {
        return $this->getData('status') ?? self::STATUS_PENDING;
    }

    /**
     * Set status
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * Get occurrence count
     */
    public function getOccurrenceCount(): int
    {
        return (int) ($this->getData('occurrence_count') ?? 1);
    }

    /**
     * Set occurrence count
     */
    public function setOccurrenceCount(int $count): self
    {
        return $this->setData('occurrence_count', $count);
    }
}

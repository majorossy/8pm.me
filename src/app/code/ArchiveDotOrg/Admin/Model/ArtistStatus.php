<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Artist Status Model
 *
 * Pre-aggregated statistics per artist for dashboard performance.
 */
class ArtistStatus extends AbstractModel
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\ArtistStatus::class);
    }

    /**
     * Get status ID
     */
    public function getStatusId(): ?int
    {
        return $this->getData('status_id') ? (int) $this->getData('status_id') : null;
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
     * Get downloaded shows count
     */
    public function getDownloadedShows(): int
    {
        return (int) ($this->getData('downloaded_shows') ?? 0);
    }

    /**
     * Set downloaded shows count
     */
    public function setDownloadedShows(int $count): self
    {
        return $this->setData('downloaded_shows', $count);
    }

    /**
     * Get imported tracks count
     */
    public function getImportedTracks(): int
    {
        return (int) ($this->getData('imported_tracks') ?? 0);
    }

    /**
     * Set imported tracks count
     */
    public function setImportedTracks(int $count): self
    {
        return $this->setData('imported_tracks', $count);
    }

    /**
     * Get matched tracks count
     */
    public function getMatchedTracks(): int
    {
        return (int) ($this->getData('matched_tracks') ?? 0);
    }

    /**
     * Set matched tracks count
     */
    public function setMatchedTracks(int $count): self
    {
        return $this->setData('matched_tracks', $count);
    }

    /**
     * Get match rate percentage
     */
    public function getMatchRatePercent(): float
    {
        return (float) ($this->getData('match_rate_percent') ?? 0.0);
    }

    /**
     * Set match rate percentage
     */
    public function setMatchRatePercent(float $rate): self
    {
        return $this->setData('match_rate_percent', $rate);
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Daily Metrics Model
 *
 * Pre-aggregated time-series metrics for dashboard charts.
 */
class DailyMetrics extends AbstractModel
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\DailyMetrics::class);
    }

    /**
     * Get metric ID
     */
    public function getMetricId(): ?int
    {
        return $this->getData('metric_id') ? (int) $this->getData('metric_id') : null;
    }

    /**
     * Get metric date
     */
    public function getMetricDate(): ?string
    {
        return $this->getData('metric_date');
    }

    /**
     * Set metric date
     */
    public function setMetricDate(string $date): self
    {
        return $this->setData('metric_date', $date);
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
     * Get shows downloaded count
     */
    public function getShowsDownloaded(): int
    {
        return (int) ($this->getData('shows_downloaded') ?? 0);
    }

    /**
     * Set shows downloaded count
     */
    public function setShowsDownloaded(int $count): self
    {
        return $this->setData('shows_downloaded', $count);
    }

    /**
     * Get tracks imported count
     */
    public function getTracksImported(): int
    {
        return (int) ($this->getData('tracks_imported') ?? 0);
    }

    /**
     * Set tracks imported count
     */
    public function setTracksImported(int $count): self
    {
        return $this->setData('tracks_imported', $count);
    }

    /**
     * Get match rate percentage
     */
    public function getMatchRatePercent(): ?float
    {
        return $this->getData('match_rate_percent') ? (float) $this->getData('match_rate_percent') : null;
    }

    /**
     * Set match rate percentage
     */
    public function setMatchRatePercent(?float $rate): self
    {
        return $this->setData('match_rate_percent', $rate);
    }
}

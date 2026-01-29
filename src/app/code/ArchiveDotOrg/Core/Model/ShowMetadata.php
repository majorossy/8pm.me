<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Show Metadata Model
 *
 * Represents metadata stored in archivedotorg_show_metadata table
 * Stores large JSON blobs (reviews, workable_servers) outside of EAV
 */
class ShowMetadata extends AbstractModel
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(\ArchiveDotOrg\Core\Model\ResourceModel\ShowMetadata::class);
    }

    /**
     * Get metadata ID
     *
     * @return int|null
     */
    public function getMetadataId(): ?int
    {
        return $this->getData('metadata_id') ? (int) $this->getData('metadata_id') : null;
    }

    /**
     * Set metadata ID
     *
     * @param int $metadataId
     * @return $this
     */
    public function setMetadataId(int $metadataId): self
    {
        return $this->setData('metadata_id', $metadataId);
    }

    /**
     * Get show identifier
     *
     * @return string
     */
    public function getShowIdentifier(): string
    {
        return (string) $this->getData('show_identifier');
    }

    /**
     * Set show identifier
     *
     * @param string $showIdentifier
     * @return $this
     */
    public function setShowIdentifier(string $showIdentifier): self
    {
        return $this->setData('show_identifier', $showIdentifier);
    }

    /**
     * Get artist ID
     *
     * @return int|null
     */
    public function getArtistId(): ?int
    {
        return $this->getData('artist_id') ? (int) $this->getData('artist_id') : null;
    }

    /**
     * Set artist ID
     *
     * @param int $artistId
     * @return $this
     */
    public function setArtistId(int $artistId): self
    {
        return $this->setData('artist_id', $artistId);
    }

    /**
     * Get reviews JSON
     *
     * @return array
     */
    public function getReviews(): array
    {
        $reviewsJson = $this->getData('reviews_json');
        if (!$reviewsJson) {
            return [];
        }

        $reviews = json_decode($reviewsJson, true);
        return is_array($reviews) ? $reviews : [];
    }

    /**
     * Set reviews JSON
     *
     * @param array $reviews
     * @return $this
     */
    public function setReviews(array $reviews): self
    {
        return $this->setData('reviews_json', json_encode($reviews));
    }

    /**
     * Get workable servers
     *
     * @return array
     */
    public function getWorkableServers(): array
    {
        $serversJson = $this->getData('workable_servers');
        if (!$serversJson) {
            return [];
        }

        $servers = json_decode($serversJson, true);
        return is_array($servers) ? $servers : [];
    }

    /**
     * Set workable servers
     *
     * @param array $servers
     * @return $this
     */
    public function setWorkableServers(array $servers): self
    {
        return $this->setData('workable_servers', json_encode($servers));
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}

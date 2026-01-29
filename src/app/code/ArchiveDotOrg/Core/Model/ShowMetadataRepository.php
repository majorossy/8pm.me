<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Model\ShowMetadata;
use ArchiveDotOrg\Core\Model\ShowMetadataFactory;
use ArchiveDotOrg\Core\Model\ResourceModel\ShowMetadata as ShowMetadataResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Show Metadata Repository
 *
 * Provides service layer for managing show metadata (reviews, workable_servers)
 * stored in archivedotorg_show_metadata table
 */
class ShowMetadataRepository
{
    private ShowMetadataFactory $showMetadataFactory;
    private ShowMetadataResource $showMetadataResource;

    /**
     * @param ShowMetadataFactory $showMetadataFactory
     * @param ShowMetadataResource $showMetadataResource
     */
    public function __construct(
        ShowMetadataFactory $showMetadataFactory,
        ShowMetadataResource $showMetadataResource
    ) {
        $this->showMetadataFactory = $showMetadataFactory;
        $this->showMetadataResource = $showMetadataResource;
    }

    /**
     * Save show metadata
     *
     * @param string $showIdentifier Archive.org show identifier
     * @param int $artistId Artist ID
     * @param array $workableServers Workable servers array
     * @param array $reviews Reviews array
     * @return void
     * @throws CouldNotSaveException
     */
    public function save(
        string $showIdentifier,
        int $artistId,
        array $workableServers,
        array $reviews
    ): void {
        try {
            // Load existing or create new
            $metadata = $this->showMetadataFactory->create();
            $this->showMetadataResource->loadByShowIdentifier($metadata, $showIdentifier);

            // Set data
            $metadata->setShowIdentifier($showIdentifier);
            $metadata->setArtistId($artistId);
            $metadata->setWorkableServers($workableServers);
            $metadata->setReviews($reviews);

            // Save
            $this->showMetadataResource->save($metadata);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save show metadata: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Get show metadata by identifier
     *
     * @param string $showIdentifier
     * @return ShowMetadata
     * @throws NoSuchEntityException
     */
    public function getByIdentifier(string $showIdentifier): ShowMetadata
    {
        $metadata = $this->showMetadataFactory->create();
        $this->showMetadataResource->loadByShowIdentifier($metadata, $showIdentifier);

        if (!$metadata->getMetadataId()) {
            throw new NoSuchEntityException(
                __('Show metadata with identifier "%1" does not exist.', $showIdentifier)
            );
        }

        return $metadata;
    }

    /**
     * Get workable servers for a show
     *
     * @param string $showIdentifier
     * @return array
     */
    public function getWorkableServers(string $showIdentifier): array
    {
        try {
            $metadata = $this->getByIdentifier($showIdentifier);
            return $metadata->getWorkableServers();
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * Get reviews for a show
     *
     * @param string $showIdentifier
     * @return array
     */
    public function getReviews(string $showIdentifier): array
    {
        try {
            $metadata = $this->getByIdentifier($showIdentifier);
            return $metadata->getReviews();
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * Check if show metadata exists
     *
     * @param string $showIdentifier
     * @return bool
     */
    public function exists(string $showIdentifier): bool
    {
        try {
            $this->getByIdentifier($showIdentifier);
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Delete show metadata
     *
     * @param string $showIdentifier
     * @return void
     * @throws NoSuchEntityException
     */
    public function delete(string $showIdentifier): void
    {
        $metadata = $this->getByIdentifier($showIdentifier);

        try {
            $this->showMetadataResource->delete($metadata);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not delete show metadata: %1', $e->getMessage()),
                $e
            );
        }
    }
}

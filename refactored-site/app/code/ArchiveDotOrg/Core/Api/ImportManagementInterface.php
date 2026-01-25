<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\CollectionInfoInterface;
use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;

/**
 * Import Management Interface
 *
 * REST API for managing Archive.org imports
 */
interface ImportManagementInterface
{
    /**
     * Start a new import job
     *
     * @param string $artistName Artist display name
     * @param string $collectionId Archive.org collection ID
     * @param int|null $limit Maximum number of shows to import
     * @param int|null $offset Number of shows to skip
     * @param bool $dryRun If true, simulate import without making changes
     * @return \ArchiveDotOrg\Core\Api\Data\ImportJobInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function startImport(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null,
        bool $dryRun = false
    ): ImportJobInterface;

    /**
     * Get the status of an import job
     *
     * @param string $jobId The job identifier
     * @return \ArchiveDotOrg\Core\Api\Data\ImportJobInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getJobStatus(string $jobId): ImportJobInterface;

    /**
     * Delete a product by SKU
     *
     * @param string $sku Product SKU
     * @return bool True if deleted successfully
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteProduct(string $sku): bool;

    /**
     * Get list of configured collections
     *
     * @param bool $includeStats If true, include import statistics
     * @return \ArchiveDotOrg\Core\Api\Data\CollectionInfoInterface[]
     */
    public function listCollections(bool $includeStats = false): array;

    /**
     * Get collection info by ID
     *
     * @param string $collectionId Archive.org collection ID
     * @return \ArchiveDotOrg\Core\Api\Data\CollectionInfoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCollection(string $collectionId): CollectionInfoInterface;

    /**
     * Cancel a running import job
     *
     * @param string $jobId The job identifier
     * @return bool True if cancelled successfully
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelJob(string $jobId): bool;
}

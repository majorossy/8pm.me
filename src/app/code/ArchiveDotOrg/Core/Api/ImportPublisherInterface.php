<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;

/**
 * Interface for publishing import jobs to message queue
 */
interface ImportPublisherInterface
{
    /**
     * Publish an import job to the queue
     *
     * @param string $artistName
     * @param string $collectionId
     * @param int $limit
     * @param int $offset
     * @param bool $dryRun
     * @return ImportJobInterface The created job with ID
     */
    public function publish(
        string $artistName,
        string $collectionId,
        int $limit = 0,
        int $offset = 0,
        bool $dryRun = false
    ): ImportJobInterface;

    /**
     * Publish a status update message
     *
     * @param string $jobId
     * @param string $status
     * @param string|null $message
     * @return void
     */
    public function publishStatusUpdate(string $jobId, string $status, ?string $message = null): void;

    /**
     * Cancel a queued or running import job
     *
     * @param string $jobId
     * @return bool True if cancellation was successful
     */
    public function cancel(string $jobId): bool;
}

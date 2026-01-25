<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Progress Tracker Interface
 *
 * Tracks import progress for resume capability
 */
interface ProgressTrackerInterface
{
    /**
     * Start tracking a new import job
     *
     * @param string $jobId
     * @param string $artistName
     * @param string $collectionId
     * @param int $totalItems
     * @return void
     */
    public function startJob(
        string $jobId,
        string $artistName,
        string $collectionId,
        int $totalItems
    ): void;

    /**
     * Mark an identifier as processed
     *
     * @param string $jobId
     * @param string $identifier
     * @param bool $success
     * @param string|null $error
     * @return void
     */
    public function markProcessed(
        string $jobId,
        string $identifier,
        bool $success = true,
        ?string $error = null
    ): void;

    /**
     * Complete a job
     *
     * @param string $jobId
     * @return void
     */
    public function completeJob(string $jobId): void;

    /**
     * Get unprocessed identifiers from a job
     *
     * @param string $jobId
     * @return string[]
     */
    public function getUnprocessedIdentifiers(string $jobId): array;

    /**
     * Check if a job exists and is resumable
     *
     * @param string $artistName
     * @param string $collectionId
     * @return string|null Job ID if resumable, null otherwise
     */
    public function findResumableJob(string $artistName, string $collectionId): ?string;

    /**
     * Get job progress
     *
     * @param string $jobId
     * @return array{total: int, processed: int, successful: int, failed: int}
     */
    public function getProgress(string $jobId): array;

    /**
     * Generate a new job ID
     *
     * @return string
     */
    public function generateJobId(): string;

    /**
     * Clear old completed jobs
     *
     * @param int $olderThanDays
     * @return int Number of jobs cleared
     */
    public function clearOldJobs(int $olderThanDays = 7): int;
}

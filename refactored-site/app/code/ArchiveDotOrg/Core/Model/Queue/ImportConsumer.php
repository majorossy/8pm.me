<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Queue;

use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;
use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Consumer for async import jobs
 *
 * Processes import jobs from the message queue
 */
class ImportConsumer
{
    private ShowImporterInterface $showImporter;
    private JobStatusManager $jobStatusManager;
    private Logger $logger;

    /**
     * @param ShowImporterInterface $showImporter
     * @param JobStatusManager $jobStatusManager
     * @param Logger $logger
     */
    public function __construct(
        ShowImporterInterface $showImporter,
        JobStatusManager $jobStatusManager,
        Logger $logger
    ) {
        $this->showImporter = $showImporter;
        $this->jobStatusManager = $jobStatusManager;
        $this->logger = $logger;
    }

    /**
     * Process an import job from the queue
     *
     * @param ImportJobInterface $job
     * @return void
     */
    public function processJob(ImportJobInterface $job): void
    {
        $jobId = $job->getJobId();

        $this->logger->info('Starting async import job', [
            'job_id' => $jobId,
            'artist' => $job->getArtistName(),
            'collection' => $job->getCollectionId()
        ]);

        // Check if job was cancelled before we start
        if ($this->jobStatusManager->isJobCancelled($jobId)) {
            $this->logger->info('Import job was cancelled before processing', ['job_id' => $jobId]);
            return;
        }

        // Update status to running
        $job->setStatus('running');
        $job->setData('started_at', date('Y-m-d H:i:s'));
        $this->jobStatusManager->saveJob($job);

        try {
            $artistName = $job->getArtistName();
            $collectionId = $job->getCollectionId();
            $limit = (int) ($job->getData('limit') ?? 0);
            $offset = (int) ($job->getData('offset') ?? 0);
            $dryRun = (bool) ($job->getData('dry_run') ?? false);

            // Progress callback to update job status
            $progressCallback = function (int $total, int $current, string $message) use ($job, $jobId) {
                // Check for cancellation during processing
                if ($this->jobStatusManager->isJobCancelled($jobId)) {
                    throw new LocalizedException(__('Import job was cancelled.'));
                }

                $job->setTotalShows($total);
                $job->setProcessedShows($current);

                if ($total > 0) {
                    $job->setProgress(round(($current / $total) * 100, 2));
                }

                // Save progress periodically (every 5 shows)
                if ($current % 5 === 0 || $current === $total) {
                    $this->jobStatusManager->saveJob($job);
                }
            };

            // Run the import
            $result = $this->showImporter->importByCollection(
                $artistName,
                $collectionId,
                $limit ?: null,
                $offset,
                $progressCallback,
                $dryRun
            );

            // Update final status
            $job->setStatus('completed');
            $job->setTotalShows($result->getTotalShows());
            $job->setProcessedShows($result->getProcessedShows());
            $job->setTracksCreated($result->getTracksCreated());
            $job->setTracksUpdated($result->getTracksUpdated());
            $job->setErrorCount($result->getErrorCount());
            $job->setProgress(100.0);
            $job->setData('completed_at', date('Y-m-d H:i:s'));

            if ($result->getErrors()) {
                $job->setData('errors', $result->getErrors());
            }

            $this->jobStatusManager->saveJob($job);

            $this->logger->info('Async import job completed', [
                'job_id' => $jobId,
                'tracks_created' => $result->getTracksCreated(),
                'tracks_updated' => $result->getTracksUpdated(),
                'errors' => $result->getErrorCount()
            ]);

        } catch (LocalizedException $e) {
            // Handle cancellation gracefully
            if (strpos($e->getMessage(), 'cancelled') !== false) {
                $this->logger->info('Import job cancelled during processing', ['job_id' => $jobId]);
                return;
            }

            $this->handleJobFailure($job, $e);
        } catch (\Exception $e) {
            $this->handleJobFailure($job, $e);
        }
    }

    /**
     * Handle job failure
     *
     * @param ImportJobInterface $job
     * @param \Exception $exception
     * @return void
     */
    private function handleJobFailure(ImportJobInterface $job, \Exception $exception): void
    {
        $jobId = $job->getJobId();

        $job->setStatus('failed');
        $job->setData('error', $exception->getMessage());
        $job->setData('completed_at', date('Y-m-d H:i:s'));
        $this->jobStatusManager->saveJob($job);

        $this->logger->error('Async import job failed', [
            'job_id' => $jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

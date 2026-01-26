<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Queue;

use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;
use ArchiveDotOrg\Core\Api\Data\ImportJobInterfaceFactory;
use ArchiveDotOrg\Core\Api\ImportPublisherInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Publisher for async import jobs
 *
 * Publishes import requests to the message queue for background processing
 */
class ImportPublisher implements ImportPublisherInterface
{
    private const TOPIC_IMPORT_JOB = 'archivedotorg.import.job';
    private const TOPIC_IMPORT_STATUS = 'archivedotorg.import.status';

    private PublisherInterface $publisher;
    private ImportJobInterfaceFactory $importJobFactory;
    private JobStatusManager $jobStatusManager;
    private Json $json;
    private Logger $logger;

    /**
     * @param PublisherInterface $publisher
     * @param ImportJobInterfaceFactory $importJobFactory
     * @param JobStatusManager $jobStatusManager
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        ImportJobInterfaceFactory $importJobFactory,
        JobStatusManager $jobStatusManager,
        Json $json,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->importJobFactory = $importJobFactory;
        $this->jobStatusManager = $jobStatusManager;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function publish(
        string $artistName,
        string $collectionId,
        int $limit = 0,
        int $offset = 0,
        bool $dryRun = false
    ): ImportJobInterface {
        $jobId = $this->generateJobId($collectionId);

        /** @var ImportJobInterface $job */
        $job = $this->importJobFactory->create();
        $job->setJobId($jobId);
        $job->setStatus('queued');
        $job->setArtistName($artistName);
        $job->setCollectionId($collectionId);
        $job->setTotalShows($limit);
        $job->setProcessedShows(0);
        $job->setTracksCreated(0);
        $job->setTracksUpdated(0);
        $job->setErrorCount(0);
        $job->setProgress(0.0);

        // Store additional parameters in the job for the consumer
        $job->setData('limit', $limit);
        $job->setData('offset', $offset);
        $job->setData('dry_run', $dryRun);
        $job->setData('queued_at', date('Y-m-d H:i:s'));

        // Save initial job status
        $this->jobStatusManager->saveJob($job);

        // Publish to queue
        try {
            $this->publisher->publish(self::TOPIC_IMPORT_JOB, $job);

            $this->logger->info('Import job published to queue', [
                'job_id' => $jobId,
                'artist' => $artistName,
                'collection' => $collectionId,
                'limit' => $limit,
                'offset' => $offset,
                'dry_run' => $dryRun
            ]);
        } catch (\Exception $e) {
            $job->setStatus('failed');
            $job->setData('error', $e->getMessage());
            $this->jobStatusManager->saveJob($job);

            $this->logger->error('Failed to publish import job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }

        return $job;
    }

    /**
     * @inheritDoc
     */
    public function publishStatusUpdate(string $jobId, string $status, ?string $message = null): void
    {
        $statusData = $this->json->serialize([
            'job_id' => $jobId,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        try {
            $this->publisher->publish(self::TOPIC_IMPORT_STATUS, $statusData);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to publish status update', [
                'job_id' => $jobId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $jobId): bool
    {
        $job = $this->jobStatusManager->getJob($jobId);

        if ($job === null) {
            return false;
        }

        $currentStatus = $job->getStatus();

        // Can only cancel queued or running jobs
        if (!in_array($currentStatus, ['queued', 'running'])) {
            return false;
        }

        $job->setStatus('cancelled');
        $job->setData('cancelled_at', date('Y-m-d H:i:s'));
        $this->jobStatusManager->saveJob($job);

        $this->logger->info('Import job cancelled', ['job_id' => $jobId]);

        return true;
    }

    /**
     * Generate unique job ID
     *
     * @param string $collectionId
     * @return string
     */
    private function generateJobId(string $collectionId): string
    {
        return sprintf(
            'import_%s_%s_%s',
            date('Ymd_His'),
            substr($collectionId, 0, 20),
            substr(uniqid(), -6)
        );
    }
}

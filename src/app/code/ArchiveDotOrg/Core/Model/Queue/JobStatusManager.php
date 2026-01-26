<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Queue;

use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;
use ArchiveDotOrg\Core\Api\Data\ImportJobInterfaceFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Manages job status persistence for async imports
 *
 * Stores job status in JSON files under var/archivedotorg/jobs/
 */
class JobStatusManager
{
    private const JOBS_DIR = 'archivedotorg/jobs';

    private Filesystem $filesystem;
    private Json $json;
    private ImportJobInterfaceFactory $importJobFactory;
    private ?WriteInterface $varDirectory = null;

    /**
     * @param Filesystem $filesystem
     * @param Json $json
     * @param ImportJobInterfaceFactory $importJobFactory
     */
    public function __construct(
        Filesystem $filesystem,
        Json $json,
        ImportJobInterfaceFactory $importJobFactory
    ) {
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->importJobFactory = $importJobFactory;
    }

    /**
     * Save job status to file
     *
     * @param ImportJobInterface $job
     * @return void
     */
    public function saveJob(ImportJobInterface $job): void
    {
        $directory = $this->getVarDirectory();
        $filePath = $this->getJobFilePath($job->getJobId());

        $data = [
            'job_id' => $job->getJobId(),
            'status' => $job->getStatus(),
            'artist_name' => $job->getArtistName(),
            'collection_id' => $job->getCollectionId(),
            'total_shows' => $job->getTotalShows(),
            'processed_shows' => $job->getProcessedShows(),
            'tracks_created' => $job->getTracksCreated(),
            'tracks_updated' => $job->getTracksUpdated(),
            'error_count' => $job->getErrorCount(),
            'progress' => $job->getProgress(),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Include additional data if present
        if (method_exists($job, 'getData')) {
            $extraData = $job->getData();
            if (is_array($extraData)) {
                foreach (['limit', 'offset', 'dry_run', 'queued_at', 'started_at', 'completed_at', 'cancelled_at', 'error', 'errors'] as $key) {
                    if (isset($extraData[$key])) {
                        $data[$key] = $extraData[$key];
                    }
                }
            }
        }

        $directory->writeFile($filePath, $this->json->serialize($data));
    }

    /**
     * Get job by ID
     *
     * @param string $jobId
     * @return ImportJobInterface|null
     */
    public function getJob(string $jobId): ?ImportJobInterface
    {
        $directory = $this->getVarDirectory();
        $filePath = $this->getJobFilePath($jobId);

        if (!$directory->isExist($filePath)) {
            return null;
        }

        try {
            $content = $directory->readFile($filePath);
            $data = $this->json->unserialize($content);

            return $this->createJobFromData($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all jobs, optionally filtered by status
     *
     * @param string|null $status
     * @param int $limit
     * @return ImportJobInterface[]
     */
    public function getJobs(?string $status = null, int $limit = 100): array
    {
        $directory = $this->getVarDirectory();
        $jobsPath = self::JOBS_DIR;

        if (!$directory->isExist($jobsPath)) {
            return [];
        }

        $jobs = [];
        $files = $directory->read($jobsPath);

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) use ($directory, $jobsPath) {
            $statA = $directory->stat($a);
            $statB = $directory->stat($b);
            return ($statB['mtime'] ?? 0) - ($statA['mtime'] ?? 0);
        });

        foreach ($files as $file) {
            if (count($jobs) >= $limit) {
                break;
            }

            if (substr($file, -5) !== '.json') {
                continue;
            }

            try {
                $content = $directory->readFile($file);
                $data = $this->json->unserialize($content);

                if ($status !== null && ($data['status'] ?? '') !== $status) {
                    continue;
                }

                $jobs[] = $this->createJobFromData($data);
            } catch (\Exception $e) {
                // Skip invalid files
                continue;
            }
        }

        return $jobs;
    }

    /**
     * Delete job file
     *
     * @param string $jobId
     * @return bool
     */
    public function deleteJob(string $jobId): bool
    {
        $directory = $this->getVarDirectory();
        $filePath = $this->getJobFilePath($jobId);

        if (!$directory->isExist($filePath)) {
            return false;
        }

        try {
            $directory->delete($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clean up old completed/failed jobs
     *
     * @param int $olderThanDays
     * @return int Number of jobs cleaned up
     */
    public function cleanupOldJobs(int $olderThanDays = 7): int
    {
        $directory = $this->getVarDirectory();
        $jobsPath = self::JOBS_DIR;

        if (!$directory->isExist($jobsPath)) {
            return 0;
        }

        $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);
        $cleaned = 0;

        foreach ($directory->read($jobsPath) as $file) {
            if (substr($file, -5) !== '.json') {
                continue;
            }

            try {
                $stat = $directory->stat($file);
                if (($stat['mtime'] ?? time()) < $cutoffTime) {
                    $content = $directory->readFile($file);
                    $data = $this->json->unserialize($content);

                    // Only clean up completed, failed, or cancelled jobs
                    if (in_array($data['status'] ?? '', ['completed', 'failed', 'cancelled'])) {
                        $directory->delete($file);
                        $cleaned++;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $cleaned;
    }

    /**
     * Check if a job is cancelled
     *
     * @param string $jobId
     * @return bool
     */
    public function isJobCancelled(string $jobId): bool
    {
        $job = $this->getJob($jobId);
        return $job !== null && $job->getStatus() === 'cancelled';
    }

    /**
     * Get var directory writer
     *
     * @return WriteInterface
     */
    private function getVarDirectory(): WriteInterface
    {
        if ($this->varDirectory === null) {
            $this->varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

            // Ensure jobs directory exists
            if (!$this->varDirectory->isExist(self::JOBS_DIR)) {
                $this->varDirectory->create(self::JOBS_DIR);
            }
        }

        return $this->varDirectory;
    }

    /**
     * Get file path for job
     *
     * @param string $jobId
     * @return string
     */
    private function getJobFilePath(string $jobId): string
    {
        // Sanitize job ID for filename
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $jobId);
        return self::JOBS_DIR . '/' . $safeId . '.json';
    }

    /**
     * Create ImportJob from array data
     *
     * @param array $data
     * @return ImportJobInterface
     */
    private function createJobFromData(array $data): ImportJobInterface
    {
        /** @var ImportJobInterface $job */
        $job = $this->importJobFactory->create();

        $job->setJobId($data['job_id'] ?? '');
        $job->setStatus($data['status'] ?? 'unknown');
        $job->setArtistName($data['artist_name'] ?? '');
        $job->setCollectionId($data['collection_id'] ?? '');
        $job->setTotalShows((int) ($data['total_shows'] ?? 0));
        $job->setProcessedShows((int) ($data['processed_shows'] ?? 0));
        $job->setTracksCreated((int) ($data['tracks_created'] ?? 0));
        $job->setTracksUpdated((int) ($data['tracks_updated'] ?? 0));
        $job->setErrorCount((int) ($data['error_count'] ?? 0));
        $job->setProgress((float) ($data['progress'] ?? 0.0));

        // Set additional data
        if (method_exists($job, 'setData')) {
            foreach (['limit', 'offset', 'dry_run', 'queued_at', 'started_at', 'completed_at', 'cancelled_at', 'error', 'errors'] as $key) {
                if (isset($data[$key])) {
                    $job->setData($key, $data[$key]);
                }
            }
        }

        return $job;
    }
}

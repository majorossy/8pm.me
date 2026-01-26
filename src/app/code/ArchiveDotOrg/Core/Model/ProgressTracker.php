<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ProgressTrackerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Progress Tracker Implementation
 *
 * Stores progress in JSON files for resume capability.
 * Files are stored in var/archivedotorg/progress/
 */
class ProgressTracker implements ProgressTrackerInterface
{
    private const PROGRESS_DIR = 'archivedotorg/progress';

    private Filesystem $filesystem;
    private Json $jsonSerializer;
    private ?string $varPath = null;

    /**
     * @param Filesystem $filesystem
     * @param Json $jsonSerializer
     */
    public function __construct(
        Filesystem $filesystem,
        Json $jsonSerializer
    ) {
        $this->filesystem = $filesystem;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    public function startJob(
        string $jobId,
        string $artistName,
        string $collectionId,
        int $totalItems
    ): void {
        $data = [
            'job_id' => $jobId,
            'artist_name' => $artistName,
            'collection_id' => $collectionId,
            'total_items' => $totalItems,
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'status' => 'running',
            'processed' => [],
            'errors' => []
        ];

        $this->saveJobData($jobId, $data);
    }

    /**
     * @inheritDoc
     */
    public function markProcessed(
        string $jobId,
        string $identifier,
        bool $success = true,
        ?string $error = null
    ): void {
        $data = $this->loadJobData($jobId);

        if ($data === null) {
            return;
        }

        $data['processed'][$identifier] = [
            'success' => $success,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        if (!$success && $error !== null) {
            $data['errors'][$identifier] = $error;
        }

        $this->saveJobData($jobId, $data);
    }

    /**
     * @inheritDoc
     */
    public function completeJob(string $jobId): void
    {
        $data = $this->loadJobData($jobId);

        if ($data === null) {
            return;
        }

        $data['status'] = 'completed';
        $data['completed_at'] = date('Y-m-d H:i:s');

        $this->saveJobData($jobId, $data);
    }

    /**
     * @inheritDoc
     */
    public function getUnprocessedIdentifiers(string $jobId): array
    {
        $data = $this->loadJobData($jobId);

        if ($data === null) {
            return [];
        }

        // This would need the original identifier list to be stored
        // For now, return empty as we'd need to refetch from API
        return [];
    }

    /**
     * @inheritDoc
     */
    public function findResumableJob(string $artistName, string $collectionId): ?string
    {
        $directory = $this->getProgressDirectory();

        if (!is_dir($directory)) {
            return null;
        }

        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $jobId = pathinfo($file, PATHINFO_FILENAME);
            $data = $this->loadJobData($jobId);

            if ($data === null) {
                continue;
            }

            if (
                $data['status'] === 'running' &&
                $data['artist_name'] === $artistName &&
                $data['collection_id'] === $collectionId
            ) {
                return $jobId;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getProgress(string $jobId): array
    {
        $data = $this->loadJobData($jobId);

        if ($data === null) {
            return [
                'total' => 0,
                'processed' => 0,
                'successful' => 0,
                'failed' => 0
            ];
        }

        $processed = $data['processed'] ?? [];
        $successful = 0;
        $failed = 0;

        foreach ($processed as $item) {
            if ($item['success'] ?? false) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $data['total_items'] ?? 0,
            'processed' => count($processed),
            'successful' => $successful,
            'failed' => $failed
        ];
    }

    /**
     * @inheritDoc
     */
    public function generateJobId(): string
    {
        return uniqid('import_', true);
    }

    /**
     * @inheritDoc
     */
    public function clearOldJobs(int $olderThanDays = 7): int
    {
        $directory = $this->getProgressDirectory();

        if (!is_dir($directory)) {
            return 0;
        }

        $cutoff = time() - ($olderThanDays * 86400);
        $cleared = 0;
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . '/' . $file;

            if (filemtime($filePath) < $cutoff) {
                if (unlink($filePath)) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }

    /**
     * Get the progress directory path
     *
     * @return string
     */
    private function getProgressDirectory(): string
    {
        if ($this->varPath === null) {
            $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $this->varPath = $varDir->getAbsolutePath(self::PROGRESS_DIR);

            if (!is_dir($this->varPath)) {
                $varDir->create(self::PROGRESS_DIR);
            }
        }

        return $this->varPath;
    }

    /**
     * Get the file path for a job
     *
     * @param string $jobId
     * @return string
     */
    private function getJobFilePath(string $jobId): string
    {
        return $this->getProgressDirectory() . '/' . $jobId . '.json';
    }

    /**
     * Load job data from file
     *
     * @param string $jobId
     * @return array|null
     */
    private function loadJobData(string $jobId): ?array
    {
        $filePath = $this->getJobFilePath($jobId);

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        try {
            return $this->jsonSerializer->unserialize($content);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save job data to file
     *
     * @param string $jobId
     * @param array $data
     * @return void
     */
    private function saveJobData(string $jobId, array $data): void
    {
        $filePath = $this->getJobFilePath($jobId);
        $content = $this->jsonSerializer->serialize($data);
        file_put_contents($filePath, $content);
    }
}

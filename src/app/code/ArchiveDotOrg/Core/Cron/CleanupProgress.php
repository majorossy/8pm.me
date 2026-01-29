<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Api\ProgressTrackerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Cleanup Progress Cron Job
 *
 * Cleans up old progress tracking files
 */
class CleanupProgress
{
    private const DAYS_TO_KEEP = 7;

    private ProgressTrackerInterface $progressTracker;
    private Filesystem $filesystem;
    private Config $config;
    private Logger $logger;

    /**
     * @param ProgressTrackerInterface $progressTracker
     * @param Filesystem $filesystem
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        ProgressTrackerInterface $progressTracker,
        Filesystem $filesystem,
        Config $config,
        Logger $logger
    ) {
        $this->progressTracker = $progressTracker;
        $this->filesystem = $filesystem;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $this->logger->info('CleanupProgress cron: Starting cleanup');

        // Clean old progress files
        $cleared = $this->progressTracker->clearOldJobs(self::DAYS_TO_KEEP);

        // Clean orphaned temp files from atomic writes (Fix #48)
        $tempFilesCleared = $this->cleanupTempFiles();

        $this->logger->info('CleanupProgress cron: Completed', [
            'progress_files_cleared' => $cleared,
            'temp_files_cleared' => $tempFilesCleared
        ]);
    }

    /**
     * Clean up orphaned temporary files from atomic writes
     *
     * Removes *.tmp.* files older than 1 hour that were left behind
     * by crashed processes during atomic write operations.
     *
     * @return int Number of temp files cleaned
     */
    private function cleanupTempFiles(): int
    {
        $cleaned = 0;
        $cutoff = time() - 3600; // 1 hour old
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $directories = [
            'archivedotorg/progress',
            'archivedotorg/locks',
            'archivedotorg/jobs'
        ];

        foreach ($directories as $dir) {
            if (!$varDir->isExist($dir)) {
                continue;
            }

            try {
                foreach ($varDir->read($dir) as $file) {
                    // Match *.tmp.* pattern (e.g., file.json.tmp.12345)
                    if (strpos($file, '.tmp.') !== false) {
                        try {
                            $stat = $varDir->stat($dir . '/' . $file);
                            $mtime = $stat['mtime'] ?? time();

                            if ($mtime < $cutoff) {
                                $varDir->delete($dir . '/' . $file);
                                $cleaned++;

                                $this->logger->debug('Cleaned orphaned temp file', [
                                    'file' => $file,
                                    'age_hours' => round((time() - $mtime) / 3600, 1)
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Skip files we can't process
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to clean temp files in directory', [
                    'directory' => $dir,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $cleaned;
    }
}

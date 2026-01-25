<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Api\ProgressTrackerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;

/**
 * Cleanup Progress Cron Job
 *
 * Cleans up old progress tracking files
 */
class CleanupProgress
{
    private const DAYS_TO_KEEP = 7;

    private ProgressTrackerInterface $progressTracker;
    private Config $config;
    private Logger $logger;

    /**
     * @param ProgressTrackerInterface $progressTracker
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        ProgressTrackerInterface $progressTracker,
        Config $config,
        Logger $logger
    ) {
        $this->progressTracker = $progressTracker;
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

        $cleared = $this->progressTracker->clearOldJobs(self::DAYS_TO_KEEP);

        $this->logger->info('CleanupProgress cron: Completed', [
            'files_cleared' => $cleared
        ]);
    }
}

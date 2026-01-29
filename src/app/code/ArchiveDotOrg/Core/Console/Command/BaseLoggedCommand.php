<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ArchiveDotOrg\Admin\Model\Redis\ProgressTracker;

/**
 * Base command with automatic correlation ID tracking and database logging
 *
 * Provides:
 * - Auto-generated correlation IDs for tracking command execution
 * - Start/end logging to archivedotorg_import_run table
 * - Exception handling with failure logging
 * - Redis progress tracking (for Phase 5 dashboard integration)
 *
 * Subclasses must implement doExecute() instead of execute()
 */
abstract class BaseLoggedCommand extends Command
{
    protected ResourceConnection $resourceConnection;
    protected LoggerInterface $logger;
    protected ?ProgressTracker $progressTracker = null;

    /**
     * Current artist being processed (set by subclass for progress tracking)
     */
    protected ?string $currentArtist = null;

    /**
     * Generate a UUID v4-like correlation ID
     */
    protected function generateCorrelationId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Execute command with automatic correlation ID and logging
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $correlationId = $this->generateCorrelationId();

        try {
            $this->logStart($correlationId, $input);
            $result = $this->doExecute($input, $output, $correlationId);
            $this->logEnd($correlationId, 'completed');
            $this->completeRedisProgress();
            return $result;
        } catch (\Exception $e) {
            $this->logEnd($correlationId, 'failed', $e->getMessage());
            $this->failRedisProgress($e->getMessage());
            throw $e;
        }
    }

    /**
     * Subclasses implement this instead of execute()
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $correlationId Unique ID for tracking this command execution
     * @return int Command::SUCCESS or Command::FAILURE
     */
    abstract protected function doExecute(
        InputInterface $input,
        OutputInterface $output,
        string $correlationId
    ): int;

    /**
     * Log command start to database
     */
    protected function logStart(string $correlationId, InputInterface $input): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

            // Check if table exists (Phase 0 should create it)
            if (!$connection->isTableExists($table)) {
                $this->logger->warning(
                    'Table archivedotorg_import_run does not exist. Skipping database logging.',
                    ['correlation_id' => $correlationId]
                );
                return;
            }

            $data = [
                'correlation_id' => $correlationId,
                'command' => $this->getName(),
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
                'command_args' => json_encode($input->getOptions()),
            ];

            // Artist ID will be null for now - will be populated by subclass if needed
            $connection->insert($table, $data);

        } catch (\Exception $e) {
            // Don't fail the command if logging fails
            $this->logger->error('Failed to log command start', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log command completion to database
     */
    protected function logEnd(string $correlationId, string $status, ?string $errorMessage = null): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

            if (!$connection->isTableExists($table)) {
                return;
            }

            $data = [
                'status' => $status,
                'completed_at' => date('Y-m-d H:i:s'),
            ];

            if ($errorMessage !== null) {
                $data['error_message'] = $errorMessage;
            }

            $connection->update(
                $table,
                $data,
                ['correlation_id = ?' => $correlationId]
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to log command end', [
                'correlation_id' => $correlationId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update progress counters in database
     *
     * Call this from doExecute() to track progress
     */
    protected function updateProgress(
        string $correlationId,
        int $showsProcessed = 0,
        int $tracksProcessed = 0
    ): void {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

            if (!$connection->isTableExists($table)) {
                return;
            }

            $data = [];
            if ($showsProcessed > 0) {
                $data['shows_processed'] = $showsProcessed;
            }
            if ($tracksProcessed > 0) {
                $data['tracks_processed'] = $tracksProcessed;
            }

            if (!empty($data)) {
                $connection->update(
                    $table,
                    $data,
                    ['correlation_id = ?' => $correlationId]
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to update progress', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Set ProgressTracker instance (injected by DI in subclasses)
     */
    public function setProgressTracker(ProgressTracker $progressTracker): void
    {
        $this->progressTracker = $progressTracker;
    }

    /**
     * Set current artist for Redis progress tracking
     *
     * Call this from doExecute() at the start of processing
     */
    protected function setCurrentArtist(string $artist): void
    {
        $this->currentArtist = $artist;
    }

    /**
     * Update Redis progress for real-time dashboard updates
     *
     * @param int $current Current item number (e.g., show 150)
     * @param int $total Total items to process (e.g., 523 shows)
     * @param int $processed Number of items successfully processed (e.g., 145 tracks)
     * @param string $status Status: running, completed, failed
     */
    protected function updateRedisProgress(
        string $correlationId,
        int $current = 0,
        int $total = 0,
        int $processed = 0,
        string $status = 'running'
    ): void {
        if (!$this->progressTracker || !$this->currentArtist) {
            return;
        }

        $this->progressTracker->updateProgress(
            $this->currentArtist,
            $correlationId,
            $current,
            $total,
            $processed,
            $status
        );
    }

    /**
     * Mark import as completed in Redis
     */
    protected function completeRedisProgress(): void
    {
        if (!$this->progressTracker || !$this->currentArtist) {
            return;
        }

        $this->progressTracker->complete($this->currentArtist);
    }

    /**
     * Mark import as failed in Redis
     */
    protected function failRedisProgress(string $errorMessage): void
    {
        if (!$this->progressTracker || !$this->currentArtist) {
            return;
        }

        $this->progressTracker->fail($this->currentArtist, $errorMessage);
    }

    /**
     * Clear Redis progress keys
     */
    protected function clearRedisProgress(): void
    {
        if (!$this->progressTracker || !$this->currentArtist) {
            return;
        }

        $this->progressTracker->clear($this->currentArtist);
    }
}

<?php

declare(strict_types=1);
declare(ticks=1);  // Required for signal handling

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
 * - Graceful shutdown on SIGTERM/SIGINT signals
 *
 * Subclasses must implement doExecute() instead of execute()
 */
abstract class BaseLoggedCommand extends Command
{
    protected ResourceConnection $resourceConnection;
    protected LoggerInterface $logger;
    protected ?ProgressTracker $progressTracker = null;
    protected OutputInterface $output;
    protected ?\Magento\Backend\Model\Auth\Session $authSession = null;

    /**
     * Current artist being processed (set by subclass for progress tracking)
     */
    protected ?string $currentArtist = null;

    /**
     * Signal handler flag - set to true when shutdown signal received
     */
    protected bool $shouldStop = false;

    /**
     * Start time for duration tracking
     */
    private ?float $startTime = null;

    /**
     * Unique UUID for this command execution
     */
    private ?string $uuid = null;

    /**
     * Correlation ID for tracking this execution
     */
    private ?string $correlationId = null;

    /**
     * Generate a UUID v4
     */
    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a UUID v4-like correlation ID
     */
    protected function generateCorrelationId(): string
    {
        return $this->generateUuid();
    }

    /**
     * Execute command with automatic correlation ID and logging
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->startTime = microtime(true);
        $this->uuid = $this->generateUuid();
        $this->correlationId = $this->generateCorrelationId();
        $correlationId = $this->correlationId;

        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();

        try {
            $this->logStart($correlationId, $input);
            $result = $this->doExecute($input, $output, $correlationId);

            if ($this->shouldStop) {
                $this->logEnd($correlationId, 'cancelled', 'Stopped by user signal');
                $this->failRedisProgress('Stopped by user signal');
                return Command::FAILURE;
            }

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
     * Setup signal handlers for graceful shutdown
     *
     * Handles SIGTERM (docker stop, kill) and SIGINT (Ctrl+C)
     *
     * @return void
     */
    protected function setupSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            // PCNTL extension not available (Windows or disabled)
            return;
        }

        $handler = function (int $signal) {
            $this->shouldStop = true;
            $signalName = $signal === SIGTERM ? 'SIGTERM' : 'SIGINT';
            if ($this->output) {
                $this->output->writeln(sprintf(
                    '<comment>Received %s - stopping gracefully after current operation...</comment>',
                    $signalName
                ));
            }
        };

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    /**
     * Check if command should continue processing
     *
     * Call this in loops to check for shutdown signals
     *
     * @return bool True if should continue, false if should stop
     */
    protected function shouldContinue(): bool
    {
        return !$this->shouldStop;
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
     * Get user/system identifier for audit trail
     *
     * @return string Format: "cli:username" or "admin:john.smith" or "web:guest"
     */
    protected function getStartedBy(): string
    {
        // CLI execution
        if (php_sapi_name() === 'cli') {
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $user = posix_getpwuid(posix_geteuid());
                return 'cli:' . ($user['name'] ?? 'unknown');
            }
            return 'cli:unknown';
        }

        // Web execution - check for admin user
        if ($this->authSession) {
            try {
                $adminUser = $this->authSession->getUser();
                if ($adminUser && $adminUser->getUsername()) {
                    return 'admin:' . $adminUser->getUsername();
                }
            } catch (\Exception $e) {
                // Auth session not available or not logged in
            }
        }

        return 'web:guest';
    }

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
                'uuid' => $this->uuid,
                'correlation_id' => $correlationId,
                'command_name' => $this->getName(),
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
                'started_by' => $this->getStartedBy(),
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

            // Calculate duration and memory usage
            $duration = $this->startTime ? (microtime(true) - $this->startTime) : null;
            $memoryMb = memory_get_peak_usage(true) / 1048576;

            $data = [
                'status' => $status,
                'completed_at' => date('Y-m-d H:i:s'),
                'duration_seconds' => $duration ? (int)$duration : null,
                'memory_peak_mb' => (int)$memoryMb,
            ];

            if ($errorMessage !== null) {
                $data['error_message'] = $errorMessage;
            }

            $connection->update(
                $table,
                $data,
                ['correlation_id = ?' => $correlationId]
            );

            // Auto-update artist stats if this was an import command
            if ($status === 'completed' && $this->shouldUpdateArtistStats()) {
                $this->updateArtistStats($correlationId);
            }

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
     *
     * @param string $correlationId
     * @param int $itemsProcessed Total items processed (shows, tracks, etc.)
     * @param int $itemsSuccessful Successfully completed items
     */
    protected function updateProgress(
        string $correlationId,
        int $itemsProcessed = 0,
        int $itemsSuccessful = 0
    ): void {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

            if (!$connection->isTableExists($table)) {
                return;
            }

            $data = [];
            if ($itemsProcessed > 0) {
                $data['items_processed'] = $itemsProcessed;
            }
            if ($itemsSuccessful > 0) {
                $data['items_successful'] = $itemsSuccessful;
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
     * Set Auth Session for admin user detection (optional - only for web context)
     */
    public function setAuthSession(\Magento\Backend\Model\Auth\Session $authSession): void
    {
        $this->authSession = $authSession;
    }

    /**
     * Set current artist for progress tracking and database logging
     *
     * Call this from doExecute() at the start of processing
     */
    protected function setCurrentArtist(string $artist): void
    {
        $this->currentArtist = $artist;

        // Also update artist_name in database import_run record
        if ($this->correlationId) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

                if ($connection->isTableExists($table)) {
                    $connection->update(
                        $table,
                        ['artist_name' => $artist],
                        ['correlation_id = ?' => $this->correlationId]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to update artist name in import_run', [
                    'correlation_id' => $this->correlationId,
                    'artist' => $artist,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Set collection ID for database logging
     *
     * Call this from doExecute() at the start of processing (after setCurrentArtist)
     * This enables updateArtistStats() to create rows with proper collection_id
     */
    protected function setCollectionId(string $collectionId): void
    {
        if ($this->correlationId) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

                if ($connection->isTableExists($table)) {
                    $connection->update(
                        $table,
                        ['collection_id' => $collectionId],
                        ['correlation_id = ?' => $this->correlationId]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to update collection_id in import_run', [
                    'correlation_id' => $this->correlationId,
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
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

    /**
     * Check if this command should update artist statistics
     *
     * @return bool
     */
    protected function shouldUpdateArtistStats(): bool
    {
        $command = $this->getName();
        return str_contains($command, 'download') ||
               str_contains($command, 'populate') ||
               str_contains($command, 'import');
    }

    /**
     * Update artist statistics after successful import
     *
     * @param string $correlationId
     * @return void
     */
    protected function updateArtistStats(string $correlationId): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $importTable = $this->resourceConnection->getTableName('archivedotorg_import_run');
            $artistTable = $this->resourceConnection->getTableName('archivedotorg_artist_status');

            // Check if artist_status table exists
            if (!$connection->isTableExists($artistTable)) {
                return;
            }

            // Get import run data
            $importData = $connection->fetchRow(
                $connection->select()
                    ->from($importTable)
                    ->where('correlation_id = ?', $correlationId)
            );

            if (!$importData || empty($importData['artist_name'])) {
                return;
            }

            $artistName = $importData['artist_name'];
            $commandName = $importData['command_name'] ?? '';
            $itemsSuccessful = (int)($importData['items_successful'] ?? 0);
            $collectionId = $importData['collection_id'] ?? '';

            // Update artist_status table based on command type
            // Use insertOnDuplicate to create row if it doesn't exist
            if (str_contains($commandName, 'download')) {
                // Insert or update downloaded_shows
                $connection->insertOnDuplicate(
                    $artistTable,
                    [
                        'artist_name' => $artistName,
                        'collection_id' => $collectionId,
                        'downloaded_shows' => $itemsSuccessful,
                        'last_download_at' => new \Zend_Db_Expr('NOW()'),
                        'created_at' => new \Zend_Db_Expr('NOW()'),
                        'updated_at' => new \Zend_Db_Expr('NOW()')
                    ],
                    [
                        'downloaded_shows' => new \Zend_Db_Expr('downloaded_shows + VALUES(downloaded_shows)'),
                        'last_download_at' => new \Zend_Db_Expr('NOW()'),
                        'updated_at' => new \Zend_Db_Expr('NOW()')
                    ]
                );
            } elseif (str_contains($commandName, 'populate')) {
                // Insert or update imported_tracks and last_populate_at
                $connection->insertOnDuplicate(
                    $artistTable,
                    [
                        'artist_name' => $artistName,
                        'collection_id' => $collectionId,
                        'imported_tracks' => $itemsSuccessful,
                        'last_populate_at' => new \Zend_Db_Expr('NOW()'),
                        'created_at' => new \Zend_Db_Expr('NOW()'),
                        'updated_at' => new \Zend_Db_Expr('NOW()')
                    ],
                    [
                        'imported_tracks' => new \Zend_Db_Expr('imported_tracks + VALUES(imported_tracks)'),
                        'last_populate_at' => new \Zend_Db_Expr('NOW()'),
                        'updated_at' => new \Zend_Db_Expr('NOW()')
                    ]
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to update artist stats', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

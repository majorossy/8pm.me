<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Exception\LockException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * New download command with correlation ID tracking and improved safety
 *
 * Improvements over archive:download-metadata:
 * - Correlation ID tracking for monitoring
 * - Database logging (archivedotorg_import_run table)
 * - Lock protection against concurrent downloads
 * - Progress bar with ETA
 * - Organized folder structure (from Phase 1)
 */
class DownloadCommand extends BaseLoggedCommand
{
    private MetadataDownloaderInterface $metadataDownloader;
    private LockServiceInterface $lockService;
    private State $state;
    private DirectoryList $directoryList;

    public function __construct(
        MetadataDownloaderInterface $metadataDownloader,
        LockServiceInterface $lockService,
        State $state,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        ?string $name = null
    ) {
        $this->metadataDownloader = $metadataDownloader;
        $this->lockService = $lockService;
        $this->state = $state;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('archive:download')
            ->setDescription('Download and cache show metadata from Archive.org (with logging)')
            ->addArgument(
                'artist',
                InputArgument::REQUIRED,
                'Artist name or collection ID'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum shows to download'
            )
            ->addOption(
                'incremental',
                'i',
                InputOption::VALUE_NONE,
                'Only fetch shows added since last run'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-download even if cached'
            );
    }

    protected function doExecute(
        InputInterface $input,
        OutputInterface $output,
        string $correlationId
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Set area code
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Already set
        }

        // Get arguments
        $artist = $input->getArgument('artist');
        $limit = $input->getOption('limit');
        $incremental = $input->getOption('incremental');
        $force = $input->getOption('force');

        // Validate limit
        if ($limit !== null) {
            if (!ctype_digit((string)$limit) || (int)$limit <= 0) {
                $io->error('Limit must be a positive integer');
                return self::FAILURE;
            }
            $limit = (int)$limit;
        }

        // Get collection ID from artist name
        $allCollections = $this->metadataDownloader->getAllCollections();
        $collectionId = null;

        // Try exact match on collection ID first
        if (isset($allCollections[$artist])) {
            $collectionId = $artist;
        } else {
            // Try artist name match
            foreach ($allCollections as $id => $info) {
                if (strcasecmp($info['artist_name'], $artist) === 0) {
                    $collectionId = $id;
                    break;
                }
            }
        }

        if ($collectionId === null) {
            $io->error("Unknown artist or collection: $artist");
            $io->text('Available collections:');
            $this->listCollections($io, $allCollections);
            return self::FAILURE;
        }

        $artistInfo = $allCollections[$collectionId];
        $artistName = $artistInfo['artist_name'];

        // Pre-flight check: Skip if all metadata already downloaded
        // This check runs BEFORE acquiring lock (fast check to avoid unnecessary operations)
        if (!$force && !$incremental) {
            $skipResult = $this->shouldSkipAlreadyDownloaded($artistName, $collectionId, $io);
            if ($skipResult !== null) {
                return $skipResult;
            }
        }

        $io->title("Downloading: {$artistName} ($collectionId)");
        $io->text("Correlation ID: $correlationId");

        // Set current artist and collection_id for progress tracking
        $this->setCurrentArtist($artistName);
        $this->setCollectionId($collectionId);

        // Acquire GLOBAL lock first - prevents ANY concurrent Archive.org downloads
        // Archive.org rate-limits requests; concurrent downloads cause API failures
        $globalLockToken = null;
        try {
            $globalLockToken = $this->lockService->acquire('download', '_GLOBAL_', 300);
        } catch (LockException $e) {
            $io->error("Another Archive.org download is already running. Downloads must run one at a time.");
            $io->note("Wait for the current download to complete, then retry.");
            return self::FAILURE;
        }

        // Acquire per-artist lock (prevents duplicate artist downloads)
        try {
            $lockToken = $this->lockService->acquire('download', $collectionId, 300);
        } catch (LockException $e) {
            // Release global lock before failing
            if ($globalLockToken !== null) {
                try {
                    $this->lockService->release($globalLockToken);
                } catch (\Exception $releaseEx) {
                    // Ignore release errors
                }
            }
            $io->error($e->getMessage());
            return self::FAILURE;
        }

        try {
            // Progress bar setup
            $progressBar = null;
            $totalShows = 0;
            $currentShow = 0;

            $progressCallback = function (string $message) use ($io, &$progressBar, &$totalShows, &$currentShow) {
                // Parse progress messages
                if (preg_match('/Selected (\d+) unique shows/', $message, $matches)) {
                    $totalShows = (int)$matches[1];
                    if ($progressBar === null && $totalShows > 0) {
                        $progressBar = new ProgressBar($io, $totalShows);
                        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
                        $progressBar->setMessage('Starting...');
                        $progressBar->start();
                    }
                } elseif (preg_match('/\[(\d+)\/\d+\] Downloaded: (.+)$/', $message, $matches)) {
                    $currentShow = (int)$matches[1];
                    $identifier = $matches[2];
                    if ($progressBar !== null) {
                        $progressBar->setProgress($currentShow);
                        $progressBar->setMessage($identifier);
                    } else {
                        $io->writeln($message);
                    }
                } elseif (preg_match('/\[(\d+)\/\d+\] FAILED:/', $message, $matches)) {
                    $currentShow = (int)$matches[1];
                    if ($progressBar !== null) {
                        $progressBar->setProgress($currentShow);
                        $progressBar->setMessage('Failed...');
                    }
                    $io->writeln($message);
                } else {
                    // Show other messages (cache status, etc)
                    if ($progressBar === null) {
                        $io->writeln($message);
                    }
                }
            };

            // Download
            $result = $this->metadataDownloader->download(
                $collectionId,
                $limit,
                $force,
                $incremental,
                null,
                $progressCallback
            );

            // Finish progress bar
            if ($progressBar !== null) {
                $progressBar->finish();
                $io->newLine(2);
            }

            // Show results
            $failedCount = $result['failed'] ?? 0;
            $downloadedCount = $result['downloaded'] ?? 0;

            // Update progress in database - pass both items_processed and items_successful
            // items_processed = unique shows found, items_successful = actually downloaded
            $this->updateProgress($correlationId, $result['unique_shows'] ?? 0, $downloadedCount);
            $cachedCount = $result['cached'] ?? 0;

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total Recordings', number_format($result['total_recordings'] ?? 0)],
                    ['Unique Shows', number_format($result['unique_shows'] ?? 0)],
                    ['Downloaded', number_format($downloadedCount)],
                    ['Already Cached', number_format($cachedCount)],
                    ['Failed', number_format($failedCount)],
                ]
            );

            if ($failedCount > 0) {
                $io->warning("Completed with $failedCount failures");
                return self::FAILURE;
            } else {
                $io->success('Download complete!');
                return self::SUCCESS;
            }

        } finally {
            // Always release locks (per-artist first, then global)
            try {
                $this->lockService->release($lockToken);
            } catch (\Exception $e) {
                $this->logger->error('Failed to release per-artist lock', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);
            }

            // Release global lock
            if ($globalLockToken !== null) {
                try {
                    $this->lockService->release($globalLockToken);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to release global lock', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * List available collections
     */
    private function listCollections(SymfonyStyle $io, array $collections): void
    {
        $rows = [];
        foreach ($collections as $id => $info) {
            $rows[] = [$id, $info['artist_name'], $info['identifier_pattern'] . '*'];
        }

        $io->table(['Collection ID', 'Artist Name', 'Identifier Pattern'], $rows);
    }

    /**
     * Check if download should be skipped because metadata is already fully downloaded
     *
     * Uses the archivedotorg_artist_status table to determine if an artist's
     * metadata has already been downloaded. This is more reliable than the
     * progress file because it reflects the actual database state.
     *
     * @param string $artistName Artist name
     * @param string $collectionId Archive.org collection ID
     * @param SymfonyStyle $io Console output
     * @return int|null Command exit code if should skip, null to continue
     */
    private function shouldSkipAlreadyDownloaded(
        string $artistName,
        string $collectionId,
        SymfonyStyle $io
    ): ?int {
        // Get artist status from database
        $artistStatus = $this->getArtistStatus($artistName);

        // If no status record, continue with download
        if ($artistStatus === null) {
            return null;
        }

        $downloadedShows = (int)($artistStatus['downloaded_shows'] ?? 0);

        // If no shows downloaded yet, continue
        if ($downloadedShows === 0) {
            return null;
        }

        // Count cached shows in the metadata directory
        $cachedShows = $this->countCachedShows($collectionId);

        // Also check the progress file for completion status
        $progress = $this->metadataDownloader->getProgress($collectionId);
        $progressCompleted = $progress !== null && ($progress['status'] ?? '') === 'completed';

        // Skip if:
        // 1. Progress file says completed, OR
        // 2. Downloaded shows >= cached shows (all shows processed)
        if ($progressCompleted || ($downloadedShows > 0 && $cachedShows > 0 && $downloadedShows >= $cachedShows)) {
            $io->success(sprintf(
                "Artist '%s' already has %s shows downloaded.",
                $artistName,
                number_format($downloadedShows)
            ));

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Downloaded shows', number_format($downloadedShows)],
                    ['Cached files', number_format($cachedShows)],
                    ['Progress status', $progress['status'] ?? 'N/A'],
                    ['Last updated', $progress['last_updated'] ?? 'N/A'],
                ]
            );

            $io->note('Use --force to re-download or --incremental for new shows only.');
            return self::SUCCESS;
        }

        return null;
    }

    /**
     * Get artist status from archivedotorg_artist_status table
     *
     * @param string $artistName Artist name to look up
     * @return array|null Artist status record or null if not found
     */
    private function getArtistStatus(string $artistName): ?array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('archivedotorg_artist_status');

            if (!$connection->isTableExists($tableName)) {
                return null;
            }

            $select = $connection->select()
                ->from($tableName)
                ->where('artist_name = ?', $artistName)
                ->limit(1);

            $result = $connection->fetchRow($select);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get artist status', [
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Count cached shows in metadata directory
     *
     * Checks both organized folder structure (preferred) and flat structure (legacy)
     *
     * @param string $collectionId Archive.org collection ID
     * @return int Number of cached JSON files
     */
    private function countCachedShows(string $collectionId): int
    {
        try {
            $varDir = $this->directoryList->getPath('var');
            $baseDir = $varDir . '/archivedotorg/metadata';

            // First check organized folder structure
            $organizedDir = $baseDir . '/' . $collectionId;
            if (is_dir($organizedDir)) {
                $files = glob($organizedDir . '/*.json');
                if ($files !== false && count($files) > 0) {
                    return count($files);
                }
            }

            // Fall back to using MetadataDownloader's method which handles both structures
            $identifiers = $this->metadataDownloader->getDownloadedIdentifiers($collectionId);
            return count($identifiers);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to count cached shows', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}

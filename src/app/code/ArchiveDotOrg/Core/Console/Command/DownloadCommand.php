<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Exception\LockException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
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

    public function __construct(
        MetadataDownloaderInterface $metadataDownloader,
        LockServiceInterface $lockService,
        State $state,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        ?string $name = null
    ) {
        $this->metadataDownloader = $metadataDownloader;
        $this->lockService = $lockService;
        $this->state = $state;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
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
        $io->title("Downloading: {$artistInfo['artist_name']} ($collectionId)");
        $io->text("Correlation ID: $correlationId");

        // Set current artist for progress tracking
        $this->setCurrentArtist($artistInfo['artist_name']);

        // Acquire lock
        try {
            $lockToken = $this->lockService->acquire('download', $collectionId, 300);
        } catch (LockException $e) {
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

            // Update progress in database
            $this->updateProgress($correlationId, $result['unique_shows'] ?? 0);

            // Show results
            $failedCount = $result['failed'] ?? 0;
            $downloadedCount = $result['downloaded'] ?? 0;
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
            // Always release lock
            try {
                $this->lockService->release($lockToken);
            } catch (\Exception $e) {
                $this->logger->error('Failed to release lock', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);
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
}

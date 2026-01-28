<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command to download and cache show metadata from Archive.org
 *
 * Phase 1 of the track populator: Downloads metadata to local JSON files
 * for offline processing in Phase 2.
 */
class DownloadMetadataCommand extends Command
{
    private MetadataDownloaderInterface $metadataDownloader;
    private State $state;

    public function __construct(
        MetadataDownloaderInterface $metadataDownloader,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->metadataDownloader = $metadataDownloader;
        $this->state = $state;
    }

    protected function configure(): void
    {
        $this->setName('archive:download:metadata')
            ->setDescription('Download and cache show metadata from Archive.org')
            ->addOption(
                'collection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Archive.org collection ID (e.g., GratefulDead, Goose)'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Download metadata for ALL configured collections'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum shows to download (for testing)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-download even if already cached'
            )
            ->addOption(
                'incremental',
                'i',
                InputOption::VALUE_NONE,
                'Only fetch shows added since last run'
            )
            ->addOption(
                'since',
                's',
                InputOption::VALUE_OPTIONAL,
                'Only fetch shows added since this date (YYYY-MM-DD)'
            )
            ->addOption(
                'status',
                null,
                InputOption::VALUE_NONE,
                'Show download progress without downloading'
            )
            ->addOption(
                'retry-failed',
                null,
                InputOption::VALUE_NONE,
                'Retry only previously failed downloads'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Set area code
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Already set
        }

        $collection = $input->getOption('collection');
        $all = $input->getOption('all');
        $statusOnly = $input->getOption('status');

        // Validate: must have --collection or --all
        if (!$all && empty($collection)) {
            $io->error('You must specify --collection=<id> or --all');
            $io->text('Available collections:');
            $this->listCollections($io);
            return Command::FAILURE;
        }

        // Get collections to process
        $collections = $all
            ? array_keys($this->metadataDownloader->getAllCollections())
            : [$collection];

        // Validate collection exists
        $allCollections = $this->metadataDownloader->getAllCollections();
        foreach ($collections as $col) {
            if (!isset($allCollections[$col])) {
                $io->error("Unknown collection: $col");
                $io->text('Available collections:');
                $this->listCollections($io);
                return Command::FAILURE;
            }
        }

        // Status only mode
        if ($statusOnly) {
            return $this->showStatus($io, $collections);
        }

        // Retry failed mode
        if ($input->getOption('retry-failed')) {
            return $this->retryFailed($io, $collections);
        }

        // Download mode
        $limit = $input->getOption('limit');
        $force = $input->getOption('force');
        $incremental = $input->getOption('incremental');
        $since = $input->getOption('since');

        // Validate limit
        if ($limit !== null) {
            if (!ctype_digit((string)$limit) || (int)$limit <= 0) {
                $io->error('Limit must be a positive integer');
                return Command::FAILURE;
            }
            $limit = (int)$limit;
        }

        // Validate since date
        if ($since !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            $io->error('Since date must be in YYYY-MM-DD format');
            return Command::FAILURE;
        }

        $totalStats = [
            'total_recordings' => 0,
            'unique_shows' => 0,
            'downloaded' => 0,
            'cached' => 0,
            'failed' => 0,
        ];

        foreach ($collections as $collectionId) {
            $artistInfo = $allCollections[$collectionId];
            $io->title("Downloading: {$artistInfo['artist_name']} ($collectionId)");

            $progressCallback = function (string $message) use ($io) {
                $io->writeln($message);
            };

            try {
                $result = $this->metadataDownloader->download(
                    $collectionId,
                    $limit,
                    $force,
                    $incremental,
                    $since,
                    $progressCallback
                );

                // Aggregate stats
                foreach ($totalStats as $key => $value) {
                    if (isset($result[$key])) {
                        $totalStats[$key] += $result[$key];
                    }
                }

                $failedCount = $result['failed'] ?? 0;
                $downloadedCount = $result['downloaded'] ?? 0;
                $cachedCount = $result['cached'] ?? 0;

                if ($failedCount > 0) {
                    $io->warning("Completed with $failedCount failures");
                } else {
                    $io->success("Completed: $downloadedCount downloaded, $cachedCount already cached");
                }

            } catch (\Exception $e) {
                $io->error("Failed to download $collectionId: " . $e->getMessage());
                $totalStats['failed']++;
            }
        }

        // Summary for --all
        if ($all && count($collections) > 1) {
            $io->section('Summary');
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total Recordings', number_format($totalStats['total_recordings'])],
                    ['Unique Shows', number_format($totalStats['unique_shows'])],
                    ['Downloaded', number_format($totalStats['downloaded'])],
                    ['Already Cached', number_format($totalStats['cached'])],
                    ['Failed', number_format($totalStats['failed'])],
                ]
            );
        }

        return $totalStats['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Show download status for collections
     */
    private function showStatus(SymfonyStyle $io, array $collections): int
    {
        $io->title('Download Status');

        $rows = [];
        foreach ($collections as $collectionId) {
            $progress = $this->metadataDownloader->getProgress($collectionId);

            if ($progress === null) {
                $rows[] = [
                    $collectionId,
                    'Not started',
                    '-',
                    '-',
                    '-',
                    '-',
                ];
            } else {
                $rows[] = [
                    $collectionId,
                    $progress['status'] ?? 'unknown',
                    number_format($progress['unique_shows'] ?? 0),
                    number_format($progress['downloaded'] ?? 0),
                    number_format($progress['failed'] ?? 0),
                    $progress['last_updated'] ?? '-',
                ];
            }
        }

        $io->table(
            ['Collection', 'Status', 'Unique Shows', 'Downloaded', 'Failed', 'Last Updated'],
            $rows
        );

        // Show cache directory info
        $io->section('Cache Location');
        $io->text('var/archivedotorg/metadata/');

        return Command::SUCCESS;
    }

    /**
     * Retry failed downloads
     */
    private function retryFailed(SymfonyStyle $io, array $collections): int
    {
        $io->title('Retrying Failed Downloads');

        $totalDownloaded = 0;
        $totalStillFailed = 0;

        foreach ($collections as $collectionId) {
            $io->section($collectionId);

            $progressCallback = function (string $message) use ($io) {
                $io->writeln($message);
            };

            $result = $this->metadataDownloader->retryFailed($collectionId, $progressCallback);
            $totalDownloaded += $result['downloaded'];
            $totalStillFailed += $result['still_failed'];
        }

        $io->newLine();
        $io->text("Total recovered: $totalDownloaded");
        $io->text("Still failing: $totalStillFailed");

        return $totalStillFailed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * List available collections
     */
    private function listCollections(SymfonyStyle $io): void
    {
        $collections = $this->metadataDownloader->getAllCollections();

        $rows = [];
        foreach ($collections as $id => $info) {
            $rows[] = [$id, $info['artist_name'], $info['identifier_pattern'] . '*'];
        }

        $io->table(['Collection ID', 'Artist Name', 'Identifier Pattern'], $rows);
    }
}

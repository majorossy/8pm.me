<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\DirectoryList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Status Command
 *
 * CLI command to check Archive.org module status and connectivity
 * Usage: bin/magento archive:status
 */
class StatusCommand extends Command
{
    private const ARGUMENT_ARTIST = 'artist';
    private const OPTION_TEST_COLLECTION = 'test-collection';

    /**
     * @param Config $config
     * @param ArchiveApiClientInterface $apiClient
     * @param ProductCollectionFactory $productCollectionFactory
     * @param TrackMatcherServiceInterface $trackMatcher
     * @param ResourceConnection $resourceConnection
     * @param DirectoryList $directoryList
     * @param string|null $name
     */
    public function __construct(
        private readonly Config $config,
        private readonly ArchiveApiClientInterface $apiClient,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly TrackMatcherServiceInterface $trackMatcher,
        private readonly ResourceConnection $resourceConnection,
        private readonly DirectoryList $directoryList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:status')
            ->setDescription('Check Archive.org module status and import statistics')
            ->addArgument(
                self::ARGUMENT_ARTIST,
                InputArgument::OPTIONAL,
                'Artist name to show detailed stats for (e.g., "Lettuce", "Phish")'
            )
            ->addOption(
                self::OPTION_TEST_COLLECTION,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Test connectivity with a specific collection'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $artistName = $input->getArgument(self::ARGUMENT_ARTIST);
        $testCollection = $input->getOption(self::OPTION_TEST_COLLECTION);

        if ($artistName) {
            return $this->showArtistStatus($io, $artistName, $testCollection);
        }

        return $this->showOverallStatus($io, $testCollection);
    }

    /**
     * Show status for a specific artist
     *
     * @param SymfonyStyle $io
     * @param string $artistName
     * @param string|null $testCollection
     * @return int
     */
    private function showArtistStatus(SymfonyStyle $io, string $artistName, ?string $testCollection): int
    {
        $io->title('Archive.org Import Status');

        $stats = $this->getArtistStats($artistName);

        if ($stats === null) {
            $io->error("Artist '{$artistName}' not found in configuration.");
            return Command::FAILURE;
        }

        $io->section("Artist: {$artistName}");

        $io->table(['Metric', 'Value'], [
            ['Downloaded shows', number_format($stats['downloaded_shows'])],
            ['Processed shows', number_format($stats['processed_shows'])],
            ['Unprocessed', number_format($stats['unprocessed'])],
            ['Unmatched tracks', number_format($stats['unmatched_tracks']) .
                ($stats['total_tracks'] > 0 ?
                    sprintf(' (%.1f%%)', ($stats['unmatched_tracks'] / $stats['total_tracks']) * 100) :
                    '')],
            ['Match rate', $stats['total_tracks'] > 0 ?
                sprintf('%.1f%%', (($stats['total_tracks'] - $stats['unmatched_tracks']) / $stats['total_tracks']) * 100) :
                'N/A'],
            ['Last download', $stats['last_download'] ?: 'Never'],
            ['Last populate', $stats['last_populate'] ?: 'Never']
        ]);

        $this->testApiConnection($io, $testCollection);

        $io->success('Status check completed.');

        return Command::SUCCESS;
    }

    /**
     * Show overall status for all artists
     *
     * @param SymfonyStyle $io
     * @param string|null $testCollection
     * @return int
     */
    private function showOverallStatus(SymfonyStyle $io, ?string $testCollection): int
    {
        $io->title('Archive.org Module Status');

        // Configuration status
        $io->section('Configuration');
        $io->table(['Setting', 'Value'], [
            ['Module Enabled', $this->config->isEnabled() ? 'Yes' : 'No'],
            ['Debug Mode', $this->config->isDebugEnabled() ? 'Yes' : 'No'],
            ['Base URL', $this->config->getBaseUrl()],
            ['Timeout', $this->config->getTimeout() . ' seconds'],
            ['Retry Attempts', $this->config->getRetryAttempts()],
            ['Batch Size', $this->config->getBatchSize()],
            ['Audio Format', $this->config->getAudioFormat()],
            ['Cron Enabled', $this->config->isCronEnabled() ? 'Yes' : 'No'],
            ['Cron Schedule', $this->config->getCronSchedule()]
        ]);

        // Overall statistics
        $io->section('Overall Statistics');

        $mappings = $this->config->getArtistMappings();
        $totalArtists = count($mappings);
        $totalShows = 0;
        $totalTracks = 0;

        foreach ($mappings as $mapping) {
            $artistName = $mapping['artist_name'] ?? '';
            if ($artistName) {
                $stats = $this->getArtistStats($artistName);
                if ($stats) {
                    $totalShows += $stats['processed_shows'];
                    $totalTracks += $stats['total_tracks'];
                }
            }
        }

        $io->table(['Metric', 'Value'], [
            ['Total artists', number_format($totalArtists)],
            ['Total shows', number_format($totalShows)],
            ['Total tracks', number_format($totalTracks)]
        ]);

        $this->testApiConnection($io, $testCollection);

        $io->success('Status check completed.');
        $io->note('Use "archive:status <artist>" to see detailed stats for a specific artist.');

        return Command::SUCCESS;
    }

    /**
     * Get statistics for a specific artist
     *
     * @param string $artistName
     * @return array|null
     */
    private function getArtistStats(string $artistName): ?array
    {
        // Check if artist is configured
        $mappings = $this->config->getArtistMappings();
        $found = false;

        foreach ($mappings as $mapping) {
            if (($mapping['artist_name'] ?? '') === $artistName) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return null;
        }

        $artistKey = $this->getArtistKey($artistName);

        // Get downloaded shows count (from filesystem)
        $downloadedShows = $this->getDownloadedShowsCount($artistName);

        // Get processed shows count (products created)
        $processedShows = $this->getProcessedShowsCount($artistName);

        // Get total tracks
        $totalTracks = $this->getTotalTracksCount($artistName);

        // Get unmatched tracks count
        $unmatchedTracks = $this->getUnmatchedTracksCount($artistName, $artistKey);

        // Get last import dates
        $lastDownload = $this->getLastDownloadDate($artistName);
        $lastPopulate = $this->getLastPopulateDate($artistName);

        return [
            'downloaded_shows' => $downloadedShows,
            'processed_shows' => $processedShows,
            'unprocessed' => max(0, $downloadedShows - $processedShows),
            'total_tracks' => $totalTracks,
            'unmatched_tracks' => $unmatchedTracks,
            'last_download' => $lastDownload,
            'last_populate' => $lastPopulate
        ];
    }

    /**
     * Get count of downloaded shows from filesystem
     *
     * @param string $artistName
     * @return int
     */
    private function getDownloadedShowsCount(string $artistName): int
    {
        try {
            // Get var directory path (BP is Magento root)
            $varPath = BP . '/var';
            $metadataPath = $varPath . '/archivedotorg/metadata/' . $artistName;

            if (!is_dir($metadataPath)) {
                return 0;
            }

            $files = glob($metadataPath . '/*.json');
            return $files ? count($files) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of processed shows (distinct identifiers in products)
     *
     * @param string $artistName
     * @return int
     */
    private function getProcessedShowsCount(string $artistName): int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('identifier');
            $collection->addAttributeToFilter('archive_collection', $artistName);

            $identifiers = [];
            foreach ($collection as $product) {
                $identifier = $product->getData('identifier');
                if ($identifier) {
                    $identifiers[$identifier] = true;
                }
            }

            return count($identifiers);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total tracks count
     *
     * @param string $artistName
     * @return int
     */
    private function getTotalTracksCount(string $artistName): int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('archive_collection', $artistName);
            return $collection->getSize();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get unmatched tracks count
     *
     * @param string $artistName
     * @param string $artistKey
     * @return int
     */
    private function getUnmatchedTracksCount(string $artistName, string $artistKey): int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['title', 'song_title']);
            $collection->addAttributeToFilter('archive_collection', $artistName);

            $unmatched = 0;

            foreach ($collection as $product) {
                $trackTitle = $product->getData('song_title') ?: $product->getData('title');

                if (empty($trackTitle)) {
                    continue;
                }

                // Try to match this track
                $matchResult = $this->trackMatcher->match($trackTitle, $artistKey);

                if ($matchResult === null) {
                    $unmatched++;
                }
            }

            return $unmatched;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get last download date from filesystem
     *
     * @param string $artistName
     * @return string|null
     */
    private function getLastDownloadDate(string $artistName): ?string
    {
        try {
            // Get var directory path (BP is Magento root)
            $varPath = BP . '/var';
            $metadataPath = $varPath . '/archivedotorg/metadata/' . $artistName;

            if (!is_dir($metadataPath)) {
                return null;
            }

            $files = glob($metadataPath . '/*.json');
            if (!$files) {
                return null;
            }

            $latestMtime = 0;
            foreach ($files as $file) {
                $mtime = filemtime($file);
                if ($mtime > $latestMtime) {
                    $latestMtime = $mtime;
                }
            }

            return $latestMtime > 0 ? date('Y-m-d H:i:s', $latestMtime) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get last populate date from product creation dates
     *
     * @param string $artistName
     * @return string|null
     */
    private function getLastPopulateDate(string $artistName): ?string
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('archive_collection', $artistName);
            $collection->setOrder('created_at', 'DESC');
            $collection->setPageSize(1);

            $product = $collection->getFirstItem();
            if ($product && $product->getId()) {
                return $product->getCreatedAt();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Test API connection
     *
     * @param SymfonyStyle $io
     * @param string|null $testCollection
     * @return void
     */
    private function testApiConnection(SymfonyStyle $io, ?string $testCollection): void
    {
        $io->section('API Connectivity');
        $io->write('Testing connection to Archive.org... ');

        $connected = $this->apiClient->testConnection();

        if ($connected) {
            $io->writeln('<info>OK</info>');
        } else {
            $io->writeln('<error>FAILED</error>');
            $io->error('Cannot connect to Archive.org. Check network connectivity and API URL.');
            return;
        }

        // Test specific collection if requested
        if ($testCollection !== null) {
            $io->writeln('');
            $io->writeln("Testing collection: {$testCollection}");

            try {
                $count = $this->apiClient->getCollectionCount($testCollection);
                $io->writeln(sprintf('<info>Collection "%s" contains %d items</info>', $testCollection, $count));
            } catch (\Exception $e) {
                $io->error('Failed to query collection: ' . $e->getMessage());
            }
        }
    }

    /**
     * Convert artist name to URL key format
     *
     * @param string $artistName
     * @return string
     */
    private function getArtistKey(string $artistName): string
    {
        return strtolower(str_replace(' ', '-', $artistName));
    }
}

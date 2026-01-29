<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Api\TrackPopulatorServiceInterface;
use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * New populate command with enhanced matching and tracking.
 *
 * Replaces archive:populate:tracks with improved:
 * - TrackMatcherService integration (hybrid matching)
 * - Unmatched track export
 * - Better progress reporting
 * - Match type/confidence display
 */
class PopulateCommand extends Command
{
    private TrackPopulatorServiceInterface $trackPopulatorService;
    private TrackMatcherServiceInterface $trackMatcherService;
    private MetadataDownloaderInterface $metadataDownloader;
    private Config $config;
    private State $state;

    public function __construct(
        TrackPopulatorServiceInterface $trackPopulatorService,
        TrackMatcherServiceInterface $trackMatcherService,
        MetadataDownloaderInterface $metadataDownloader,
        Config $config,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->trackPopulatorService = $trackPopulatorService;
        $this->trackMatcherService = $trackMatcherService;
        $this->metadataDownloader = $metadataDownloader;
        $this->config = $config;
        $this->state = $state;
    }

    protected function configure(): void
    {
        $this->setName('archive:populate')
            ->setDescription('[NEW] Populate products from cached metadata with hybrid track matching')
            ->addArgument(
                'artist',
                InputArgument::REQUIRED,
                'Artist name (or URL key) for matching'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be created without creating products'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum shows to process (for testing)',
                null
            )
            ->addOption(
                'export-unmatched',
                null,
                InputOption::VALUE_OPTIONAL,
                'Export unmatched tracks to file (e.g., unmatched.txt)',
                null
            )
            ->setHelp(<<<'HELP'
Populate Magento products from cached Archive.org metadata using hybrid track matching.

<info>Matching Algorithm:</info>
1. Exact match - Hash lookup (O(1))
2. Alias match - Configured aliases (O(1))
3. Metaphone phonetic match (O(1))
4. Limited fuzzy - Levenshtein on top 5 candidates only

<info>Examples:</info>
  # Dry run to preview matches
  bin/magento archive:populate lettuce --dry-run

  # Process first 100 shows
  bin/magento archive:populate lettuce --limit=100

  # Export unmatched tracks for review
  bin/magento archive:populate lettuce --export-unmatched=var/unmatched_lettuce.txt

<info>Prerequisites:</info>
  1. Artist configured in etc/config.xml or YAML
  2. Metadata downloaded: bin/magento archive:download {artist}

<info>See also:</info>
  archive:download          - Download metadata from Archive.org
  archive:show-unmatched    - View unmatched tracks with suggestions
HELP
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

        $artistInput = $input->getArgument('artist');
        $dryRun = $input->getOption('dry-run');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;
        $exportUnmatched = $input->getOption('export-unmatched');

        // Validate limit
        if ($limit !== null && $limit <= 0) {
            $io->error('Limit must be a positive integer');
            return Command::FAILURE;
        }

        // Get artist mapping
        $artistMappings = $this->config->getArtistMappings();
        $artistName = null;
        $collectionId = null;
        $artistKey = null;

        // Try to find artist by name or URL key
        foreach ($artistMappings as $mapping) {
            $name = $mapping['artist_name'] ?? '';
            $collection = $mapping['collection_id'] ?? '';
            $urlKey = strtolower(str_replace(' ', '-', $name));

            if (strcasecmp($name, $artistInput) === 0 || strcasecmp($urlKey, $artistInput) === 0) {
                $artistName = $name;
                $collectionId = $collection;
                $artistKey = $urlKey;
                break;
            }
        }

        if (!$artistName || !$collectionId) {
            $io->error("Artist not found: $artistInput");
            $io->text('Available artists:');
            foreach ($artistMappings as $mapping) {
                $name = $mapping['artist_name'] ?? '';
                $collection = $mapping['collection_id'] ?? '';
                $io->text("  - $name (collection: $collection)");
            }
            return Command::FAILURE;
        }

        $io->title("Archive.org Track Populate: $artistName");

        if ($dryRun) {
            $io->note('DRY RUN MODE - No products will be created');
        }

        // Build indexes for matching
        $io->section('Building track matching indexes...');
        try {
            $this->trackMatcherService->buildIndexes($artistKey);
            $io->success("Indexes built for: $artistKey");
        } catch (\Exception $e) {
            $io->error("Failed to build indexes: " . $e->getMessage());
            $io->text('This may indicate missing or invalid YAML configuration.');
            return Command::FAILURE;
        }

        // Track unmatched for export
        $unmatchedTracks = [];

        // Progress callback
        $progressBar = null;
        $progressCallback = function (string $message) use ($io, &$progressBar) {
            if ($progressBar) {
                $progressBar->clear();
            }
            $io->text($message);
            if ($progressBar) {
                $progressBar->display();
            }
        };

        // Execute population
        $io->section('Processing cached metadata...');

        try {
            $result = $this->trackPopulatorService->populate(
                $artistName,
                $collectionId,
                $limit,
                $dryRun,
                $progressCallback
            );

            // Display results
            $io->newLine();
            $io->section('Results');

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Shows processed', $result['shows_processed']],
                    ['Tracks matched', $result['tracks_matched']],
                    ['Tracks unmatched', $result['tracks_unmatched']],
                    ['Products created', $result['products_created']],
                    ['Products skipped', $result['products_skipped']],
                    ['Categories populated', $result['categories_populated']],
                    ['Categories empty', $result['categories_empty']],
                ]
            );

            // Calculate match rate
            $totalTracks = $result['tracks_matched'] + $result['tracks_unmatched'];
            if ($totalTracks > 0) {
                $matchRate = ($result['tracks_matched'] / $totalTracks) * 100;
                $io->text(sprintf('Match rate: <info>%.1f%%</info>', $matchRate));
            }

            // Display errors if any
            if (!empty($result['errors'])) {
                $io->section('Errors');
                foreach ($result['errors'] as $error) {
                    $io->text("  • $error");
                }
            }

            // Export unmatched tracks if requested
            if ($exportUnmatched && $result['tracks_unmatched'] > 0) {
                $io->section('Exporting unmatched tracks...');
                // Note: This requires TrackPopulatorService to track unmatched names
                $io->warning('Unmatched track export not yet implemented in TrackPopulatorService');
                $io->text("Run: bin/magento archive:show-unmatched $artistName");
            }

            if ($dryRun) {
                $io->note('This was a dry run. Use without --dry-run to create products.');
            } else {
                $io->success("Population complete for: $artistName");
            }

            // Clean up indexes to free memory
            $this->trackMatcherService->clearIndexes($artistKey);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if ($progressBar) {
                $progressBar->finish();
            }
            $io->error("Population failed: " . $e->getMessage());
            $this->trackMatcherService->clearIndexes($artistKey);
            return Command::FAILURE;
        }
    }
}

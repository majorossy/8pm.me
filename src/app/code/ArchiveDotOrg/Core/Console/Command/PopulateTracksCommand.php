<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Api\TrackPopulatorServiceInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command to populate track categories with products from cached metadata
 *
 * Phase 2 of the track populator: Reads local metadata cache and creates
 * products for tracks that match existing track categories.
 */
class PopulateTracksCommand extends Command
{
    private TrackPopulatorServiceInterface $trackPopulatorService;
    private MetadataDownloaderInterface $metadataDownloader;
    private State $state;

    public function __construct(
        TrackPopulatorServiceInterface $trackPopulatorService,
        MetadataDownloaderInterface $metadataDownloader,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->trackPopulatorService = $trackPopulatorService;
        $this->metadataDownloader = $metadataDownloader;
        $this->state = $state;
    }

    protected function configure(): void
    {
        $this->setName('archive:populate:tracks')
            ->setDescription('Populate track categories with products from cached metadata')
            ->addArgument(
                'artist',
                InputArgument::OPTIONAL,
                'Artist name as it appears in Magento categories'
            )
            ->addOption(
                'collection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Archive.org collection ID'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Populate tracks for ALL configured collections'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum shows to process (for testing)'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be created without creating products'
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

        $artist = $input->getArgument('artist');
        $collection = $input->getOption('collection');
        $all = $input->getOption('all');
        $dryRun = $input->getOption('dry-run');

        // Validate: must have artist or --all
        if (!$all && empty($artist)) {
            $io->error('You must specify an artist name or use --all');
            $io->text('Usage: bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead');
            $io->text('       bin/magento archive:populate:tracks --all');
            return Command::FAILURE;
        }

        $limit = $input->getOption('limit');
        if ($limit !== null) {
            if (!ctype_digit((string)$limit) || (int)$limit <= 0) {
                $io->error('Limit must be a positive integer');
                return Command::FAILURE;
            }
            $limit = (int)$limit;
        }

        // Get collections to process
        $allCollections = $this->metadataDownloader->getAllCollections();

        if ($all) {
            $collectionsToProcess = [];
            foreach ($allCollections as $collectionId => $info) {
                $collectionsToProcess[] = [
                    'artist' => $info['artist_name'],
                    'collection' => $collectionId,
                ];
            }
        } else {
            // Validate artist/collection
            if (!is_string($artist) || trim($artist) === '') {
                $io->error('Artist name must be non-empty');
                return Command::FAILURE;
            }
            $artist = trim($artist);

            // Find collection ID from config if not provided
            if (empty($collection)) {
                $collection = $this->findCollectionForArtist($artist, $allCollections);
                if ($collection === null) {
                    $io->error("Could not find collection ID for artist: $artist");
                    $io->text('Please specify --collection=<id> or configure the artist mapping.');
                    $io->text('Available collections:');
                    $this->listCollections($io, $allCollections);
                    return Command::FAILURE;
                }
                $io->text("Using collection: $collection");
            }

            // Validate collection exists
            if (!isset($allCollections[$collection])) {
                $io->error("Unknown collection: $collection");
                $io->text('Available collections:');
                $this->listCollections($io, $allCollections);
                return Command::FAILURE;
            }

            $collectionsToProcess = [
                ['artist' => $artist, 'collection' => $collection],
            ];
        }

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No products will be created');
        }

        $totalStats = [
            'shows_processed' => 0,
            'products_created' => 0,
            'products_skipped' => 0,
            'tracks_matched' => 0,
            'tracks_unmatched' => 0,
            'categories_populated' => 0,
            'errors' => [],
        ];

        foreach ($collectionsToProcess as $item) {
            $artistName = $item['artist'];
            $collectionId = $item['collection'];

            $io->title("Populating tracks for: $artistName ($collectionId)");

            $progressCallback = function (string $message) use ($io) {
                $io->writeln($message);
            };

            try {
                $result = $this->trackPopulatorService->populate(
                    $artistName,
                    $collectionId,
                    $limit,
                    $dryRun,
                    $progressCallback
                );

                // Aggregate stats
                $totalStats['shows_processed'] += $result['shows_processed'];
                $totalStats['products_created'] += $result['products_created'];
                $totalStats['products_skipped'] += $result['products_skipped'];
                $totalStats['tracks_matched'] += $result['tracks_matched'];
                $totalStats['tracks_unmatched'] += $result['tracks_unmatched'];
                $totalStats['categories_populated'] += $result['categories_populated'];
                $totalStats['errors'] = array_merge($totalStats['errors'], $result['errors']);

                // Show results for this collection
                $io->newLine();
                $io->table(
                    ['Metric', 'Count'],
                    [
                        ['Shows Processed', number_format($result['shows_processed'])],
                        ['Tracks Matched', number_format($result['tracks_matched'])],
                        ['Tracks Unmatched', number_format($result['tracks_unmatched'])],
                        ['Products Created', number_format($result['products_created'])],
                        ['Products Skipped (existing)', number_format($result['products_skipped'])],
                        ['Categories Populated', number_format($result['categories_populated'])],
                    ]
                );

                if (!empty($result['errors'])) {
                    $io->warning(count($result['errors']) . ' errors occurred');
                } else {
                    $io->success("Completed: {$result['products_created']} products created");
                }

            } catch (\Exception $e) {
                $io->error("Failed: " . $e->getMessage());
                $totalStats['errors'][] = "$collectionId: " . $e->getMessage();
            }
        }

        // Summary for --all
        if ($all && count($collectionsToProcess) > 1) {
            $io->section('Total Summary');
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Shows Processed', number_format($totalStats['shows_processed'])],
                    ['Tracks Matched', number_format($totalStats['tracks_matched'])],
                    ['Tracks Unmatched', number_format($totalStats['tracks_unmatched'])],
                    ['Products Created', number_format($totalStats['products_created'])],
                    ['Products Skipped', number_format($totalStats['products_skipped'])],
                    ['Categories Populated', number_format($totalStats['categories_populated'])],
                    ['Errors', count($totalStats['errors'])],
                ]
            );
        }

        // Show errors if any
        if (!empty($totalStats['errors'])) {
            $io->section('Errors');
            foreach (array_slice($totalStats['errors'], 0, 10) as $error) {
                $io->text("  - $error");
            }
            if (count($totalStats['errors']) > 10) {
                $io->text("  ... and " . (count($totalStats['errors']) - 10) . " more");
            }
        }

        return empty($totalStats['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Find collection ID for an artist name
     */
    private function findCollectionForArtist(string $artistName, array $allCollections): ?string
    {
        foreach ($allCollections as $collectionId => $info) {
            if (strcasecmp($info['artist_name'], $artistName) === 0) {
                return $collectionId;
            }
        }

        // Try partial match
        $normalizedArtist = strtolower($artistName);
        foreach ($allCollections as $collectionId => $info) {
            if (str_contains(strtolower($info['artist_name']), $normalizedArtist)) {
                return $collectionId;
            }
        }

        return null;
    }

    /**
     * List available collections
     */
    private function listCollections(SymfonyStyle $io, array $allCollections): void
    {
        $rows = [];
        foreach ($allCollections as $id => $info) {
            $rows[] = [$id, $info['artist_name']];
        }

        $io->table(['Collection ID', 'Artist Name'], $rows);
    }
}

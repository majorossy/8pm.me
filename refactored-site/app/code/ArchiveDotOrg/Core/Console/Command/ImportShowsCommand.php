<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import Shows Command
 *
 * CLI command to import shows from Archive.org
 * Usage: bin/magento archive:import:shows "Artist Name" --collection=collection_id --limit=10
 */
class ImportShowsCommand extends Command
{
    private const ARGUMENT_ARTIST = 'artist';
    private const OPTION_COLLECTION = 'collection';
    private const OPTION_LIMIT = 'limit';
    private const OPTION_OFFSET = 'offset';
    private const OPTION_DRY_RUN = 'dry-run';

    private ShowImporterInterface $showImporter;
    private Config $config;
    private State $state;

    /**
     * @param ShowImporterInterface $showImporter
     * @param Config $config
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ShowImporterInterface $showImporter,
        Config $config,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->showImporter = $showImporter;
        $this->config = $config;
        $this->state = $state;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:import:shows')
            ->setDescription('Import shows and tracks from Archive.org')
            ->addArgument(
                self::ARGUMENT_ARTIST,
                InputArgument::REQUIRED,
                'Artist name (e.g., "STS9", "Railroad Earth")'
            )
            ->addOption(
                self::OPTION_COLLECTION,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Archive.org collection ID (if not configured in admin)'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of shows to import'
            )
            ->addOption(
                self::OPTION_OFFSET,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Skip first N shows'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                'd',
                InputOption::VALUE_NONE,
                'Preview what would be imported without making changes'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Set area code
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Already set
        }

        // Check if module is enabled
        if (!$this->config->isEnabled()) {
            $io->error('ArchiveDotOrg module is disabled in configuration.');
            return Command::FAILURE;
        }

        $artistName = $input->getArgument(self::ARGUMENT_ARTIST);
        $collectionId = $input->getOption(self::OPTION_COLLECTION);
        $limit = $input->getOption(self::OPTION_LIMIT);
        $offset = $input->getOption(self::OPTION_OFFSET);
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);

        // Validate artist name
        if (!is_string($artistName) || trim($artistName) === '') {
            $io->error('Artist name must be a non-empty string.');
            return Command::FAILURE;
        }
        $artistName = trim($artistName);

        // Validate collection ID format (alphanumeric, underscore, hyphen only)
        if ($collectionId !== null) {
            if (!is_string($collectionId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $collectionId)) {
                $io->error('Collection ID must contain only alphanumeric characters, underscores, and hyphens.');
                return Command::FAILURE;
            }
        }

        // Validate limit (must be positive integer if provided)
        if ($limit !== null) {
            if (!ctype_digit((string) $limit) || (int) $limit <= 0) {
                $io->error('Limit must be a positive integer.');
                return Command::FAILURE;
            }
            $limit = (int) $limit;
        }

        // Validate offset (must be non-negative integer if provided)
        if ($offset !== null) {
            if (!ctype_digit((string) $offset) || (int) $offset < 0) {
                $io->error('Offset must be a non-negative integer.');
                return Command::FAILURE;
            }
            $offset = (int) $offset;
        }

        // Get collection ID from config if not provided
        if ($collectionId === null) {
            $collectionId = $this->config->getCollectionIdForArtist($artistName);
        }

        if ($collectionId === null) {
            $io->error(sprintf(
                'No collection ID provided for artist "%s". ' .
                'Use --collection option or configure in Admin > Stores > Configuration > Archive.org Import.',
                $artistName
            ));
            return Command::FAILURE;
        }

        $io->title('Archive.org Show Import');
        $io->table(['Setting', 'Value'], [
            ['Artist', $artistName],
            ['Collection', $collectionId],
            ['Limit', $limit ?? 'None'],
            ['Offset', $offset ?? 'None'],
            ['Mode', $dryRun ? 'Dry Run' : 'Live Import']
        ]);

        if ($dryRun) {
            return $this->executeDryRun($io, $artistName, $collectionId, $limit, $offset);
        }

        return $this->executeImport($io, $output, $artistName, $collectionId, $limit, $offset);
    }

    /**
     * Execute dry run
     *
     * @param SymfonyStyle $io
     * @param string $artistName
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     */
    private function executeDryRun(
        SymfonyStyle $io,
        string $artistName,
        string $collectionId,
        ?int $limit,
        ?int $offset
    ): int {
        $io->section('Performing Dry Run...');

        try {
            $result = $this->showImporter->dryRun(
                $artistName,
                $collectionId,
                $limit !== null ? (int) $limit : null,
                $offset !== null ? (int) $offset : null
            );

            $io->success('Dry run completed.');
            $this->displayResults($io, $result->toArray());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Dry run failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Execute actual import
     *
     * @param SymfonyStyle $io
     * @param OutputInterface $output
     * @param string $artistName
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     */
    private function executeImport(
        SymfonyStyle $io,
        OutputInterface $output,
        string $artistName,
        string $collectionId,
        ?int $limit,
        ?int $offset
    ): int {
        $io->section('Starting Import...');

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        try {
            $result = $this->showImporter->importByCollection(
                $artistName,
                $collectionId,
                $limit !== null ? (int) $limit : null,
                $offset !== null ? (int) $offset : null,
                function (int $total, int $current, string $message) use ($progressBar) {
                    if ($current === 0) {
                        $progressBar->start($total);
                    }
                    $progressBar->setProgress($current);
                    $progressBar->setMessage($message);
                }
            );

            $progressBar->finish();
            $output->writeln('');

            if ($result->hasErrors()) {
                $io->warning('Import completed with errors.');
            } else {
                $io->success('Import completed successfully.');
            }

            $this->displayResults($io, $result->toArray());

            return $result->hasErrors() ? Command::FAILURE : Command::SUCCESS;
        } catch (\Exception $e) {
            $progressBar->finish();
            $output->writeln('');
            $io->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display import results
     *
     * @param SymfonyStyle $io
     * @param array $results
     * @return void
     */
    private function displayResults(SymfonyStyle $io, array $results): void
    {
        $io->section('Results');

        $io->table(['Metric', 'Count'], [
            ['Shows Processed', $results['shows_processed']],
            ['Tracks Created', $results['tracks_created']],
            ['Tracks Updated', $results['tracks_updated']],
            ['Tracks Skipped', $results['tracks_skipped']],
            ['Total Tracks', $results['total_tracks']],
            ['Errors', $results['error_count']]
        ]);

        if ($results['duration_seconds'] !== null) {
            $io->writeln(sprintf(
                '<info>Duration: %d seconds</info>',
                $results['duration_seconds']
            ));
        }

        if (!empty($results['errors'])) {
            $io->section('Errors');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $io->writeln(sprintf(
                    '<error>%s</error> - %s',
                    $error['context'] ?? 'Unknown',
                    $error['message']
                ));
            }

            if (count($results['errors']) > 10) {
                $io->writeln(sprintf(
                    '<comment>... and %d more errors</comment>',
                    count($results['errors']) - 10
                ));
            }
        }
    }
}

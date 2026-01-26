<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\ProductRefresher;
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
 * Refresh Products Command
 *
 * CLI command to refresh existing products with fresh Archive.org data.
 * Uses batch API calls for efficiency - fetches 100 shows per API call.
 *
 * Performance:
 * - Before: 523 API calls for STS9 (one per show)
 * - After: ~6 API calls (100 identifiers per batch)
 *
 * Usage:
 *   bin/magento archive:refresh:products "Artist Name"
 *   bin/magento archive:refresh:products "STS9" --fields=rating,reviews,downloads,trending
 *   bin/magento archive:refresh:products "STS9" --fields=length  # Slow: requires per-show API
 *   bin/magento archive:refresh:products "STS9" --limit=10 --dry-run
 */
class RefreshProductsCommand extends Command
{
    private const ARGUMENT_ARTIST = 'artist';
    private const OPTION_FIELDS = 'fields';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_FORCE = 'force';
    private const OPTION_LIMIT = 'limit';

    private ProductRefresher $productRefresher;
    private Config $config;
    private State $state;

    /**
     * @param ProductRefresher $productRefresher
     * @param Config $config
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ProductRefresher $productRefresher,
        Config $config,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->productRefresher = $productRefresher;
        $this->config = $config;
        $this->state = $state;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $defaultFields = implode(', ', ProductRefresher::DEFAULT_FIELDS);
        $allFields = implode(', ', ProductRefresher::ALL_FIELDS);

        $this->setName('archive:refresh:products')
            ->setDescription('Refresh existing products with fresh Archive.org data using efficient batch API')
            ->addArgument(
                self::ARGUMENT_ARTIST,
                InputArgument::REQUIRED,
                'Artist name (e.g., "STS9", "Grateful Dead")'
            )
            ->addOption(
                self::OPTION_FIELDS,
                'f',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Fields to update (comma-separated). Default: %s. All: %s. ' .
                    'Note: "length" requires slow per-show API calls.',
                    $defaultFields,
                    $allFields
                )
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                'd',
                InputOption::VALUE_NONE,
                'Preview what would be updated without making changes'
            )
            ->addOption(
                self::OPTION_FORCE,
                null,
                InputOption::VALUE_NONE,
                'Force refresh (bypass 1-week cache)'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of shows to process'
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
        $fieldsOption = $input->getOption(self::OPTION_FIELDS);
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        $force = $input->getOption(self::OPTION_FORCE);
        $limit = $input->getOption(self::OPTION_LIMIT);

        // Validate artist name
        if (!is_string($artistName) || trim($artistName) === '') {
            $io->error('Artist name must be a non-empty string.');
            return Command::FAILURE;
        }
        $artistName = trim($artistName);

        // Parse fields
        $fields = [];
        if ($fieldsOption !== null) {
            $fields = $this->parseFields($fieldsOption);
            if ($fields === null) {
                $io->error(sprintf(
                    'Invalid fields option. Valid values: %s',
                    implode(', ', ProductRefresher::ALL_FIELDS)
                ));
                return Command::FAILURE;
            }
        }

        // Validate limit
        if ($limit !== null) {
            if (!ctype_digit((string) $limit) || (int) $limit <= 0) {
                $io->error('Limit must be a positive integer.');
                return Command::FAILURE;
            }
            $limit = (int) $limit;
        }

        $displayFields = empty($fields) ? ProductRefresher::DEFAULT_FIELDS : $fields;
        $needsPerShowApi = in_array(ProductRefresher::FIELD_LENGTH, $displayFields);

        $io->title('Archive.org Product Refresh');
        $io->table(['Setting', 'Value'], [
            ['Artist', $artistName],
            ['Fields', implode(', ', $displayFields)],
            ['Limit', $limit ?? 'None'],
            ['Force Refresh', $force ? 'Yes (bypass cache)' : 'No (use 1-week cache)'],
            ['Mode', $dryRun ? 'Dry Run' : 'Live Update'],
            ['API Mode', $needsPerShowApi ? 'Batch + Per-Show (slow)' : 'Batch Only (fast)'],
        ]);

        if ($dryRun) {
            return $this->executeDryRun($io, $artistName, $fields, $limit);
        }

        return $this->executeRefresh($io, $output, $artistName, $fields, $force, $limit);
    }

    /**
     * Parse fields option
     *
     * @param string $fieldsOption
     * @return array|null Parsed fields or null if invalid
     */
    private function parseFields(string $fieldsOption): ?array
    {
        $validFields = ProductRefresher::ALL_FIELDS;
        $requestedFields = array_map('trim', explode(',', $fieldsOption));
        $fields = [];

        foreach ($requestedFields as $field) {
            if ($field === '') {
                continue;
            }
            if (!in_array($field, $validFields, true)) {
                return null;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Execute dry run
     *
     * @param SymfonyStyle $io
     * @param string $artistName
     * @param array $fields
     * @param int|null $limit
     * @return int
     */
    private function executeDryRun(
        SymfonyStyle $io,
        string $artistName,
        array $fields,
        ?int $limit
    ): int {
        $io->section('Performing Dry Run...');

        try {
            $result = $this->productRefresher->dryRun($artistName, $fields, $limit);

            $io->success('Dry run completed.');
            $this->displayDryRunResults($io, $result);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Dry run failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Execute actual refresh
     *
     * @param SymfonyStyle $io
     * @param OutputInterface $output
     * @param string $artistName
     * @param array $fields
     * @param bool $force
     * @param int|null $limit
     * @return int
     */
    private function executeRefresh(
        SymfonyStyle $io,
        OutputInterface $output,
        string $artistName,
        array $fields,
        bool $force,
        ?int $limit
    ): int {
        $io->section('Starting Refresh...');

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        try {
            $result = $this->productRefresher->refresh(
                $artistName,
                $fields,
                $force,
                $limit,
                function (int $total, int $current, string $message) use ($progressBar) {
                    if ($current === 1) {
                        $progressBar->start($total);
                    }
                    $progressBar->setProgress($current);
                    $progressBar->setMessage($message);
                }
            );

            $progressBar->finish();
            $output->writeln('');

            if (!empty($result['errors'])) {
                $io->warning('Refresh completed with errors.');
            } else {
                $io->success('Refresh completed successfully.');
            }

            $this->displayResults($io, $result);

            return empty($result['errors']) ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $progressBar->finish();
            $output->writeln('');
            $io->error('Refresh failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display dry run results
     *
     * @param SymfonyStyle $io
     * @param array $results
     * @return void
     */
    private function displayDryRunResults(SymfonyStyle $io, array $results): void
    {
        $io->section('Preview');

        $io->table(['Metric', 'Count'], [
            ['Total Products', $results['total_products']],
            ['Unique Shows', $results['unique_shows']],
            ['Shows to Process', $results['shows_to_process']],
            ['Fields to Update', implode(', ', $results['fields_to_update'])],
            ['Estimated API Calls', $results['estimated_api_calls']],
            ['  - Batch API Calls', $results['batch_api_calls']],
            ['  - Per-Show API Calls', $results['per_show_api_calls']],
            ['Uses Batch API', $results['uses_batch_api'] ? 'Yes (fast)' : 'No'],
        ]);

        if (!empty($results['shows'])) {
            $io->section('Shows (first 10)');
            $showsToDisplay = array_slice($results['shows'], 0, 10);
            $rows = [];
            foreach ($showsToDisplay as $show) {
                $rows[] = [$show['identifier'], $show['product_count']];
            }
            $io->table(['Show Identifier', 'Products'], $rows);

            if (count($results['shows']) > 10) {
                $io->writeln(sprintf(
                    '<comment>... and %d more shows</comment>',
                    count($results['shows']) - 10
                ));
            }
        }

        // Add performance note
        if ($results['uses_batch_api'] && $results['per_show_api_calls'] === 0) {
            $io->note(sprintf(
                'Using batch API: %d shows will be fetched in only %d API call(s)',
                $results['shows_to_process'],
                $results['batch_api_calls']
            ));
        } elseif ($results['per_show_api_calls'] > 0) {
            $io->warning(sprintf(
                'Using per-show API for "length" field: This will make %d additional API calls. ' .
                'Consider removing "length" from fields for faster refresh.',
                $results['per_show_api_calls']
            ));
        }
    }

    /**
     * Display refresh results
     *
     * @param SymfonyStyle $io
     * @param array $results
     * @return void
     */
    private function displayResults(SymfonyStyle $io, array $results): void
    {
        $io->section('Results');

        $io->table(['Metric', 'Count'], [
            ['Total Shows', $results['total_shows']],
            ['Shows Processed', $results['shows_processed']],
            ['Products Updated', $results['products_updated']],
            ['Products Skipped', $results['products_skipped']],
            ['API Calls Made', $results['api_calls']],
            ['Cache Hits', $results['cache_hits']],
            ['Errors', count($results['errors'])]
        ]);

        // Performance summary
        if ($results['api_calls'] > 0) {
            $showsPerCall = $results['shows_processed'] / $results['api_calls'];
            $io->writeln(sprintf(
                '<info>Efficiency: %.1f shows per API call</info>',
                $showsPerCall
            ));
        }

        if (!empty($results['errors'])) {
            $io->section('Errors');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $io->writeln(sprintf(
                    '<error>%s</error> - %s',
                    $error['identifier'] ?? $error['sku'] ?? 'Unknown',
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

<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use ArchiveDotOrg\Core\Model\ArtistEnrichmentService;
use ArchiveDotOrg\Core\Model\ArtistConfigLoader;

/**
 * CLI command to enrich artist categories with Wikipedia and web search data
 */
class EnrichArtistDataCommand extends Command
{
    private ArtistEnrichmentService $enrichmentService;
    private ArtistConfigLoader $configLoader;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryListInterface $categoryList;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        ArtistEnrichmentService $enrichmentService,
        ArtistConfigLoader $configLoader,
        CategoryRepositoryInterface $categoryRepository,
        CategoryListInterface $categoryList,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        parent::__construct($name);
        $this->enrichmentService = $enrichmentService;
        $this->configLoader = $configLoader;
        $this->categoryRepository = $categoryRepository;
        $this->categoryList = $categoryList;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    protected function configure()
    {
        $this->setName('archive:artist:enrich')
            ->setDescription('Enrich artist categories with Wikipedia and web search data')
            ->addArgument(
                'artist',
                InputArgument::OPTIONAL,
                'Artist name (or omit to use --all)'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Process all configured artists'
            )
            ->addOption(
                'fields',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated fields to enrich (bio,origin,years_active,genres,website,facebook,instagram,twitter,stats,stats_extended)',
                'all'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview what would be enriched without saving'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing data'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artistName = $input->getArgument('artist');
        $processAll = $input->getOption('all');
        $fieldsOption = $input->getOption('fields');
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        // Validate input
        if (!$artistName && !$processAll) {
            $output->writeln('<error>Please specify an artist name or use --all</error>');
            return Command::FAILURE;
        }

        if ($artistName && $processAll) {
            $output->writeln('<error>Cannot use both artist name and --all</error>');
            return Command::FAILURE;
        }

        // Parse fields
        $fields = $this->parseFields($fieldsOption);
        if ($fields === false) {
            $output->writeln('<error>Invalid fields option. Use: bio,origin,years_active,genres,website,facebook,instagram,twitter</error>');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $output->writeln('<comment>DRY RUN MODE - No data will be saved</comment>');
        }

        // Get artists to process
        $artists = $processAll
            ? $this->getAllArtists()
            : [$this->getArtistByName($artistName)];

        if (empty($artists)) {
            $output->writeln('<error>No artists found</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Enriching %d artist(s) with fields: %s</info>',
            count($artists),
            implode(', ', $fields)
        ));

        // Progress callback
        $progressCallback = function ($total, $current, $message) use ($output) {
            $output->writeln(sprintf('[%d/%d] %s', $current, $total, $message));
        };

        // Process artists
        $startTime = microtime(true);
        $results = $this->enrichmentService->enrichBatch($artists, $fields, $progressCallback);
        $elapsed = round(microtime(true) - $startTime, 2);

        // Display results
        $output->writeln('');
        $output->writeln('<info>=== Enrichment Results ===</info>');
        $output->writeln(sprintf('Total artists: %d', $results['total']));
        $output->writeln(sprintf('Processed: %d', $results['processed']));
        $output->writeln(sprintf('Updated: %d', $results['updated']));
        $output->writeln(sprintf('Failed: %d', $results['failed']));
        $output->writeln(sprintf('Time: %s seconds', $elapsed));

        // Show detailed results
        if ($output->isVerbose()) {
            $output->writeln('');
            $output->writeln('<info>=== Detailed Results ===</info>');
            foreach ($results['details'] as $artistName => $detail) {
                $output->writeln(sprintf(
                    '<comment>%s:</comment> %d updated, %d failed',
                    $artistName,
                    count($detail['fields_updated']),
                    count($detail['fields_failed'])
                ));

                if (!empty($detail['fields_updated'])) {
                    $output->writeln('  Updated: ' . implode(', ', $detail['fields_updated']));
                }

                if (!empty($detail['data_sources'])) {
                    $output->writeln('  Sources:');
                    foreach ($detail['data_sources'] as $field => $source) {
                        $confidence = $detail['confidence'][$field] ?? 'unknown';
                        $output->writeln(sprintf('    - %s: %s [%s]', $field, $source, $confidence));
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Parse fields option into array
     *
     * @param string $fieldsOption Comma-separated fields or 'all'
     * @return array|false Array of fields or false if invalid
     */
    private function parseFields(string $fieldsOption)
    {
        $validFields = ['bio', 'origin', 'years_active', 'genres', 'website', 'facebook', 'instagram', 'twitter', 'stats', 'stats_extended'];

        if ($fieldsOption === 'all') {
            return $validFields;
        }

        $fields = array_map('trim', explode(',', $fieldsOption));
        $invalid = array_diff($fields, $validFields);

        if (!empty($invalid)) {
            return false;
        }

        return $fields;
    }

    /**
     * Get all configured artists
     *
     * @return array Array of ['category_id' => int, 'artist_name' => string]
     */
    private function getAllArtists(): array
    {
        $artists = [];
        $availableArtists = $this->configLoader->getAvailableArtists();

        foreach ($availableArtists as $artistKey => $artistName) {
            $category = $this->findArtistCategory($artistName);
            if ($category) {
                $artists[] = [
                    'category_id' => (int)$category->getId(),
                    'artist_name' => $artistName,
                ];
            }
        }

        return $artists;
    }

    /**
     * Get artist by name
     *
     * @param string $artistName Artist name
     * @return array Artist data
     */
    private function getArtistByName(string $artistName): array
    {
        $category = $this->findArtistCategory($artistName);
        if (!$category) {
            throw new \InvalidArgumentException("Artist category not found: $artistName");
        }

        return [
            'category_id' => (int)$category->getId(),
            'artist_name' => $artistName,
        ];
    }

    /**
     * Find artist category by name
     *
     * @param string $artistName Artist name
     * @return \Magento\Catalog\Api\Data\CategoryInterface|null
     */
    private function findArtistCategory(string $artistName): ?\Magento\Catalog\Api\Data\CategoryInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('name', $artistName, 'eq')
            ->addFilter('is_artist', 1, 'eq')
            ->setPageSize(1)
            ->create();

        $results = $this->categoryList->getList($searchCriteria);

        if ($results->getTotalCount() > 0) {
            return $results->getItems()[0];
        }

        return null;
    }
}

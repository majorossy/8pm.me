<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sync Albums Command
 *
 * CLI command to sync products to album/song categories based on title matching
 * Usage: bin/magento archive:sync:albums
 */
class SyncAlbumsCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_THRESHOLD = 'threshold';
    private const OPTION_COLLECTION = 'collection';

    private CategoryCollectionFactory $categoryCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private AttributeOptionManagerInterface $attributeOptionManager;
    private ProductRepositoryInterface $productRepository;
    private Config $config;
    private Logger $logger;
    private State $state;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param AttributeOptionManagerInterface $attributeOptionManager
     * @param ProductRepositoryInterface $productRepository
     * @param Config $config
     * @param Logger $logger
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        AttributeOptionManagerInterface $attributeOptionManager,
        ProductRepositoryInterface $productRepository,
        Config $config,
        Logger $logger,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->attributeOptionManager = $attributeOptionManager;
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->logger = $logger;
        $this->state = $state;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:sync:albums')
            ->setDescription('Sync products to album/song categories based on title matching')
            ->addOption(
                self::OPTION_DRY_RUN,
                'd',
                InputOption::VALUE_NONE,
                'Preview matches without making changes'
            )
            ->addOption(
                self::OPTION_THRESHOLD,
                't',
                InputOption::VALUE_OPTIONAL,
                'Similarity threshold percentage (default: 75)',
                '75'
            )
            ->addOption(
                self::OPTION_COLLECTION,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Filter by artist/collection name (e.g., STS9, "Disco Biscuits")'
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

        if (!$this->config->isEnabled()) {
            $io->error('ArchiveDotOrg module is disabled in configuration.');
            return Command::FAILURE;
        }

        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        $threshold = (float) $input->getOption(self::OPTION_THRESHOLD);
        $collection = $input->getOption(self::OPTION_COLLECTION);

        $io->title('Archive.org Album Sync');
        $io->table(['Setting', 'Value'], [
            ['Mode', $dryRun ? 'Dry Run' : 'Live Sync'],
            ['Similarity Threshold', $threshold . '%'],
            ['Collection Filter', $collection ?: 'All']
        ]);

        // Get all song categories
        $songCategories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_song', 1);

        // Filter by collection/artist if specified
        if ($collection) {
            $songCategories->addAttributeToFilter('artist', $collection);
        }

        $totalCategories = $songCategories->getSize();

        if ($totalCategories === 0) {
            $io->warning('No song categories found. Make sure categories have is_song = 1.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Processing %d song categories...', $totalCategories));

        $progressBar = new ProgressBar($output, $totalCategories);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $stats = [
            'categories_processed' => 0,
            'products_matched' => 0,
            'products_assigned' => 0,
            'errors' => []
        ];

        foreach ($songCategories as $category) {
            $progressBar->setMessage($category->getName() ?? 'Category #' . $category->getId());
            $progressBar->advance();

            try {
                $matched = $this->processSongCategory($category, $threshold, $dryRun);
                $stats['categories_processed']++;
                $stats['products_matched'] += $matched['matched'];
                $stats['products_assigned'] += $matched['assigned'];
                if (!empty($matched['first_error']) && empty($stats['first_assignment_error'])) {
                    $stats['first_assignment_error'] = $matched['first_error'];
                }
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'category' => $category->getName(),
                    'error' => $e->getMessage()
                ];
                $this->logger->logImportError('Album sync error', [
                    'category_id' => $category->getId(),
                    'error' => $e->getMessage()
                ]);
            }

            // Memory management
            if ($stats['categories_processed'] % 100 === 0) {
                $this->attributeOptionManager->clearCache();
                gc_collect_cycles();
            }
        }

        $progressBar->finish();
        $output->writeln('');

        // Display results
        $io->section('Results');
        $io->table(['Metric', 'Count'], [
            ['Categories Processed', $stats['categories_processed']],
            ['Products Matched', $stats['products_matched']],
            ['Products Assigned', $stats['products_assigned']],
            ['Errors', count($stats['errors'])]
        ]);

        if (!empty($stats['first_assignment_error'])) {
            $io->section('Assignment Error');
            $io->writeln('<error>' . $stats['first_assignment_error'] . '</error>');
        }

        if (!empty($stats['errors'])) {
            $io->section('Errors');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $io->writeln(sprintf(
                    '<error>%s</error>: %s',
                    $error['category'],
                    $error['error']
                ));
            }
        }

        return empty($stats['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Process a single song category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param float $threshold
     * @param bool $dryRun
     * @return array
     */
    private function processSongCategory($category, float $threshold, bool $dryRun): array
    {
        $result = ['matched' => 0, 'assigned' => 0];

        $artistName = $category->getData('artist');

        if (empty($artistName)) {
            return $result;
        }

        // Get the archive_collection option ID
        $collectionOptionId = $this->attributeOptionManager->getOptionId('archive_collection', $artistName);

        if ($collectionOptionId === null) {
            return $result;
        }

        // Get products for this artist
        $products = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['title', 'sku', 'name'])
            ->addAttributeToFilter('archive_collection', $collectionOptionId);

        $categoryName = strtolower(trim($category->getName() ?? ''));

        if (empty($categoryName)) {
            return $result;
        }

        foreach ($products as $product) {
            $productTitle = strtolower(trim($product->getData('title') ?? ''));

            if (empty($productTitle)) {
                continue;
            }

            // Check for match using multiple methods
            $isMatch = $this->isMatch($productTitle, $categoryName, $threshold);

            if ($isMatch) {
                $result['matched']++;

                if (!$dryRun) {
                    try {
                        $categoryIds = array_merge(
                            [$category->getId()],
                            $product->getCategoryIds() ?? []
                        );

                        $this->categoryLinkManagement->assignProductToCategories(
                            $product->getSku(),
                            array_unique($categoryIds)
                        );

                        $result['assigned']++;
                    } catch (\Exception $e) {
                        // Log first few errors to console for debugging
                        if (!isset($result['first_error'])) {
                            $result['first_error'] = $e->getMessage();
                        }
                        $this->logger->debug('Failed to assign product to category', [
                            'sku' => $product->getSku(),
                            'category_id' => $category->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $result['assigned']++;
                }
            }
        }

        return $result;
    }

    /**
     * Check if product title matches category name
     *
     * @param string $productTitle
     * @param string $categoryName
     * @param float $threshold
     * @return bool
     */
    private function isMatch(string $productTitle, string $categoryName, float $threshold): bool
    {
        // Exact metaphone match
        $productMetaphone = metaphone($productTitle);
        $categoryMetaphone = metaphone($categoryName);

        if ($productMetaphone === $categoryMetaphone) {
            return true;
        }

        // Contains check with metaphone
        if (
            strlen($categoryMetaphone) >= 4 &&
            strpos($productMetaphone, $categoryMetaphone) !== false
        ) {
            return true;
        }

        // Similar text percentage
        $similarity = 0;
        similar_text($productTitle, $categoryName, $similarity);

        if ($similarity >= $threshold) {
            return true;
        }

        return false;
    }
}

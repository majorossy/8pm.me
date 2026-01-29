<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cleanup Products Command
 *
 * CLI command to cleanup/delete Archive.org imported products
 * Usage: bin/magento archive:cleanup:products --collection=OldCollection --older-than=365 --dry-run
 */
class CleanupProductsCommand extends Command
{
    private const OPTION_COLLECTION = 'collection';
    private const OPTION_OLDER_THAN = 'older-than';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_FORCE = 'force';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private ProductCollectionFactory $productCollectionFactory;
    private ProductRepositoryInterface $productRepository;
    private ResourceConnection $resourceConnection;
    private Config $config;
    private State $state;
    private Logger $logger;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param State $state
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        Config $config,
        State $state,
        Logger $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:cleanup:products')
            ->setDescription('Delete Archive.org imported products by collection or age')
            ->addOption(
                self::OPTION_COLLECTION,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Delete products from specific collection/artist (e.g., "STS9")'
            )
            ->addOption(
                self::OPTION_OLDER_THAN,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Delete products imported more than N days ago'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                'd',
                InputOption::VALUE_NONE,
                'Preview what would be deleted without making changes'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            )
            ->addOption(
                self::OPTION_BATCH_SIZE,
                'b',
                InputOption::VALUE_OPTIONAL,
                'Number of products to delete per batch',
                100
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

        $collectionFilter = $input->getOption(self::OPTION_COLLECTION);
        $olderThanDays = $input->getOption(self::OPTION_OLDER_THAN);
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        $force = $input->getOption(self::OPTION_FORCE);
        $batchSize = (int) $input->getOption(self::OPTION_BATCH_SIZE);

        // Validate inputs
        if ($collectionFilter === null && $olderThanDays === null) {
            $io->error('You must specify either --collection or --older-than (or both).');
            return Command::FAILURE;
        }

        if ($olderThanDays !== null) {
            if (!ctype_digit((string) $olderThanDays) || (int) $olderThanDays <= 0) {
                $io->error('--older-than must be a positive integer (days).');
                return Command::FAILURE;
            }
            $olderThanDays = (int) $olderThanDays;
        }

        if ($batchSize <= 0 || $batchSize > 1000) {
            $io->error('--batch-size must be between 1 and 1000.');
            return Command::FAILURE;
        }

        // Build product collection
        try {
            $collection = $this->buildProductCollection($collectionFilter, $olderThanDays);
        } catch (LocalizedException $e) {
            $io->error('Failed to build product collection: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $totalProducts = $collection->getSize();

        if ($totalProducts === 0) {
            $io->success('No products found matching the criteria.');
            return Command::SUCCESS;
        }

        // Display summary
        $io->title('Archive.org Product Cleanup');

        $criteria = [];
        if ($collectionFilter !== null) {
            $criteria[] = ['Collection', $collectionFilter];
        }
        if ($olderThanDays !== null) {
            $criteria[] = ['Older Than', $olderThanDays . ' days'];
        }
        $criteria[] = ['Products Found', $totalProducts];
        $criteria[] = ['Mode', $dryRun ? 'Dry Run (no changes)' : 'LIVE DELETE'];

        $io->table(['Filter', 'Value'], $criteria);

        // Confirmation
        if (!$dryRun && !$force) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                sprintf(
                    '<question>Are you sure you want to DELETE %d products? This cannot be undone. [y/N]</question> ',
                    $totalProducts
                ),
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $io->warning('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Execute cleanup
        if ($dryRun) {
            return $this->executeDryRun($io, $collection);
        }

        return $this->executeDelete($io, $output, $collection, $batchSize);
    }

    /**
     * Build the product collection with filters
     *
     * @param string|null $collectionFilter
     * @param int|null $olderThanDays
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws LocalizedException
     */
    private function buildProductCollection(?string $collectionFilter, ?int $olderThanDays)
    {
        $collection = $this->productCollectionFactory->create();

        // Only include Archive.org products (have identifier attribute set)
        $collection->addAttributeToFilter('identifier', ['notnull' => true]);
        $collection->addAttributeToFilter('identifier', ['neq' => '']);

        // Filter by collection/artist
        if ($collectionFilter !== null) {
            // Archive collection is a dropdown, so we need to filter by option label
            $collection->addAttributeToSelect('archive_collection');

            // Get the attribute option ID for the collection name
            $attribute = $collection->getResource()->getAttribute('archive_collection');
            if ($attribute && $attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions();
                $optionId = null;

                foreach ($options as $option) {
                    if (strcasecmp($option['label'], $collectionFilter) === 0) {
                        $optionId = $option['value'];
                        break;
                    }
                }

                if ($optionId !== null) {
                    $collection->addAttributeToFilter('archive_collection', $optionId);
                } else {
                    throw new LocalizedException(
                        __('Collection/artist "%1" not found in attribute options.', $collectionFilter)
                    );
                }
            }
        }

        // Filter by age (created_at)
        if ($olderThanDays !== null) {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
            $collection->addAttributeToFilter('created_at', ['lt' => $cutoffDate]);
        }

        return $collection;
    }

    /**
     * Execute dry run - list products that would be deleted
     *
     * @param SymfonyStyle $io
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return int
     */
    private function executeDryRun(SymfonyStyle $io, $collection): int
    {
        $io->section('Products that would be deleted (Dry Run)');

        $collection->addAttributeToSelect(['name', 'sku', 'identifier', 'created_at']);
        $collection->setPageSize(50);

        $products = [];
        $count = 0;

        foreach ($collection as $product) {
            $count++;
            if ($count <= 20) {
                $products[] = [
                    $product->getSku(),
                    substr($product->getName(), 0, 40),
                    $product->getData('identifier'),
                    $product->getCreatedAt()
                ];
            }
        }

        if (!empty($products)) {
            $io->table(['SKU', 'Name', 'Identifier', 'Created'], $products);
        }

        if ($count > 20) {
            $io->writeln(sprintf('<comment>... and %d more products</comment>', $count - 20));
        }

        $io->newLine();
        $io->success(sprintf('Dry run complete. %d products would be deleted.', $count));

        return Command::SUCCESS;
    }

    /**
     * Execute actual deletion
     *
     * @param SymfonyStyle $io
     * @param OutputInterface $output
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param int $batchSize
     * @return int
     */
    private function executeDelete(SymfonyStyle $io, OutputInterface $output, $collection, int $batchSize): int
    {
        $io->section('Deleting Products...');

        $totalProducts = $collection->getSize();
        $deletedCount = 0;
        $errorCount = 0;
        $errors = [];

        $progressBar = new ProgressBar($output, $totalProducts);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        // Process in batches
        $collection->setPageSize($batchSize);
        $lastPage = $collection->getLastPageNumber();

        for ($currentPage = 1; $currentPage <= $lastPage; $currentPage++) {
            $collection->setCurPage($currentPage);
            $collection->load();

            foreach ($collection as $product) {
                try {
                    $sku = $product->getSku();
                    $this->productRepository->delete($product);
                    $deletedCount++;

                    $progressBar->setMessage('Deleted: ' . $sku);
                    $progressBar->advance();

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ];

                    $progressBar->setMessage('Error: ' . $product->getSku());
                    $progressBar->advance();

                    $this->logger->logImportError('Product deletion failed', [
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Clear collection for next page
            $collection->clear();

            // Memory cleanup
            gc_collect_cycles();
        }

        $progressBar->finish();
        $output->writeln('');

        // Clean up orphaned URL rewrites
        $io->section('Cleaning up orphaned URL rewrites...');
        $orphanedCount = $this->cleanOrphanedUrlRewrites();
        $io->writeln(sprintf('Removed %d orphaned URL rewrites', $orphanedCount));

        // Results
        $io->section('Results');

        $io->table(['Metric', 'Count'], [
            ['Products Deleted', $deletedCount],
            ['URL Rewrites Removed', $orphanedCount],
            ['Errors', $errorCount]
        ]);

        // Show errors if any
        if (!empty($errors)) {
            $io->section('Errors');
            foreach (array_slice($errors, 0, 10) as $error) {
                $io->writeln(sprintf(
                    '<error>%s</error>: %s',
                    $error['sku'],
                    $error['error']
                ));
            }

            if (count($errors) > 10) {
                $io->writeln(sprintf(
                    '<comment>... and %d more errors</comment>',
                    count($errors) - 10
                ));
            }
        }

        $this->logger->info('Product cleanup completed', [
            'deleted' => $deletedCount,
            'errors' => $errorCount
        ]);

        if ($errorCount > 0) {
            $io->warning(sprintf(
                'Cleanup completed with %d errors. %d products deleted.',
                $errorCount,
                $deletedCount
            ));
            return Command::FAILURE;
        }

        $io->success(sprintf('Successfully deleted %d products.', $deletedCount));
        return Command::SUCCESS;
    }

    /**
     * Clean up orphaned URL rewrites (rewrites for deleted products)
     *
     * @return int Number of orphaned rewrites removed
     */
    private function cleanOrphanedUrlRewrites(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $urlRewriteTable = $this->resourceConnection->getTableName('url_rewrite');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        // Delete URL rewrites for products that no longer exist
        $query = $connection->deleteFromSelect(
            $connection->select()
                ->from($urlRewriteTable, 'url_rewrite_id')
                ->where('entity_type = ?', 'product')
                ->where(
                    'entity_id NOT IN (?)',
                    $connection->select()->from($productTable, 'entity_id')
                ),
            $urlRewriteTable
        );

        return $connection->query($query)->rowCount();
    }
}

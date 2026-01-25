<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Sync Albums Cron Job
 *
 * Synchronizes products to album/song categories based on title matching
 */
class SyncAlbums
{
    private const SIMILARITY_THRESHOLD = 75.0;

    private CategoryCollectionFactory $categoryCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private AttributeOptionManagerInterface $attributeOptionManager;
    private Config $config;
    private Logger $logger;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param AttributeOptionManagerInterface $attributeOptionManager
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        AttributeOptionManagerInterface $attributeOptionManager,
        Config $config,
        Logger $logger
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->attributeOptionManager = $attributeOptionManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            $this->logger->debug('SyncAlbums cron: Module is disabled');
            return;
        }

        $this->logger->info('SyncAlbums cron: Starting album sync');

        $songCategories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_song', 1);

        $stats = [
            'categories' => 0,
            'matched' => 0,
            'assigned' => 0,
            'errors' => 0
        ];

        foreach ($songCategories as $category) {
            try {
                $result = $this->processSongCategory($category);
                $stats['categories']++;
                $stats['matched'] += $result['matched'];
                $stats['assigned'] += $result['assigned'];
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->logger->error('SyncAlbums cron: Category processing failed', [
                    'category_id' => $category->getId(),
                    'error' => $e->getMessage()
                ]);
            }

            // Memory management
            if ($stats['categories'] % 100 === 0) {
                $this->attributeOptionManager->clearCache();
                gc_collect_cycles();
            }
        }

        $this->logger->info('SyncAlbums cron: Completed', $stats);
    }

    /**
     * Process a single song category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     */
    private function processSongCategory($category): array
    {
        $result = ['matched' => 0, 'assigned' => 0];

        $artistName = $category->getData('artist');

        if (empty($artistName)) {
            return $result;
        }

        $collectionOptionId = $this->attributeOptionManager->getOptionId('archive_collection', $artistName);

        if ($collectionOptionId === null) {
            return $result;
        }

        $products = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['title', 'sku'])
            ->addAttributeToFilter('archive_collection', $collectionOptionId);

        $categoryName = strtolower(trim($category->getName()));

        foreach ($products as $product) {
            $productTitle = strtolower(trim($product->getData('title') ?? ''));

            if (empty($productTitle)) {
                continue;
            }

            if ($this->isMatch($productTitle, $categoryName)) {
                $result['matched']++;

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
                    // Log but don't fail the whole process
                    $this->logger->debug('SyncAlbums cron: Assignment failed', [
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ]);
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
     * @return bool
     */
    private function isMatch(string $productTitle, string $categoryName): bool
    {
        // Exact metaphone match
        if (metaphone($productTitle) === metaphone($categoryName)) {
            return true;
        }

        // Contains check
        $categoryMetaphone = metaphone($categoryName);
        if (strlen($categoryMetaphone) >= 4 && strpos(metaphone($productTitle), $categoryMetaphone) !== false) {
            return true;
        }

        // Similar text percentage
        $similarity = 0;
        similar_text($productTitle, $categoryName, $similarity);

        return $similarity >= self::SIMILARITY_THRESHOLD;
    }
}

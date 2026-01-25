<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\CategoryAssignmentServiceInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Category Assignment Service Implementation
 *
 * Manages product-to-category assignments during imports
 */
class CategoryAssignmentService implements CategoryAssignmentServiceInterface
{
    /**
     * Cache of category IDs by collection ID
     *
     * @var array<string, int>
     */
    private array $collectionCategoryCache = [];

    /**
     * Cache of show category IDs by identifier
     *
     * @var array<string, int>
     */
    private array $showCategoryCache = [];

    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryFactory $categoryFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private StoreManagerInterface $storeManager;
    private ResourceConnection $resourceConnection;
    private Config $config;
    private Logger $logger;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        Config $config,
        Logger $logger
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function assignToArtistCategory(
        int $productId,
        string $artistName,
        ?string $collectionId = null
    ): bool {
        try {
            $categoryId = $this->getOrCreateArtistCategory($artistName, $collectionId);

            if ($categoryId === null) {
                $this->logger->debug('No artist category found or created', [
                    'artist' => $artistName,
                    'collection' => $collectionId
                ]);
                return false;
            }

            return $this->assignProductToCategory($productId, $categoryId);
        } catch (\Exception $e) {
            $this->logger->logImportError('Failed to assign product to artist category', [
                'product_id' => $productId,
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function assignToShowCategory(
        int $productId,
        string $showIdentifier,
        string $showTitle,
        ?int $parentCategoryId = null
    ): ?int {
        if ($parentCategoryId === null) {
            return null;
        }

        try {
            $categoryId = $this->getOrCreateShowCategory(
                $showIdentifier,
                $showTitle,
                $parentCategoryId
            );

            if ($categoryId !== null) {
                $this->assignProductToCategory($productId, $categoryId);
            }

            return $categoryId;
        } catch (\Exception $e) {
            $this->logger->logImportError('Failed to assign product to show category', [
                'product_id' => $productId,
                'show' => $showIdentifier,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateArtistCategory(
        string $artistName,
        ?string $collectionId = null
    ): ?int {
        // First, check if we have a configured category for this collection
        if ($collectionId !== null) {
            $categoryId = $this->getCategoryByCollectionId($collectionId);
            if ($categoryId !== null) {
                return $categoryId;
            }
        }

        // Check configuration for artist mappings
        $mappings = $this->config->getArtistMappings();
        foreach ($mappings as $mapping) {
            if (isset($mapping['artist_name']) && $mapping['artist_name'] === $artistName) {
                if (isset($mapping['category_id']) && !empty($mapping['category_id'])) {
                    return (int) $mapping['category_id'];
                }
            }
        }

        // If no existing category, we could create one (disabled by default for safety)
        $this->logger->debug('No artist category found', [
            'artist' => $artistName,
            'collection' => $collectionId
        ]);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateShowCategory(
        string $showIdentifier,
        string $showTitle,
        int $parentCategoryId
    ): ?int {
        $cacheKey = $showIdentifier . '_' . $parentCategoryId;

        // Check cache
        if (isset($this->showCategoryCache[$cacheKey])) {
            return $this->showCategoryCache[$cacheKey];
        }

        // Look for existing category with this identifier
        $categoryId = $this->findCategoryByIdentifier($showIdentifier, $parentCategoryId);

        if ($categoryId !== null) {
            $this->showCategoryCache[$cacheKey] = $categoryId;
            return $categoryId;
        }

        // Create new category
        try {
            $category = $this->categoryFactory->create();
            $category->setName($this->sanitizeCategoryName($showTitle));
            $category->setParentId($parentCategoryId);
            $category->setIsActive(true);
            $category->setIncludeInMenu(false);
            $category->setData('archive_collection_id', $showIdentifier);
            $category->setData('is_album', true);

            // Set path
            $parentCategory = $this->categoryRepository->get($parentCategoryId);
            $category->setPath($parentCategory->getPath());

            $this->categoryRepository->save($category);

            $categoryId = (int) $category->getId();
            $this->showCategoryCache[$cacheKey] = $categoryId;

            $this->logger->debug('Created show category', [
                'category_id' => $categoryId,
                'show' => $showIdentifier,
                'parent_id' => $parentCategoryId
            ]);

            return $categoryId;
        } catch (\Exception $e) {
            $this->logger->logImportError('Failed to create show category', [
                'show' => $showIdentifier,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategoryByCollectionId(string $collectionId): ?int
    {
        // Check cache
        if (isset($this->collectionCategoryCache[$collectionId])) {
            return $this->collectionCategoryCache[$collectionId];
        }

        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('entity_id');
            $collection->addAttributeToFilter('archive_collection_id', $collectionId);
            $collection->setPageSize(1);

            $category = $collection->getFirstItem();

            if ($category && $category->getId()) {
                $categoryId = (int) $category->getId();
                $this->collectionCategoryCache[$collectionId] = $categoryId;
                return $categoryId;
            }
        } catch (\Exception $e) {
            $this->logger->debug('Error looking up category by collection ID', [
                'collection' => $collectionId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function bulkAssignToCategory(array $productIds, int $categoryId): int
    {
        if (empty($productIds)) {
            return 0;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_category_product');

        // Get max position
        $select = $connection->select()
            ->from($tableName, ['max_position' => new \Zend_Db_Expr('MAX(position)')])
            ->where('category_id = ?', $categoryId);

        $maxPosition = (int) $connection->fetchOne($select);
        $position = $maxPosition + 1;

        $insertData = [];
        foreach ($productIds as $productId) {
            $insertData[] = [
                'category_id' => $categoryId,
                'product_id' => (int) $productId,
                'position' => $position++
            ];
        }

        try {
            $connection->insertOnDuplicate(
                $tableName,
                $insertData,
                ['position']
            );

            $this->logger->debug('Bulk assigned products to category', [
                'category_id' => $categoryId,
                'count' => count($productIds)
            ]);

            return count($productIds);
        } catch (\Exception $e) {
            $this->logger->logImportError('Bulk category assignment failed', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Assign a single product to a category
     *
     * @param int $productId
     * @param int $categoryId
     * @return bool
     */
    private function assignProductToCategory(int $productId, int $categoryId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_category_product');

        try {
            // Get max position
            $select = $connection->select()
                ->from($tableName, ['max_position' => new \Zend_Db_Expr('MAX(position)')])
                ->where('category_id = ?', $categoryId);

            $maxPosition = (int) $connection->fetchOne($select);

            $connection->insertOnDuplicate(
                $tableName,
                [
                    'category_id' => $categoryId,
                    'product_id' => $productId,
                    'position' => $maxPosition + 1
                ],
                ['position']
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->debug('Category assignment failed', [
                'product_id' => $productId,
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Find category by archive identifier under a parent
     *
     * @param string $identifier
     * @param int $parentCategoryId
     * @return int|null
     */
    private function findCategoryByIdentifier(string $identifier, int $parentCategoryId): ?int
    {
        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('entity_id');
            $collection->addAttributeToFilter('archive_collection_id', $identifier);
            $collection->addAttributeToFilter('parent_id', $parentCategoryId);
            $collection->setPageSize(1);

            $category = $collection->getFirstItem();

            if ($category && $category->getId()) {
                return (int) $category->getId();
            }
        } catch (\Exception $e) {
            // Attribute may not exist
            $this->logger->debug('Error finding category by identifier', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Sanitize category name for use as URL key
     *
     * @param string $name
     * @return string
     */
    private function sanitizeCategoryName(string $name): string
    {
        // Trim and limit length
        $name = trim($name);

        if (strlen($name) > 200) {
            $name = substr($name, 0, 200);
        }

        // Remove problematic characters
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);

        return $name ?: 'Unknown Show';
    }

    /**
     * Clear internal caches
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->collectionCategoryCache = [];
        $this->showCategoryCache = [];
    }
}

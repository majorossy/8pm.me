<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Category Service
 *
 * Provides category operations with duplication prevention.
 */
class CategoryService
{
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryInterfaceFactory $categoryFactory;
    private CollectionFactory $categoryCollectionFactory;
    private array $categoryCache = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryInterfaceFactory $categoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Find category by URL key and parent ID.
     *
     * Prevents duplicate categories with same URL key under same parent.
     *
     * @param string $urlKey
     * @param int $parentId
     * @return CategoryInterface|null
     */
    public function findByUrlKeyAndParent(string $urlKey, int $parentId): ?CategoryInterface
    {
        $cacheKey = sprintf('%s_%d', $urlKey, $parentId);

        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('url_key', $urlKey)
                   ->addAttributeToFilter('parent_id', $parentId)
                   ->setPageSize(1);

        $category = $collection->getFirstItem();

        if ($category->getId()) {
            $this->categoryCache[$cacheKey] = $category;
            return $category;
        }

        return null;
    }

    /**
     * Create category if it doesn't exist (idempotent operation).
     *
     * @param string $name
     * @param string $urlKey
     * @param int $parentId
     * @param array $additionalData Additional category attributes
     * @return CategoryInterface
     * @throws LocalizedException
     */
    public function createIfNotExists(
        string $name,
        string $urlKey,
        int $parentId,
        array $additionalData = []
    ): CategoryInterface {
        // Check for existing category
        $existing = $this->findByUrlKeyAndParent($urlKey, $parentId);
        if ($existing) {
            return $existing;
        }

        // Create new category
        /** @var CategoryInterface $category */
        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setUrlKey($urlKey);
        $category->setParentId($parentId);
        $category->setIsActive(true);

        // Set additional attributes
        foreach ($additionalData as $key => $value) {
            $category->setData($key, $value);
        }

        $savedCategory = $this->categoryRepository->save($category);

        // Cache the new category
        $cacheKey = sprintf('%s_%d', $urlKey, $parentId);
        $this->categoryCache[$cacheKey] = $savedCategory;

        return $savedCategory;
    }

    /**
     * Bulk check if categories exist.
     *
     * @param array $categories Array of ['url_key' => string, 'parent_id' => int]
     * @return array Array of url_key => CategoryInterface for existing categories
     */
    public function findExistingCategories(array $categories): array
    {
        $urlKeys = array_column($categories, 'url_key');
        $parentIds = array_column($categories, 'parent_id');

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('url_key', ['in' => $urlKeys])
                   ->addAttributeToFilter('parent_id', ['in' => $parentIds]);

        $existing = [];
        foreach ($collection as $category) {
            $key = sprintf('%s_%d', $category->getUrlKey(), $category->getParentId());
            $existing[$category->getUrlKey()] = $category;
            $this->categoryCache[$key] = $category;
        }

        return $existing;
    }

    /**
     * Clear internal cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->categoryCache = [];
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;

/**
 * Populate Category Attributes Data Patch
 *
 * Sets is_artist, is_album, is_song, and song_track_number attributes
 * on existing categories based on their level in the hierarchy.
 *
 * Hierarchy:
 *   Level 2: Artists root container (ID 48)
 *   Level 3: Artist categories (is_artist = 1)
 *   Level 4: Album categories (is_album = 1)
 *   Level 5: Track/Song categories (is_song = 1, song_track_number = position)
 */
class PopulateCategoryAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private const ARTISTS_ROOT_ID = 48;

    private ModuleDataSetupInterface $moduleDataSetup;
    private CollectionFactory $categoryCollectionFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private LoggerInterface $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $this->populateArtistAttributes();
            $this->populateAlbumAttributes();
            $this->populateTrackAttributes();
        } catch (\Exception $e) {
            $this->logger->error('Failed to populate category attributes: ' . $e->getMessage());
            throw $e;
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Set is_artist = 1 for all level 3 categories under Artists root
     */
    private function populateArtistAttributes(): void
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('level', 3);
        $collection->addFieldToFilter('path', ['like' => '1/2/' . self::ARTISTS_ROOT_ID . '/%']);

        foreach ($collection as $category) {
            $category->setData('is_artist', 1);
            $category->setData('is_album', 0);
            $category->setData('is_song', 0);
            $this->categoryRepository->save($category);
            $this->logger->info(sprintf('Set is_artist=1 for category "%s" (ID: %d)', $category->getName(), $category->getId()));
        }
    }

    /**
     * Set is_album = 1 for all level 4 categories under Artists root
     */
    private function populateAlbumAttributes(): void
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('level', 4);
        $collection->addFieldToFilter('path', ['like' => '1/2/' . self::ARTISTS_ROOT_ID . '/%']);

        foreach ($collection as $category) {
            $category->setData('is_artist', 0);
            $category->setData('is_album', 1);
            $category->setData('is_song', 0);
            $this->categoryRepository->save($category);
            $this->logger->info(sprintf('Set is_album=1 for category "%s" (ID: %d)', $category->getName(), $category->getId()));
        }
    }

    /**
     * Set is_song = 1 and song_track_number for all level 5 categories under Artists root
     */
    private function populateTrackAttributes(): void
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('level', 5);
        $collection->addFieldToFilter('path', ['like' => '1/2/' . self::ARTISTS_ROOT_ID . '/%']);
        $collection->setOrder('parent_id', 'ASC');
        $collection->setOrder('position', 'ASC');

        foreach ($collection as $category) {
            $position = (int) $category->getPosition();
            // Ensure track number is within valid range (1-99)
            $trackNumber = max(1, min(99, $position));

            $category->setData('is_artist', 0);
            $category->setData('is_album', 0);
            $category->setData('is_song', 1);
            $category->setData('song_track_number', $trackNumber);
            $this->categoryRepository->save($category);
            $this->logger->info(sprintf(
                'Set is_song=1, track_number=%d for category "%s" (ID: %d)',
                $trackNumber,
                $category->getName(),
                $category->getId()
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Reset all attributes to NULL/0 for categories under Artists root
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('level', ['gteq' => 3]);
        $collection->addFieldToFilter('path', ['like' => '1/2/' . self::ARTISTS_ROOT_ID . '/%']);

        foreach ($collection as $category) {
            $category->setData('is_artist', 0);
            $category->setData('is_album', 0);
            $category->setData('is_song', 0);
            $category->setData('song_track_number', null);
            $this->categoryRepository->save($category);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateCategoryAttributes::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

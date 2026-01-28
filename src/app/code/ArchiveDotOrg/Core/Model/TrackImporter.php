<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;
use ArchiveDotOrg\Core\Api\TrackImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Track Importer Implementation
 *
 * Creates and updates Magento products from Archive.org track data.
 * Uses proper dependency injection instead of ObjectManager.
 */
class TrackImporter implements TrackImporterInterface
{
    private ProductRepositoryInterface $productRepository;
    private ProductInterfaceFactory $productFactory;
    private AttributeOptionManagerInterface $attributeOptionManager;
    private Config $config;
    private Logger $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param AttributeOptionManagerInterface $attributeOptionManager
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        AttributeOptionManagerInterface $attributeOptionManager,
        Config $config,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->attributeOptionManager = $attributeOptionManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function importTrack(
        TrackInterface $track,
        ShowInterface $show,
        string $artistName,
        ?int $existingProductId = null
    ): int {
        $sku = $track->generateSku();

        if (empty($sku)) {
            throw new LocalizedException(
                __('Cannot import track without SHA1 hash: %1', $track->getTitle())
            );
        }

        // Only look up product ID if not provided (optimization for bulk imports)
        if ($existingProductId === null) {
            $existingProductId = $this->getProductIdBySku($sku);
        }
        $isUpdate = $existingProductId !== null;

        try {
            if ($isUpdate) {
                $product = $this->productRepository->get($sku);
            } else {
                /** @var Product $product */
                $product = $this->productFactory->create();
                $this->initializeNewProduct($product, $sku);
            }

            // Set product data from track and show
            $this->setProductData($product, $track, $show, $artistName);

            // Save the product
            $savedProduct = $this->productRepository->save($product);

            $this->logger->logTrackCreated($sku, $track->getTitle());

            return (int) $savedProduct->getId();
        } catch (\Exception $e) {
            $previousError = $e->getPrevious() ? $e->getPrevious()->getMessage() : 'none';
            $this->logger->logImportError('Failed to import track', [
                'sku' => $sku,
                'title' => $track->getTitle(),
                'error' => $e->getMessage(),
                'previous' => $previousError,
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(
                __('Failed to import track %1: %2', $sku, $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function importShowTracks(ShowInterface $show, string $artistName): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'product_ids' => []
        ];

        $tracks = $show->getTracks();

        foreach ($tracks as $track) {
            try {
                $sku = $track->generateSku();

                if (empty($sku)) {
                    $result['skipped']++;
                    continue;
                }

                // Get product ID once and pass to importTrack to avoid double query
                $existingProductId = $this->getProductIdBySku($sku);
                $isUpdate = $existingProductId !== null;

                // Pass the existing product ID to avoid redundant lookup in importTrack
                $productId = $this->importTrack($track, $show, $artistName, $existingProductId);
                $result['product_ids'][] = $productId;

                if ($isUpdate) {
                    $result['updated']++;
                } else {
                    $result['created']++;
                }
            } catch (\Exception $e) {
                $this->logger->logImportError('Track import failed', [
                    'show' => $show->getIdentifier(),
                    'track' => $track->getTitle(),
                    'error' => $e->getMessage()
                ]);
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function productExists(string $sku): bool
    {
        return $this->getProductIdBySku($sku) !== null;
    }

    /**
     * @inheritDoc
     */
    public function getProductIdBySku(string $sku): ?int
    {
        try {
            $product = $this->productRepository->get($sku);
            return (int) $product->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Initialize a new product with default values
     *
     * @param Product $product
     * @param string $sku
     * @return void
     */
    private function initializeNewProduct(Product $product, string $sku): void
    {
        $product->setSku($sku);
        $product->setTypeId(Type::TYPE_VIRTUAL);
        $product->setAttributeSetId($this->config->getAttributeSetId());
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setPrice(0);
        $product->setWebsiteIds([$this->config->getDefaultWebsiteId()]);
        $product->setStoreId(0);

        // Stock data for virtual product
        $product->setStockData([
            'use_config_manage_stock' => 0,
            'manage_stock' => 0,
            'is_in_stock' => 1
        ]);
    }

    /**
     * Set product data from track and show
     *
     * @param Product $product
     * @param TrackInterface $track
     * @param ShowInterface $show
     * @param string $artistName
     * @return void
     * @throws LocalizedException
     */
    private function setProductData(
        Product $product,
        TrackInterface $track,
        ShowInterface $show,
        string $artistName
    ): void {
        // Generate name: Artist Title Year Venue
        $name = sprintf(
            '%s %s %s %s',
            $artistName,
            $track->getTitle(),
            $show->getYear() ?? '',
            $show->getVenue() ?? ''
        );
        $product->setName(trim($name));

        // URL key
        $product->setUrlKey($track->generateUrlKey());

        // Description
        $product->setDescription($show->getDescription() ?? '');

        // Track-specific attributes
        $product->setData('title', $track->getTitle());
        $product->setData('length', $this->formatTrackLength($track->getLength()));
        $product->setData('album_track', $track->getTrackNumber());
        $product->setData('show_source', $show->getSource());

        // Show-specific attributes
        $product->setData('identifier', $show->getIdentifier());
        $product->setData('show_name', $show->getTitle());
        $product->setData('dir', $show->getDir());
        $product->setData('server_one', $show->getServerOne() ?? 'not stored');
        $product->setData('server_two', $show->getServerTwo() ?? 'not stored');
        $product->setData('notes', $show->getNotes() ?? 'not stored');
        $product->setData('lineage', $show->getLineage() ?? 'not stored');
        $product->setData('show_date', $show->getDate());
        $product->setData('pub_date', $show->getPubDate());
        $product->setData('guid', $show->getGuid());

        // Build song URL
        if ($show->getServerOne() && $show->getDir()) {
            $filename = $this->getFilenameWithoutExtension($track->getName()) . '.flac';
            $songUrl = $this->config->buildStreamingUrl(
                $show->getServerOne(),
                $show->getDir(),
                $filename
            );
            $product->setData('song_url', $songUrl);
        }

        // Dropdown attributes (using AttributeOptionManager)
        $this->setDropdownAttribute($product, 'show_year', $show->getYear());
        $this->setDropdownAttribute($product, 'show_venue', $show->getVenue());
        $this->setDropdownAttribute($product, 'show_taper', $show->getTaper());
        $this->setDropdownAttribute($product, 'show_transferer', $show->getTransferer());
        $this->setDropdownAttribute($product, 'show_location', $show->getCoverage());
        $this->setDropdownAttribute($product, 'archive_collection', $artistName);

        // Archive.org rating attributes
        $avgRating = $show->getAvgRating();
        if ($avgRating !== null) {
            $product->setData('archive_avg_rating', $avgRating);
        }
        $product->setData('archive_num_reviews', $show->getNumReviews() ?? 0);
    }

    /**
     * Set a dropdown attribute value
     *
     * @param Product $product
     * @param string $attributeCode
     * @param string|null $value
     * @return void
     */
    private function setDropdownAttribute(Product $product, string $attributeCode, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        try {
            $optionId = $this->attributeOptionManager->getOrCreateOptionId($attributeCode, $value);
            $product->setData($attributeCode, $optionId);
        } catch (\Exception $e) {
            $this->logger->debug('Failed to set dropdown attribute', [
                'attribute' => $attributeCode,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get filename without extension
     *
     * @param string $filename
     * @return string
     */
    private function getFilenameWithoutExtension(string $filename): string
    {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            return $filename;
        }

        return substr($filename, 0, $pos);
    }

    /**
     * Format track length from seconds to MM:SS or H:MM:SS
     *
     * @param string|null $seconds
     * @return string|null
     */
    private function formatTrackLength(?string $seconds): ?string
    {
        if ($seconds === null || !is_numeric($seconds)) {
            return $seconds;
        }

        $totalSeconds = (int) floor((float) $seconds);
        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $secs = $totalSeconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }
}

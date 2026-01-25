<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Player Block
 *
 * Provides playlist data for the audio player on cart, category, and product pages
 */
class Player extends Template
{
    private Json $jsonSerializer;
    private CheckoutSession $checkoutSession;
    private Registry $registry;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private ProductRepositoryInterface $productRepository;

    /**
     * @param Context $context
     * @param Json $jsonSerializer
     * @param CheckoutSession $checkoutSession
     * @param Registry $registry
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Json $jsonSerializer,
        CheckoutSession $checkoutSession,
        Registry $registry,
        ProductCollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsonSerializer = $jsonSerializer;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Get context type (cart, category, product)
     *
     * @return string
     */
    public function getContextType(): string
    {
        return $this->getData('context_type') ?? 'cart';
    }

    /**
     * Get playlist as JSON string
     *
     * @return string
     */
    public function getPlaylistJson(): string
    {
        $playlist = $this->getPlaylistData();
        return $this->jsonSerializer->serialize($playlist);
    }

    /**
     * Get playlist data as array
     *
     * @return array
     */
    public function getPlaylistData(): array
    {
        $contextType = $this->getContextType();

        switch ($contextType) {
            case 'cart':
                return $this->getCartPlaylist();
            case 'category':
                return $this->getCategoryPlaylist();
            case 'product':
                return $this->getProductPlaylist();
            default:
                return [];
        }
    }

    /**
     * Get cart playlist
     *
     * @return array
     */
    private function getCartPlaylist(): array
    {
        $playlist = [];

        try {
            $quote = $this->checkoutSession->getQuote();

            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $trackData = $this->buildTrackData($product);

                if ($trackData !== null) {
                    $playlist[] = $trackData;
                }
            }
        } catch (LocalizedException $e) {
            // Session not available or empty cart
        }

        return $playlist;
    }

    /**
     * Get category playlist
     *
     * @return array
     */
    private function getCategoryPlaylist(): array
    {
        $playlist = [];
        $category = $this->registry->registry('current_category');

        if ($category === null) {
            return $playlist;
        }

        // Check if this is an Archive.org category
        if (!$category->getData('is_artist') && !$category->getData('is_album')) {
            return $playlist;
        }

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoryFilter($category);
            $collection->addAttributeToSelect(['title', 'song_url', 'archive_collection']);
            $collection->addAttributeToFilter('song_url', ['notnull' => true]);
            $collection->addAttributeToFilter('song_url', ['neq' => '']);
            $collection->setPageSize(100);

            foreach ($collection as $product) {
                $trackData = $this->buildTrackData($product);

                if ($trackData !== null) {
                    $playlist[] = $trackData;
                }
            }
        } catch (\Exception $e) {
            // Category or collection error
        }

        return $playlist;
    }

    /**
     * Get product playlist (single track)
     *
     * @return array
     */
    private function getProductPlaylist(): array
    {
        $playlist = [];
        $product = $this->registry->registry('current_product');

        if ($product === null) {
            return $playlist;
        }

        $trackData = $this->buildTrackData($product);

        if ($trackData !== null) {
            $playlist[] = $trackData;
        }

        return $playlist;
    }

    /**
     * Build track data from product
     *
     * @param Product $product
     * @return array|null
     */
    private function buildTrackData(Product $product): ?array
    {
        $title = $product->getData('title');
        $songUrl = $product->getData('song_url');

        if (empty($title) || empty($songUrl)) {
            return null;
        }

        $artist = $this->getAttributeOptionLabel($product, 'archive_collection');

        return [
            'title' => $title,
            'mp3' => $this->normalizeUrl($songUrl),
            'artist' => $artist ?? 'Unknown Artist'
        ];
    }

    /**
     * Normalize URL to have https:// prefix
     *
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        return 'https://' . ltrim($url, '/');
    }

    /**
     * Get the label for an attribute option
     *
     * @param Product $product
     * @param string $attributeCode
     * @return string|null
     */
    private function getAttributeOptionLabel(Product $product, string $attributeCode): ?string
    {
        $attribute = $product->getResource()->getAttribute($attributeCode);

        if (!$attribute) {
            return null;
        }

        $optionId = $product->getData($attributeCode);

        if (empty($optionId)) {
            return null;
        }

        $label = $attribute->getSource()->getOptionText($optionId);

        return $label ? (string) $label : null;
    }

    /**
     * Get playlist as comma-separated JavaScript object string (legacy format)
     *
     * @return string
     * @deprecated Use getPlaylistJson() instead
     */
    public function getPlaylist(): string
    {
        $playlist = '';

        foreach ($this->getPlaylistData() as $track) {
            $playlist .= '{';
            $playlist .= '"title":"' . $this->escapeJs($track['title']) . '",';
            $playlist .= '"mp3":"' . $track['mp3'] . '",';
            $playlist .= '"artist":"' . $this->escapeJs($track['artist']) . '"';
            $playlist .= '},';
        }

        return rtrim($playlist, ',');
    }

    /**
     * Escape JavaScript string
     *
     * Uses JSON encoding which properly escapes all special characters including
     * quotes, backslashes, newlines, and unicode. This is XSS-safe for JavaScript contexts.
     *
     * @param string $string
     * @return string
     */
    private function escapeJs(string $string): string
    {
        // json_encode with JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        // provides comprehensive escaping for JavaScript string contexts
        $encoded = json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        // json_encode wraps in quotes, so strip them for use in existing quoted context
        if ($encoded !== false && strlen($encoded) >= 2) {
            return substr($encoded, 1, -1);
        }

        // Fallback for encoding failure - escape minimal dangerous chars
        return str_replace(
            ['\\', '"', "'", "\n", "\r", '<', '>'],
            ['\\\\', '\\"', "\\'", '\\n', '\\r', '\\u003C', '\\u003E'],
            $string
        );
    }

    /**
     * Check if there are playable items
     *
     * @return bool
     */
    public function hasPlayableItems(): bool
    {
        return !empty($this->getPlaylistData());
    }

    /**
     * Get the count of playable items
     *
     * @return int
     */
    public function getPlayableItemCount(): int
    {
        return count($this->getPlaylistData());
    }
}

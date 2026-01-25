<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use Magento\Catalog\Model\Product;

/**
 * Image Import Service Interface
 *
 * Handles downloading and attaching images to products
 */
interface ImageImportServiceInterface
{
    /**
     * Import an image from URL and attach to product
     *
     * @param Product $product
     * @param string $imageUrl
     * @param string[] $imageTypes Types to assign (e.g., ['image', 'small_image', 'thumbnail'])
     * @param bool $visible Whether to show in gallery
     * @return bool Success status
     */
    public function importImage(
        Product $product,
        string $imageUrl,
        array $imageTypes = [],
        bool $visible = true
    ): bool;

    /**
     * Import spectrogram image for a track
     *
     * @param Product $product
     * @param string $serverUrl
     * @param string $dir
     * @param string $filename
     * @return bool
     */
    public function importSpectrogram(
        Product $product,
        string $serverUrl,
        string $dir,
        string $filename
    ): bool;

    /**
     * Check if product already has images
     *
     * @param Product $product
     * @return bool
     */
    public function productHasImages(Product $product): bool;
}

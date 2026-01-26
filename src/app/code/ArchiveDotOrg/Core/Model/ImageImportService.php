<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ImageImportServiceInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Image Import Service Implementation
 *
 * Downloads images from Archive.org and attaches them to products
 */
class ImageImportService implements ImageImportServiceInterface
{
    private Filesystem $filesystem;
    private Curl $httpClient;
    private Config $config;
    private Logger $logger;

    /**
     * @param Filesystem $filesystem
     * @param Curl $httpClient
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Filesystem $filesystem,
        Curl $httpClient,
        Config $config,
        Logger $logger
    ) {
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function importImage(
        Product $product,
        string $imageUrl,
        array $imageTypes = [],
        bool $visible = true
    ): bool {
        try {
            // Validate URL
            if (!$this->isValidUrl($imageUrl)) {
                $this->logger->debug('Invalid image URL', ['url' => $imageUrl]);
                return false;
            }

            // Skip URLs with problematic characters
            if ($this->hasProblematicCharacters($imageUrl)) {
                $this->logger->debug('Image URL has problematic characters', ['url' => $imageUrl]);
                return false;
            }

            // Create temp directory
            $tmpDir = $this->getTempDirectory(basename($imageUrl));

            // Download image
            $localPath = $this->downloadImage($imageUrl, $tmpDir);

            if ($localPath === null) {
                return false;
            }

            // Attach to product
            $product->addImageToMediaGallery(
                $localPath,
                $imageTypes,
                true, // Move file
                !$visible // Disabled = not visible
            );

            $this->logger->debug('Image imported', [
                'product_id' => $product->getId(),
                'url' => $imageUrl
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->logImportError('Image import failed', [
                'url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function importSpectrogram(
        Product $product,
        string $serverUrl,
        string $dir,
        string $filename
    ): bool {
        // Build spectrogram URL
        $spectrogramFilename = pathinfo($filename, PATHINFO_FILENAME) . '_spectrogram.png';
        $imageUrl = sprintf('https://%s%s/%s', $serverUrl, $dir, $spectrogramFilename);

        return $this->importImage(
            $product,
            $imageUrl,
            ['image', 'small_image', 'thumbnail'],
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function productHasImages(Product $product): bool
    {
        $image = $product->getData('image');
        return !empty($image) && $image !== 'no_selection';
    }

    /**
     * Validate URL format
     *
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check for problematic characters in URL
     *
     * @param string $url
     * @return bool
     */
    private function hasProblematicCharacters(string $url): bool
    {
        // Check for spaces or single quotes that can cause issues
        return preg_match('/[\s\']/', $url) === 1;
    }

    /**
     * Get temp directory for image storage
     *
     * @param string $imageName
     * @return string
     */
    private function getTempDirectory(string $imageName): string
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        // Create path based on first two characters (like Magento does)
        $firstChar = mb_substr($imageName, 0, 1, 'UTF-8');
        $secondChar = mb_substr($imageName, 1, 1, 'UTF-8');

        $relativePath = 'tmp/catalog/product/' . $firstChar . '/' . $secondChar;

        $mediaDir->create($relativePath);

        return $mediaDir->getAbsolutePath($relativePath);
    }

    /**
     * Download image to local path
     *
     * @param string $url
     * @param string $directory
     * @return string|null Local path or null on failure
     */
    private function downloadImage(string $url, string $directory): ?string
    {
        try {
            $this->httpClient->setTimeout($this->config->getTimeout());
            $this->httpClient->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->httpClient->setHeaders([
                'User-Agent' => 'ArchiveDotOrg-Magento/2.0'
            ]);

            $this->httpClient->get($url);

            if ($this->httpClient->getStatus() !== 200) {
                $this->logger->debug('Image download failed', [
                    'url' => $url,
                    'status' => $this->httpClient->getStatus()
                ]);
                return null;
            }

            $body = $this->httpClient->getBody();

            if (empty($body)) {
                return null;
            }

            // Validate it's actually an image
            if (!$this->isValidImageContent($body)) {
                $this->logger->debug('Downloaded content is not a valid image', ['url' => $url]);
                return null;
            }

            $localPath = $directory . '/' . basename($url);

            if (file_put_contents($localPath, $body) === false) {
                return null;
            }

            return $localPath;
        } catch (\Exception $e) {
            $this->logger->debug('Image download exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate that content is an image
     *
     * @param string $content
     * @return bool
     */
    private function isValidImageContent(string $content): bool
    {
        // Check for common image file signatures
        $signatures = [
            "\x89PNG" => 'png',
            "\xFF\xD8\xFF" => 'jpg',
            "GIF87a" => 'gif',
            "GIF89a" => 'gif',
            "RIFF" => 'webp'
        ];

        foreach ($signatures as $signature => $type) {
            if (strpos($content, $signature) === 0) {
                return true;
            }
        }

        return false;
    }
}

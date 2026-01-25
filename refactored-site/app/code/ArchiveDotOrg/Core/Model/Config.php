<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration wrapper for Archive.org module settings
 */
class Config
{
    private const XML_PATH_ENABLED = 'archivedotorg/general/enabled';
    private const XML_PATH_DEBUG = 'archivedotorg/general/debug';
    private const XML_PATH_BASE_URL = 'archivedotorg/api/base_url';
    private const XML_PATH_TIMEOUT = 'archivedotorg/api/timeout';
    private const XML_PATH_RETRY_ATTEMPTS = 'archivedotorg/api/retry_attempts';
    private const XML_PATH_RETRY_DELAY = 'archivedotorg/api/retry_delay';
    private const XML_PATH_BATCH_SIZE = 'archivedotorg/import/batch_size';
    private const XML_PATH_AUDIO_FORMAT = 'archivedotorg/import/audio_format';
    private const XML_PATH_IMPORT_IMAGES = 'archivedotorg/import/import_images';
    private const XML_PATH_ARTIST_MAPPINGS = 'archivedotorg/mappings/artist_mappings';
    private const XML_PATH_CRON_ENABLED = 'archivedotorg/cron/enabled';
    private const XML_PATH_CRON_SCHEDULE = 'archivedotorg/cron/schedule';
    private const XML_PATH_ATTRIBUTE_SET_ID = 'archivedotorg/import/attribute_set_id';
    private const XML_PATH_DEFAULT_WEBSITE_ID = 'archivedotorg/import/default_website_id';

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Archive.org base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_BASE_URL,
            ScopeInterface::SCOPE_STORE
        ) ?: 'https://archive.org';
    }

    /**
     * Get API timeout in seconds
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE
        ) ?: 30;
    }

    /**
     * Get number of retry attempts for failed API calls
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_ATTEMPTS,
            ScopeInterface::SCOPE_STORE
        ) ?: 3;
    }

    /**
     * Get delay between retries in milliseconds
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_DELAY,
            ScopeInterface::SCOPE_STORE
        ) ?: 1000;
    }

    /**
     * Get batch size for processing
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE
        ) ?: 100;
    }

    /**
     * Get preferred audio format
     *
     * @return string
     */
    public function getAudioFormat(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_AUDIO_FORMAT,
            ScopeInterface::SCOPE_STORE
        ) ?: 'flac';
    }

    /**
     * Check if images should be imported
     *
     * @return bool
     */
    public function shouldImportImages(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMPORT_IMAGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get artist to collection mappings
     *
     * @return array
     */
    public function getArtistMappings(): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ARTIST_MAPPINGS,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($value)) {
            return [];
        }

        // Handle serialized array from admin config
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                return [];
            }
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Get collection ID for an artist name
     *
     * @param string $artistName
     * @return string|null
     */
    public function getCollectionIdForArtist(string $artistName): ?string
    {
        $mappings = $this->getArtistMappings();

        foreach ($mappings as $mapping) {
            if (isset($mapping['artist_name']) && $mapping['artist_name'] === $artistName) {
                return $mapping['collection_id'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check if cron is enabled
     *
     * @return bool
     */
    public function isCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CRON_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get cron schedule expression
     *
     * @return string
     */
    public function getCronSchedule(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CRON_SCHEDULE,
            ScopeInterface::SCOPE_STORE
        ) ?: '0 2 * * *';
    }

    /**
     * Get product attribute set ID
     *
     * @return int
     */
    public function getAttributeSetId(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_ATTRIBUTE_SET_ID,
            ScopeInterface::SCOPE_STORE
        ) ?: 9;
    }

    /**
     * Get default website ID
     *
     * @return int
     */
    public function getDefaultWebsiteId(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_WEBSITE_ID,
            ScopeInterface::SCOPE_STORE
        ) ?: 1;
    }

    /**
     * Build search URL for a collection
     *
     * @param string $collectionId
     * @param int $rows
     * @return string
     */
    public function buildSearchUrl(string $collectionId, int $rows = 999999): string
    {
        return sprintf(
            '%s/advancedsearch.php?q=Collection%%3A%s&fl[]=identifier&sort[]=&sort[]=&sort[]=&rows=%d&page=1&output=json',
            $this->getBaseUrl(),
            urlencode($collectionId),
            $rows
        );
    }

    /**
     * Build metadata URL for an identifier
     *
     * @param string $identifier
     * @return string
     */
    public function buildMetadataUrl(string $identifier): string
    {
        return sprintf('%s/metadata/%s', $this->getBaseUrl(), urlencode($identifier));
    }

    /**
     * Build streaming URL for a track
     *
     * @param string $server
     * @param string $dir
     * @param string $filename
     * @return string
     */
    public function buildStreamingUrl(string $server, string $dir, string $filename): string
    {
        return sprintf('https://%s%s/%s', $server, $dir, $filename);
    }
}

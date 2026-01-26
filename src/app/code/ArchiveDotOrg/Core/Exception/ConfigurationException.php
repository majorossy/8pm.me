<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Configuration Exception
 *
 * Thrown when module configuration is invalid or missing
 */
class ConfigurationException extends LocalizedException
{
    private ?string $configPath;

    /**
     * @param Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     * @param string|null $configPath
     */
    public function __construct(
        Phrase $phrase,
        ?\Exception $cause = null,
        int $code = 0,
        ?string $configPath = null
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->configPath = $configPath;
    }

    /**
     * Create exception for missing configuration
     *
     * @param string $configPath
     * @return self
     */
    public static function missingConfig(string $configPath): self
    {
        return new self(
            __('Missing required configuration: %1', $configPath),
            null,
            0,
            $configPath
        );
    }

    /**
     * Create exception for invalid configuration value
     *
     * @param string $configPath
     * @param string $reason
     * @return self
     */
    public static function invalidValue(string $configPath, string $reason): self
    {
        return new self(
            __('Invalid configuration value for %1: %2', $configPath, $reason),
            null,
            0,
            $configPath
        );
    }

    /**
     * Create exception for missing artist mapping
     *
     * @param string $artistName
     * @return self
     */
    public static function missingArtistMapping(string $artistName): self
    {
        return new self(
            __('No collection ID configured for artist "%1". Configure in Admin > Stores > Configuration > Archive.org Import.', $artistName),
            null,
            0,
            'archivedotorg/mappings/artist_mappings'
        );
    }

    /**
     * Create exception for module disabled
     *
     * @return self
     */
    public static function moduleDisabled(): self
    {
        return new self(
            __('ArchiveDotOrg module is disabled. Enable in Admin > Stores > Configuration > Archive.org Import.'),
            null,
            0,
            'archivedotorg/general/enabled'
        );
    }

    /**
     * Get the configuration path
     *
     * @return string|null
     */
    public function getConfigPath(): ?string
    {
        return $this->configPath;
    }
}

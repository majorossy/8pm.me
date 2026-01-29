<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ArtistConfigLoaderInterface;
use ArchiveDotOrg\Core\Api\ArtistConfigValidatorInterface;
use ArchiveDotOrg\Core\Exception\ConfigurationException;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Artist Configuration Loader
 *
 * Loads and validates artist YAML configuration files.
 * Supports both yaml_parse_file() and Symfony YAML parser fallback.
 *
 * @see docs/import-rearchitecture/03-PHASE-2-YAML.md
 */
class ArtistConfigLoader implements ArtistConfigLoaderInterface
{
    private const YAML_DIR = 'app/code/ArchiveDotOrg/Core/config/artists';

    /**
     * Cached configurations
     * @var array<string, array>
     */
    private array $cache = [];

    /**
     * @param DirectoryList $directoryList
     * @param ArtistConfigValidatorInterface $validator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly ArtistConfigValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function load(string $artistKey): array
    {
        if (isset($this->cache[$artistKey])) {
            return $this->cache[$artistKey];
        }

        $yamlPath = $this->getYamlPath($artistKey);

        if (!file_exists($yamlPath)) {
            throw ConfigurationException::missingConfig($yamlPath);
        }

        // Parse YAML if file exists
        if (!function_exists('yaml_parse_file')) {
            // Fallback: try to use Symfony YAML if available
            if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                try {
                    $config = \Symfony\Component\Yaml\Yaml::parseFile($yamlPath);
                } catch (\Exception $e) {
                    throw ConfigurationException::invalidValue(
                        'yaml',
                        sprintf('Failed to parse YAML: %s', $e->getMessage())
                    );
                }
            } else {
                throw ConfigurationException::invalidValue(
                    'yaml',
                    'YAML parser not available. Install symfony/yaml or php-yaml extension.'
                );
            }
        } else {
            $config = yaml_parse_file($yamlPath);
            if ($config === false) {
                throw ConfigurationException::invalidValue(
                    'yaml',
                    sprintf('Failed to parse YAML file: %s', $yamlPath)
                );
            }
        }

        // Validate configuration before caching
        $validationResult = $this->validator->validate($config);
        if (!$validationResult['valid']) {
            $errorMessage = sprintf(
                "Invalid YAML configuration for '%s':\n%s",
                $artistKey,
                implode("\n", $validationResult['errors'])
            );
            throw ConfigurationException::invalidValue('yaml', $errorMessage);
        }

        // Log warnings if present
        if (!empty($validationResult['warnings'])) {
            foreach ($validationResult['warnings'] as $warning) {
                $this->logger->warning(sprintf('[%s] %s', $artistKey, $warning));
            }
        }

        $this->cache[$artistKey] = $config;
        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableArtists(): array
    {
        $configDir = $this->getConfigDir();

        if (!is_dir($configDir)) {
            return [];
        }

        $artists = [];
        $files = glob($configDir . '/*.yaml') ?: [];

        foreach ($files as $file) {
            $filename = basename($file, '.yaml');
            // Skip template file
            if ($filename !== 'template') {
                $artists[] = $filename;
            }
        }

        return $artists;
    }

    /**
     * @inheritDoc
     */
    public function clearCache(?string $artistKey = null): void
    {
        if ($artistKey === null) {
            $this->cache = [];
        } else {
            unset($this->cache[$artistKey]);
        }
    }

    /**
     * Get the path to the artist YAML config file.
     *
     * @param string $artistKey
     * @return string
     */
    private function getYamlPath(string $artistKey): string
    {
        return $this->getConfigDir() . '/' . $artistKey . '.yaml';
    }

    /**
     * Get the configuration directory path.
     *
     * @return string
     */
    private function getConfigDir(): string
    {
        try {
            $rootDir = $this->directoryList->getRoot();
        } catch (\Exception $e) {
            $rootDir = BP;
        }

        return $rootDir . '/src/' . self::YAML_DIR;
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Loads and caches artist YAML configuration.
 */
interface ArtistConfigLoaderInterface
{
    /**
     * Load artist configuration from YAML file.
     *
     * @param string $artistKey Artist URL key (e.g., "lettuce")
     * @return array Parsed and validated configuration
     * @throws \ArchiveDotOrg\Core\Exception\ConfigurationException If YAML invalid
     */
    public function load(string $artistKey): array;

    /**
     * Get list of all available artist keys.
     *
     * @return string[]
     */
    public function getAvailableArtists(): array;

    /**
     * Clear cached configuration.
     *
     * @param string|null $artistKey Specific artist or null for all
     * @return void
     */
    public function clearCache(?string $artistKey = null): void;
}

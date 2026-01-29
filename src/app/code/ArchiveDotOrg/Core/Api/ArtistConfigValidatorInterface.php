<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Validates artist YAML configuration.
 */
interface ArtistConfigValidatorInterface
{
    /**
     * Validate artist configuration array.
     *
     * @param array $config Parsed YAML configuration
     * @return array{valid: bool, errors: string[], warnings: string[]}
     */
    public function validate(array $config): array;
}

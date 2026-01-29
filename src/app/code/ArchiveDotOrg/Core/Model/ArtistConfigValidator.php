<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ArtistConfigValidatorInterface;

/**
 * Artist Configuration Validator
 *
 * Validates YAML artist configuration against schema requirements.
 */
class ArtistConfigValidator implements ArtistConfigValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(array $config): array
    {
        $errors = [];
        $warnings = [];

        // Validate artist section
        if (!isset($config['artist'])) {
            $errors[] = 'Missing required section: artist';
        } else {
            $errors = array_merge($errors, $this->validateArtistSection($config['artist']));
        }

        // Validate matching configuration (fuzzy must be disabled)
        if (isset($config['matching'])) {
            $matchingErrors = $this->validateMatchingSection($config['matching']);
            $errors = array_merge($errors, $matchingErrors);
        }

        // Validate albums section (if present)
        if (isset($config['albums'])) {
            $albumErrors = $this->validateAlbumsSection($config['albums']);
            $errors = array_merge($errors, $albumErrors);
        }

        // Validate tracks section (if present)
        if (isset($config['tracks'])) {
            $trackErrors = $this->validateTracksSection($config['tracks']);
            $errors = array_merge($errors, $trackErrors);
        }

        // Warnings for optional but recommended fields
        if (empty($config['albums'])) {
            $warnings[] = 'No albums defined - tracks will not have album context';
        }

        if (empty($config['tracks'])) {
            $warnings[] = 'No tracks defined - matching will rely on Archive.org metadata only';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate artist section.
     *
     * @param array $artist
     * @return array Validation errors
     */
    private function validateArtistSection(array $artist): array
    {
        $errors = [];

        if (empty($artist['name'])) {
            $errors[] = 'artist.name is required';
        }

        if (empty($artist['collection_id'])) {
            $errors[] = 'artist.collection_id is required';
        }

        // Validate URL key format (if present)
        if (isset($artist['url_key']) && !$this->isValidUrlKey($artist['url_key'])) {
            $errors[] = 'artist.url_key must contain only lowercase letters, numbers, and hyphens';
        }

        return $errors;
    }

    /**
     * Validate matching section - CRITICAL: Fuzzy matching must be disabled.
     *
     * @param array $matching
     * @return array Validation errors
     */
    private function validateMatchingSection(array $matching): array
    {
        $errors = [];

        // CRITICAL: Fuzzy matching is disabled by default
        if (!empty($matching['fuzzy_enabled'])) {
            $errors[] = 'fuzzy_enabled is true - this is disabled by default. ' .
                       'Use hybrid matching instead (exact→alias→metaphone→limited fuzzy). ' .
                       'Full fuzzy matching takes ~2-10 minutes and uses 50-100MB memory, ' .
                       'but API rate limiting (3.5 hours for 10k shows) is the real bottleneck. ' .
                       'Use --enable-fuzzy CLI flag only if absolutely needed.';
        }

        // Validate fuzzy threshold if present
        if (isset($matching['fuzzy_threshold'])) {
            $threshold = (int) $matching['fuzzy_threshold'];
            if ($threshold < 0 || $threshold > 100) {
                $errors[] = 'matching.fuzzy_threshold must be between 0 and 100';
            }
        }

        return $errors;
    }

    /**
     * Validate albums section.
     *
     * @param array $albums
     * @return array Validation errors
     */
    private function validateAlbumsSection(array $albums): array
    {
        $errors = [];
        $albumKeys = [];

        foreach ($albums as $index => $album) {
            if (empty($album['key'])) {
                $errors[] = sprintf('albums[%d].key is required', $index);
            } else {
                // Check for duplicate keys
                if (in_array($album['key'], $albumKeys, true)) {
                    $errors[] = sprintf('Duplicate album key: %s', $album['key']);
                }
                $albumKeys[] = $album['key'];

                // Validate URL key format
                if (!$this->isValidUrlKey($album['key'])) {
                    $errors[] = sprintf('albums[%d].key must contain only lowercase letters, numbers, and hyphens', $index);
                }
            }

            if (empty($album['name'])) {
                $errors[] = sprintf('albums[%d].name is required', $index);
            }

            // Validate album type if present
            if (isset($album['type']) && !in_array($album['type'], ['studio', 'live', 'compilation', 'virtual'], true)) {
                $errors[] = sprintf('albums[%d].type must be one of: studio, live, compilation, virtual', $index);
            }
        }

        return $errors;
    }

    /**
     * Validate tracks section.
     *
     * @param array $tracks
     * @return array Validation errors
     */
    private function validateTracksSection(array $tracks): array
    {
        $errors = [];
        $trackKeys = [];

        foreach ($tracks as $index => $track) {
            if (empty($track['key'])) {
                $errors[] = sprintf('tracks[%d].key is required', $index);
            } else {
                // Check for duplicate keys
                if (in_array($track['key'], $trackKeys, true)) {
                    $errors[] = sprintf('Duplicate track key: %s', $track['key']);
                }
                $trackKeys[] = $track['key'];

                // Validate URL key format
                if (!$this->isValidUrlKey($track['key'])) {
                    $errors[] = sprintf('tracks[%d].key must contain only lowercase letters, numbers, and hyphens', $index);
                }
            }

            if (empty($track['name'])) {
                $errors[] = sprintf('tracks[%d].name is required', $index);
            }

            // Validate aliases (if present, must not be empty)
            if (isset($track['aliases']) && is_array($track['aliases']) && empty($track['aliases'])) {
                $errors[] = sprintf('tracks[%d].aliases is empty - remove the aliases field or add values', $index);
            }

            // Validate type if present
            if (isset($track['type']) && !in_array($track['type'], ['original', 'cover', 'jam'], true)) {
                $errors[] = sprintf('tracks[%d].type must be one of: original, cover, jam', $index);
            }

            // Validate albums field (must be array if present)
            if (isset($track['albums']) && !is_array($track['albums'])) {
                $errors[] = sprintf('tracks[%d].albums must be an array', $index);
            }

            // Validate canonical_album (must exist and be in albums array)
            if (!empty($track['albums']) && is_array($track['albums'])) {
                if (empty($track['canonical_album'])) {
                    $errors[] = sprintf('tracks[%d].canonical_album is required when albums are defined', $index);
                } elseif (!in_array($track['canonical_album'], $track['albums'], true)) {
                    $errors[] = sprintf(
                        'tracks[%d].canonical_album "%s" must be one of the albums: %s',
                        $index,
                        $track['canonical_album'],
                        implode(', ', $track['albums'])
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Validate URL key format.
     *
     * @param string $urlKey
     * @return bool
     */
    private function isValidUrlKey(string $urlKey): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $urlKey);
    }
}

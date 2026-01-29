<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\StringNormalizerInterface;

/**
 * Normalizes strings for track matching.
 *
 * Operations:
 * - NFD decomposition + accent removal
 * - Unicode dash → ASCII hyphen
 * - Whitespace normalization
 * - Lowercase
 */
class StringNormalizer implements StringNormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize(string $input): string
    {
        if ($input === '') {
            return '';
        }

        // 1. NFD decomposition + remove accents
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($input, \Normalizer::NFD);
            if ($normalized !== false) {
                // Remove combining diacritical marks (Unicode block U+0300-U+036F)
                $input = preg_replace('/[\x{0300}-\x{036f}]/u', '', $normalized);
            }
        }

        // 2. Convert unicode dashes to ASCII hyphen
        // Em dash (—), En dash (–), Right arrow (→), Minus sign (−)
        $input = str_replace(
            ['—', '–', '→', '−', '‐', '‑', '‒', '―'],
            ['-', '-', '>', '-', '-', '-', '-', '-'],
            $input
        );

        // 3. Normalize whitespace (multiple spaces, tabs, newlines → single space)
        $input = preg_replace('/\s+/', ' ', trim($input));

        // 4. Lowercase (UTF-8 aware)
        return mb_strtolower($input, 'UTF-8');
    }
}

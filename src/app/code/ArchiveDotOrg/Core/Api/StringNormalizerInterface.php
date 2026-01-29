<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Normalizes strings for matching (Unicode, whitespace, case).
 */
interface StringNormalizerInterface
{
    /**
     * Normalize a string for matching.
     *
     * Operations:
     * - NFD decomposition + accent removal
     * - Unicode dash → ASCII hyphen
     * - Whitespace normalization
     * - Lowercase
     *
     * @param string $input Raw input string
     * @return string Normalized string
     */
    public function normalize(string $input): string;
}

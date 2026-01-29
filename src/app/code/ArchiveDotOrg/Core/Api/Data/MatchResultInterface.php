<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Result of a track matching operation.
 */
interface MatchResultInterface
{
    public const MATCH_EXACT = 'exact';
    public const MATCH_ALIAS = 'alias';
    public const MATCH_METAPHONE = 'metaphone';
    public const MATCH_FUZZY = 'fuzzy';

    /**
     * Get the matched canonical track key.
     *
     * @return string
     */
    public function getTrackKey(): string;

    /**
     * Get the match type (exact, alias, metaphone, fuzzy).
     *
     * @return string
     */
    public function getMatchType(): string;

    /**
     * Get the confidence score (0-100).
     *
     * @return int
     */
    public function getConfidence(): int;
}

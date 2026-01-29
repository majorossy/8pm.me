<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;

/**
 * Service for matching track names from Archive.org to canonical track definitions.
 *
 * Uses hybrid matching: exact → alias → metaphone → limited fuzzy
 */
interface TrackMatcherServiceInterface
{
    /**
     * Match a track name to a canonical track for an artist.
     *
     * @param string $trackName Raw track name from Archive.org
     * @param string $artistKey Artist URL key (e.g., "lettuce")
     * @return MatchResultInterface|null Match result or null if no match
     */
    public function match(string $trackName, string $artistKey): ?MatchResultInterface;

    /**
     * Build matching indexes for an artist.
     *
     * @param string $artistKey Artist URL key
     * @return void
     */
    public function buildIndexes(string $artistKey): void;

    /**
     * Clear matching indexes to free memory.
     *
     * @param string|null $artistKey Specific artist or null for all
     * @return void
     */
    public function clearIndexes(?string $artistKey = null): void;
}

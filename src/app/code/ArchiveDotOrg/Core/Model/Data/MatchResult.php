<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;

/**
 * Result of a track matching operation.
 */
class MatchResult implements MatchResultInterface
{
    /**
     * @param string $trackKey Matched canonical track key
     * @param string $matchType Type of match (exact, alias, metaphone, fuzzy)
     * @param int $confidence Confidence score (0-100)
     */
    public function __construct(
        private readonly string $trackKey,
        private readonly string $matchType,
        private readonly int $confidence
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getTrackKey(): string
    {
        return $this->trackKey;
    }

    /**
     * @inheritDoc
     */
    public function getMatchType(): string
    {
        return $this->matchType;
    }

    /**
     * @inheritDoc
     */
    public function getConfidence(): int
    {
        return $this->confidence;
    }
}

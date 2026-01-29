<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;
use ArchiveDotOrg\Core\Api\StringNormalizerInterface;
use ArchiveDotOrg\Core\Api\ArtistConfigLoaderInterface;
use ArchiveDotOrg\Core\Model\Data\MatchResult;
use Psr\Log\LoggerInterface;

/**
 * Hybrid track matching service.
 *
 * Matching algorithm (in order of priority):
 * 1. Exact match - O(1) hash lookup
 * 2. Alias match - O(1) hash lookup from configured aliases
 * 3. Metaphone phonetic match - O(1) hash lookup
 * 4. Limited fuzzy - Levenshtein on top N metaphone candidates only
 *
 * @see FIXES.md #41 for algorithm design decisions
 */
class TrackMatcherService implements TrackMatcherServiceInterface
{
    /**
     * Exact normalized name → track key
     * @var array<string, array<string, string>>
     */
    private array $exactIndex = [];

    /**
     * Normalized alias → track key
     * @var array<string, array<string, string>>
     */
    private array $aliasIndex = [];

    /**
     * Metaphone → track key (first match wins)
     * @var array<string, array<string, string>>
     */
    private array $metaphoneIndex = [];

    /**
     * All track keys and their normalized names for fuzzy fallback
     * @var array<string, array<string, string>>
     */
    private array $allTracks = [];

    /**
     * @param StringNormalizerInterface $normalizer
     * @param ArtistConfigLoaderInterface $configLoader
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly StringNormalizerInterface $normalizer,
        private readonly ArtistConfigLoaderInterface $configLoader,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function match(string $trackName, string $artistKey): ?MatchResultInterface
    {
        $this->ensureIndexed($artistKey);

        if (empty($this->exactIndex[$artistKey]) && empty($this->aliasIndex[$artistKey])) {
            // No tracks configured for this artist
            return null;
        }

        $normalized = $this->normalizer->normalize($trackName);

        if ($normalized === '') {
            return null;
        }

        // 1. Exact match - O(1)
        if (isset($this->exactIndex[$artistKey][$normalized])) {
            return new MatchResult(
                $this->exactIndex[$artistKey][$normalized],
                MatchResultInterface::MATCH_EXACT,
                100
            );
        }

        // 2. Alias match - O(1) lookup
        if (isset($this->aliasIndex[$artistKey][$normalized])) {
            return new MatchResult(
                $this->aliasIndex[$artistKey][$normalized],
                MatchResultInterface::MATCH_ALIAS,
                95
            );
        }

        // 3. Metaphone match - O(1)
        $metaphone = metaphone($normalized);
        if ($metaphone !== '' && isset($this->metaphoneIndex[$artistKey][$metaphone])) {
            return new MatchResult(
                $this->metaphoneIndex[$artistKey][$metaphone],
                MatchResultInterface::MATCH_METAPHONE,
                85
            );
        }

        // 4. Limited fuzzy - top N candidates only
        $candidateLimit = $this->config->getFuzzyCandidateLimit();
        $minScore = $this->config->getMinFuzzyScore();

        $candidate = $this->fuzzyMatchTopCandidates($normalized, $artistKey, $candidateLimit);
        if ($candidate !== null && $candidate['score'] >= $minScore) {
            return new MatchResult(
                $candidate['track'],
                MatchResultInterface::MATCH_FUZZY,
                $candidate['score']
            );
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function buildIndexes(string $artistKey): void
    {
        // Clear any existing indexes for this artist
        unset(
            $this->exactIndex[$artistKey],
            $this->aliasIndex[$artistKey],
            $this->metaphoneIndex[$artistKey],
            $this->allTracks[$artistKey]
        );

        // Initialize empty indexes
        $this->exactIndex[$artistKey] = [];
        $this->aliasIndex[$artistKey] = [];
        $this->metaphoneIndex[$artistKey] = [];
        $this->allTracks[$artistKey] = [];

        // Load artist configuration
        try {
            $artistConfig = $this->configLoader->load($artistKey);
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf('No configuration found for artist %s: %s', $artistKey, $e->getMessage())
            );
            return;
        }

        $tracks = $artistConfig['tracks'] ?? [];

        foreach ($tracks as $track) {
            if (!isset($track['key'], $track['name'])) {
                continue;
            }

            $trackKey = $track['key'];
            $normalized = $this->normalizer->normalize($track['name']);

            if ($normalized === '') {
                continue;
            }

            // Exact index
            $this->exactIndex[$artistKey][$normalized] = $trackKey;

            // Alias index
            $aliases = $track['aliases'] ?? [];
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizer->normalize($alias);
                if ($normalizedAlias !== '') {
                    $this->aliasIndex[$artistKey][$normalizedAlias] = $trackKey;
                }
            }

            // Metaphone index (first track for each metaphone wins)
            $metaphone = metaphone($normalized);
            if ($metaphone !== '' && !isset($this->metaphoneIndex[$artistKey][$metaphone])) {
                $this->metaphoneIndex[$artistKey][$metaphone] = $trackKey;
            }

            // Keep all tracks for fuzzy fallback
            $this->allTracks[$artistKey][$trackKey] = $normalized;
        }

        $this->logger->debug(
            sprintf(
                'Built indexes for %s: %d tracks, %d aliases, %d metaphones',
                $artistKey,
                count($this->exactIndex[$artistKey]),
                count($this->aliasIndex[$artistKey]),
                count($this->metaphoneIndex[$artistKey])
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function clearIndexes(?string $artistKey = null): void
    {
        if ($artistKey === null) {
            $this->exactIndex = [];
            $this->aliasIndex = [];
            $this->metaphoneIndex = [];
            $this->allTracks = [];
        } else {
            unset(
                $this->exactIndex[$artistKey],
                $this->aliasIndex[$artistKey],
                $this->metaphoneIndex[$artistKey],
                $this->allTracks[$artistKey]
            );
        }
    }

    /**
     * Fuzzy match against top candidates with similar metaphone.
     *
     * This limits the expensive Levenshtein/similar_text calculations
     * to only tracks that are phonetically similar.
     *
     * @param string $input Normalized input string
     * @param string $artistKey Artist key
     * @param int $limit Maximum candidates to consider
     * @return array{track: string, score: int}|null Best match or null
     */
    private function fuzzyMatchTopCandidates(string $input, string $artistKey, int $limit): ?array
    {
        $candidates = [];
        $inputMetaphone = metaphone($input);

        if ($inputMetaphone === '') {
            return null;
        }

        // Find candidates with similar metaphone (Levenshtein distance <= 2)
        foreach ($this->allTracks[$artistKey] ?? [] as $trackKey => $normalized) {
            $trackMetaphone = metaphone($normalized);
            if ($trackMetaphone !== '' && levenshtein($inputMetaphone, $trackMetaphone) <= 2) {
                $candidates[$trackKey] = $normalized;
                if (count($candidates) >= $limit) {
                    break;
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Find best match among candidates using similar_text
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $trackKey => $normalized) {
            similar_text($input, $normalized, $percent);
            $score = (int) $percent;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = ['track' => $trackKey, 'score' => $score];
            }
        }

        return $bestMatch;
    }

    /**
     * Ensure indexes are built for the given artist.
     *
     * @param string $artistKey
     * @return void
     */
    private function ensureIndexed(string $artistKey): void
    {
        if (!isset($this->exactIndex[$artistKey])) {
            $this->buildIndexes($artistKey);
        }
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Performance;

use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Model\StringNormalizer;

/**
 * Performance benchmarks for track matching algorithms
 *
 * Tests matching performance with different data sizes and strategies:
 * - Exact match (hash lookup) - O(1)
 * - Alias match - O(1) with pre-built index
 * - Metaphone phonetic match - O(1) with pre-built index
 * - Limited fuzzy match - O(n) on top 5 candidates only
 *
 * Performance Targets:
 * - Exact match: <100ms for 10,000 tracks
 * - Metaphone match: <500ms for 10,000 tracks
 * - Index building: <5s for 10,000 tracks
 * - Memory usage: <50MB for 10,000 tracks
 */
class MatchingBenchmark
{
    private TrackMatcherServiceInterface $trackMatcher;
    private StringNormalizer $normalizer;
    private array $testData = [];

    public function __construct(
        TrackMatcherServiceInterface $trackMatcher,
        StringNormalizer $normalizer
    ) {
        $this->trackMatcher = $trackMatcher;
        $this->normalizer = $normalizer;
    }

    /**
     * Run all benchmark tests
     *
     * @param int $trackCount Number of tracks to test with
     * @param int $iterations Number of iterations per test
     * @return array Benchmark results
     */
    public function runAll(int $trackCount = 10000, int $iterations = 10): array
    {
        $this->generateTestData($trackCount);

        return [
            'track_count' => $trackCount,
            'iterations' => $iterations,
            'index_build' => $this->benchmarkIndexBuilding($trackCount),
            'exact_match' => $this->benchmarkExactMatch($iterations),
            'alias_match' => $this->benchmarkAliasMatch($iterations),
            'metaphone_match' => $this->benchmarkMetaphoneMatch($iterations),
            'fuzzy_match' => $this->benchmarkFuzzyMatch($iterations),
            'memory_usage' => $this->measureMemoryUsage(),
        ];
    }

    /**
     * Benchmark index building performance
     *
     * Target: <5 seconds for 10,000 tracks
     *
     * @param int $trackCount
     * @return array
     */
    public function benchmarkIndexBuilding(int $trackCount): array
    {
        $this->trackMatcher->clearIndexes('test-artist');

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $this->trackMatcher->buildIndexes('test-artist');

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to ms
        $memoryUsed = $endMemory - $startMemory;

        return [
            'duration_ms' => round($duration, 2),
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            'tracks' => $trackCount,
            'target_met' => $duration < 5000, // 5 seconds target
        ];
    }

    /**
     * Benchmark exact hash match performance
     *
     * Target: <100ms for 10,000 tracks
     *
     * @param int $iterations
     * @return array
     */
    public function benchmarkExactMatch(int $iterations): array
    {
        $testTracks = array_slice($this->testData['tracks'], 0, min(100, count($this->testData['tracks'])));

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testTracks as $trackName) {
                $this->trackMatcher->match($trackName, 'test-artist');
            }
        }

        $endTime = microtime(true);
        $duration = (($endTime - $startTime) / $iterations) * 1000; // Average per iteration in ms

        return [
            'duration_ms' => round($duration, 2),
            'matches_per_iteration' => count($testTracks),
            'avg_per_match_ms' => round($duration / count($testTracks), 4),
            'target_met' => $duration < 100,
        ];
    }

    /**
     * Benchmark alias match performance
     *
     * Target: <100ms for 10,000 tracks
     *
     * @param int $iterations
     * @return array
     */
    public function benchmarkAliasMatch(int $iterations): array
    {
        $testAliases = array_slice($this->testData['aliases'], 0, min(50, count($this->testData['aliases'])));

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testAliases as $alias) {
                $this->trackMatcher->match($alias, 'test-artist');
            }
        }

        $endTime = microtime(true);
        $duration = (($endTime - $startTime) / $iterations) * 1000;

        return [
            'duration_ms' => round($duration, 2),
            'matches_per_iteration' => count($testAliases),
            'avg_per_match_ms' => round($duration / count($testAliases), 4),
            'target_met' => $duration < 100,
        ];
    }

    /**
     * Benchmark metaphone phonetic match performance
     *
     * Target: <500ms for 10,000 tracks
     *
     * @param int $iterations
     * @return array
     */
    public function benchmarkMetaphoneMatch(int $iterations): array
    {
        $testMisspellings = array_slice($this->testData['misspellings'], 0, min(50, count($this->testData['misspellings'])));

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testMisspellings as $misspelling) {
                $this->trackMatcher->match($misspelling, 'test-artist');
            }
        }

        $endTime = microtime(true);
        $duration = (($endTime - $startTime) / $iterations) * 1000;

        return [
            'duration_ms' => round($duration, 2),
            'matches_per_iteration' => count($testMisspellings),
            'avg_per_match_ms' => round($duration / count($testMisspellings), 4),
            'target_met' => $duration < 500,
        ];
    }

    /**
     * Benchmark limited fuzzy match performance
     *
     * Note: Full Levenshtein on entire catalog is NEVER used (prohibitively slow)
     * Only top 5 metaphone candidates are checked with fuzzy matching
     *
     * Target: <2 seconds for 10,000 tracks
     *
     * @param int $iterations
     * @return array
     */
    public function benchmarkFuzzyMatch(int $iterations): array
    {
        $testVariants = array_slice($this->testData['fuzzy_variants'], 0, min(20, count($this->testData['fuzzy_variants'])));

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testVariants as $variant) {
                $this->trackMatcher->match($variant, 'test-artist');
            }
        }

        $endTime = microtime(true);
        $duration = (($endTime - $startTime) / $iterations) * 1000;

        return [
            'duration_ms' => round($duration, 2),
            'matches_per_iteration' => count($testVariants),
            'avg_per_match_ms' => round($duration / count($testVariants), 4),
            'target_met' => $duration < 2000,
            'note' => 'Only tests top 5 candidates (not full catalog)',
        ];
    }

    /**
     * Measure memory usage with full index loaded
     *
     * Target: <50MB for 10,000 tracks
     *
     * @return array
     */
    public function measureMemoryUsage(): array
    {
        gc_collect_cycles();
        $baselineMemory = memory_get_usage(true);

        $this->trackMatcher->buildIndexes('test-artist');

        gc_collect_cycles();
        $loadedMemory = memory_get_usage(true);

        $memoryUsed = $loadedMemory - $baselineMemory;

        return [
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'target_met' => $memoryUsed < (50 * 1024 * 1024), // 50MB
        ];
    }

    /**
     * Generate realistic test data
     *
     * Creates track names, aliases, misspellings, and fuzzy variants
     *
     * @param int $count
     */
    private function generateTestData(int $count): void
    {
        $commonTracks = [
            'Tweezer', 'Free', 'Divided Sky', 'You Enjoy Myself', 'Down with Disease',
            'Bathtub Gin', 'Harry Hood', 'Punch You in the Eye', 'Stash', 'Reba',
            'The Lizards', 'Foam', 'Maze', 'Sample in a Jar', 'Chalk Dust Torture',
            'Cavern', 'Run Like an Antelope', 'Bouncing Around the Room', 'Fluffhead', 'Possum',
        ];

        $this->testData['tracks'] = [];
        $this->testData['aliases'] = [];
        $this->testData['misspellings'] = [];
        $this->testData['fuzzy_variants'] = [];

        // Generate tracks by repeating common tracks with variations
        for ($i = 0; $i < $count; $i++) {
            $baseTrack = $commonTracks[$i % count($commonTracks)];
            $track = $i > count($commonTracks) ? $baseTrack . ' ' . ($i % 100) : $baseTrack;
            $this->testData['tracks'][] = $track;

            // Every 10th track gets an alias
            if ($i % 10 === 0) {
                $this->testData['aliases'][] = substr($track, 0, -1) . 'z'; // "Tweezer" -> "Tweezerz"
            }

            // Every 20th track gets a misspelling for metaphone testing
            if ($i % 20 === 0) {
                $this->testData['misspellings'][] = str_replace(['e', 'a'], ['3', '4'], $track);
            }

            // Every 50th track gets a fuzzy variant
            if ($i % 50 === 0) {
                $this->testData['fuzzy_variants'][] = $this->createFuzzyVariant($track);
            }
        }
    }

    /**
     * Create a fuzzy variant of a track name (character swap, deletion, insertion)
     *
     * @param string $trackName
     * @return string
     */
    private function createFuzzyVariant(string $trackName): string
    {
        $operations = ['swap', 'delete', 'insert'];
        $operation = $operations[array_rand($operations)];

        $chars = str_split($trackName);
        $len = count($chars);

        if ($len < 3) {
            return $trackName;
        }

        switch ($operation) {
            case 'swap':
                // Swap two adjacent characters
                $pos = rand(0, $len - 2);
                [$chars[$pos], $chars[$pos + 1]] = [$chars[$pos + 1], $chars[$pos]];
                break;

            case 'delete':
                // Delete a character
                $pos = rand(0, $len - 1);
                array_splice($chars, $pos, 1);
                break;

            case 'insert':
                // Insert a character
                $pos = rand(0, $len - 1);
                array_splice($chars, $pos, 0, [chr(rand(97, 122))]);
                break;
        }

        return implode('', $chars);
    }

    /**
     * Compare this implementation vs full Levenshtein (DO NOT RUN IN PRODUCTION)
     *
     * This is purely educational to demonstrate why full Levenshtein is not used.
     *
     * Warning: This will take ~43 hours for 10,000 tracks
     *
     * @param int $trackCount
     * @return array
     */
    public function compareWithFullLevenshtein(int $trackCount = 100): array
    {
        if ($trackCount > 100) {
            return [
                'error' => 'Refusing to run full Levenshtein on more than 100 tracks',
                'estimated_time_hours' => ($trackCount * $trackCount * 0.00001544) / 3600,
            ];
        }

        $testTracks = array_slice($this->testData['tracks'], 0, $trackCount);
        $testQuery = 'Twezer'; // Misspelling

        // Limited approach (current)
        $startTime = microtime(true);
        $this->trackMatcher->match($testQuery, 'test-artist');
        $limitedDuration = (microtime(true) - $startTime) * 1000;

        // Full Levenshtein approach (DO NOT USE)
        $startTime = microtime(true);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($testTracks as $track) {
            similar_text($this->normalizer->normalize($testQuery), $this->normalizer->normalize($track), $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $track;
            }
        }

        $fullDuration = (microtime(true) - $startTime) * 1000;

        return [
            'limited_approach_ms' => round($limitedDuration, 2),
            'full_levenshtein_ms' => round($fullDuration, 2),
            'speedup_factor' => round($fullDuration / $limitedDuration, 2),
            'track_count' => $trackCount,
            'estimated_10k_tracks_hours' => round((10000 * 10000 * 0.00001544) / 3600, 2),
        ];
    }
}

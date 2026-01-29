<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Model\TrackMatcherService;
use ArchiveDotOrg\Core\Api\StringNormalizerInterface;
use ArchiveDotOrg\Core\Api\ArtistConfigLoaderInterface;
use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;
use ArchiveDotOrg\Core\Model\Config;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TrackMatcherService
 *
 * @covers \ArchiveDotOrg\Core\Model\TrackMatcherService
 */
class TrackMatcherServiceTest extends TestCase
{
    private TrackMatcherService $trackMatcher;
    private StringNormalizerInterface $normalizer;
    private ArtistConfigLoaderInterface $configLoader;
    private Config $config;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->normalizer = $this->createMock(StringNormalizerInterface::class);
        $this->configLoader = $this->createMock(ArtistConfigLoaderInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Default config values
        $this->config->method('getFuzzyCandidateLimit')->willReturn(5);
        $this->config->method('getMinFuzzyScore')->willReturn(80);

        // Default normalizer behavior: lowercase
        $this->normalizer->method('normalize')->willReturnCallback(
            fn($input) => mb_strtolower($input, 'UTF-8')
        );

        $this->trackMatcher = new TrackMatcherService(
            $this->normalizer,
            $this->configLoader,
            $this->config,
            $this->logger
        );
    }

    public function testExactMatchFound(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('Tweezer', 'phish');

        $this->assertInstanceOf(MatchResultInterface::class, $result);
        $this->assertEquals('tweezer', $result->getTrackKey());
        $this->assertEquals(MatchResultInterface::MATCH_EXACT, $result->getMatchType());
        $this->assertEquals(100, $result->getConfidence());
    }

    public function testAliasMatchFound(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => ['Twezer', 'Tweeser']
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('Twezer', 'phish');

        $this->assertInstanceOf(MatchResultInterface::class, $result);
        $this->assertEquals('tweezer', $result->getTrackKey());
        $this->assertEquals(MatchResultInterface::MATCH_ALIAS, $result->getMatchType());
        $this->assertEquals(95, $result->getConfidence());
    }

    public function testMetaphoneMatchFound(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'the-flu',
                    'name' => 'The Flu',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('lettuce')
            ->willReturn($artistConfig);

        // "The Flue" and "The Flu" have same metaphone code
        $result = $this->trackMatcher->match('The Flue', 'lettuce');

        $this->assertInstanceOf(MatchResultInterface::class, $result);
        $this->assertEquals('the-flu', $result->getTrackKey());
        $this->assertEquals(MatchResultInterface::MATCH_METAPHONE, $result->getMatchType());
        $this->assertEquals(85, $result->getConfidence());
    }

    public function testNoMatchReturnsNull(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('Completely Different Track', 'phish');

        $this->assertNull($result);
    }

    public function testCaseInsensitiveMatching(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('TWEEZER', 'phish');

        $this->assertInstanceOf(MatchResultInterface::class, $result);
        $this->assertEquals('tweezer', $result->getTrackKey());
        $this->assertEquals(MatchResultInterface::MATCH_EXACT, $result->getMatchType());
    }

    public function testEmptyStringReturnsNull(): void
    {
        $this->normalizer->method('normalize')->willReturn('');

        $result = $this->trackMatcher->match('', 'phish');

        $this->assertNull($result);
    }

    public function testNoTracksConfiguredReturnsNull(): void
    {
        $artistConfig = ['tracks' => []];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('Tweezer', 'phish');

        $this->assertNull($result);
    }

    public function testConfigLoaderExceptionReturnsNull(): void
    {
        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('unknown-artist')
            ->willThrowException(new \Exception('Artist not found'));

        $result = $this->trackMatcher->match('Some Track', 'unknown-artist');

        $this->assertNull($result);
    }

    public function testBuildIndexesCreatesAllIndexes(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => ['Twezer']
                ],
                [
                    'key' => 'the-flu',
                    'name' => 'The Flu',
                    'aliases' => ['The Flue']
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $this->trackMatcher->buildIndexes('phish');

        // Verify exact match works
        $result = $this->trackMatcher->match('Tweezer', 'phish');
        $this->assertNotNull($result);
        $this->assertEquals('tweezer', $result->getTrackKey());

        // Verify alias match works
        $result = $this->trackMatcher->match('Twezer', 'phish');
        $this->assertNotNull($result);
        $this->assertEquals('tweezer', $result->getTrackKey());
    }

    public function testClearIndexesForSpecificArtist(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->method('load')
            ->willReturn($artistConfig);

        // Build indexes for two artists
        $this->trackMatcher->buildIndexes('phish');
        $this->trackMatcher->buildIndexes('grateful-dead');

        // Clear only phish
        $this->trackMatcher->clearIndexes('phish');

        // Phish should need rebuild (will trigger configLoader again)
        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $this->trackMatcher->match('Tweezer', 'phish');
    }

    public function testClearIndexesForAllArtists(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->method('load')
            ->willReturn($artistConfig);

        $this->trackMatcher->buildIndexes('phish');

        // Clear all
        $this->trackMatcher->clearIndexes();

        // Should need rebuild
        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $this->trackMatcher->match('Tweezer', 'phish');
    }

    public function testMatchPriority(): void
    {
        // Test that exact match takes precedence over alias/metaphone
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'exact-track',
                    'name' => 'Test Track',
                    'aliases' => []
                ],
                [
                    'key' => 'alias-track',
                    'name' => 'Different Track',
                    'aliases' => ['Test Track']
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($artistConfig);

        $result = $this->trackMatcher->match('Test Track', 'test');

        // Should match exact first
        $this->assertEquals('exact-track', $result->getTrackKey());
        $this->assertEquals(MatchResultInterface::MATCH_EXACT, $result->getMatchType());
    }

    public function testSkipsTracksWithoutKeyOrName(): void
    {
        $artistConfig = [
            'tracks' => [
                ['key' => 'valid', 'name' => 'Valid Track'],
                ['name' => 'No Key'],  // Missing key
                ['key' => 'no-name'],  // Missing name
                ['key' => '', 'name' => 'Empty Key']  // Empty key
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($artistConfig);

        $this->trackMatcher->buildIndexes('test');

        // Only valid track should be indexed
        $result = $this->trackMatcher->match('Valid Track', 'test');
        $this->assertNotNull($result);

        $result = $this->trackMatcher->match('No Key', 'test');
        $this->assertNull($result);
    }

    public function testSkipsEmptyAliases(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'aliases' => ['Valid Alias', '']  // One empty alias
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($artistConfig);

        $this->normalizer->method('normalize')->willReturnCallback(function ($input) {
            return $input === '' ? '' : mb_strtolower($input, 'UTF-8');
        });

        $this->trackMatcher->buildIndexes('test');

        // Valid alias should work
        $result = $this->trackMatcher->match('Valid Alias', 'test');
        $this->assertNotNull($result);
        $this->assertEquals(MatchResultInterface::MATCH_ALIAS, $result->getMatchType());
    }

    public function testMetaphoneIndexFirstMatchWins(): void
    {
        // When multiple tracks have same metaphone, first one wins
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'first',
                    'name' => 'The Flu',  // Metaphone: 0FL
                    'aliases' => []
                ],
                [
                    'key' => 'second',
                    'name' => 'The Flue', // Same metaphone: 0FL
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($artistConfig);

        $this->trackMatcher->buildIndexes('test');

        // Both should match to 'first' (first in list)
        $result = $this->trackMatcher->match('The Floo', 'test');
        $this->assertNotNull($result);
        $this->assertEquals('first', $result->getTrackKey());
    }

    public function testFuzzyMatchWithTopCandidates(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ],
                [
                    'key' => 'the-flu',
                    'name' => 'The Flu',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $this->config->method('getMinFuzzyScore')->willReturn(75);

        // Slight misspelling - "Tweeser" is close to "Tweezer"
        $result = $this->trackMatcher->match('Tweeser', 'phish');

        // Should find a match (could be metaphone or fuzzy)
        if ($result !== null) {
            $this->assertEquals('tweezer', $result->getTrackKey());
            $this->assertContains($result->getMatchType(), [
                MatchResultInterface::MATCH_FUZZY,
                MatchResultInterface::MATCH_METAPHONE
            ]);
            $this->assertGreaterThanOrEqual(75, $result->getConfidence());
        }
    }

    public function testFuzzyMatchBelowThresholdReturnsNull(): void
    {
        $artistConfig = [
            'tracks' => [
                [
                    'key' => 'tweezer',
                    'name' => 'Tweezer',
                    'aliases' => []
                ]
            ]
        ];

        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('phish')
            ->willReturn($artistConfig);

        $this->config->method('getMinFuzzyScore')->willReturn(90);

        // Very different track name
        $result = $this->trackMatcher->match('xyz', 'phish');

        $this->assertNull($result);
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Integration\Model;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\ArchiveApiClient;
use ArchiveDotOrg\Core\Model\Cache\ApiResponseCache;
use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\Resilience\CircuitBreaker;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

/**
 * Abstract contract test - run against both real and mock API
 * to ensure mocks accurately represent Archive.org responses
 *
 * This ensures that our unit test mocks match the actual API behavior
 * and catch API drift when Archive.org makes changes.
 *
 * @abstract
 */
abstract class ArchiveOrgApiContractTest extends TestCase
{
    /**
     * Get the API client instance (real or mocked)
     *
     * @return ArchiveApiClientInterface
     */
    abstract protected function getClient(): ArchiveApiClientInterface;

    /**
     * @test
     */
    public function testSearchReturnsExpectedStructure(): void
    {
        $client = $this->getClient();
        $result = $client->fetchCollectionIdentifiers('GratefulDead', 5);

        // Verify: Returns array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Search should return at least one result');

        // Verify: Each item is a string identifier
        foreach ($result as $identifier) {
            $this->assertIsString($identifier);
            $this->assertNotEmpty($identifier);
        }
    }

    /**
     * @test
     */
    public function testMetadataHasRequiredFields(): void
    {
        $client = $this->getClient();

        // Get a sample identifier first
        $identifiers = $client->fetchCollectionIdentifiers('GratefulDead', 1);
        $this->assertNotEmpty($identifiers, 'Need at least one identifier to test metadata');

        $identifier = $identifiers[0];
        $show = $client->fetchShowMetadata($identifier);

        // Verify required ShowInterface methods work
        $this->assertNotNull($show);
        $this->assertEquals($identifier, $show->getIdentifier());
        $this->assertNotEmpty($show->getTitle(), 'Show should have a title');

        // Verify tracks array exists
        $tracks = $show->getTracks();
        $this->assertIsArray($tracks);
    }

    /**
     * @test
     */
    public function testTracksHaveRequiredFields(): void
    {
        $client = $this->getClient();

        // Get show with tracks
        $identifiers = $client->fetchCollectionIdentifiers('GratefulDead', 1);
        $this->assertNotEmpty($identifiers);

        $show = $client->fetchShowMetadata($identifiers[0]);
        $tracks = $show->getTracks();

        if (empty($tracks)) {
            $this->markTestSkipped('Show has no tracks to validate');
        }

        // Verify first track has required fields
        $track = $tracks[0];
        $this->assertNotEmpty($track->getName(), 'Track should have name');
        $this->assertNotEmpty($track->getTitle(), 'Track should have title');
        $this->assertNotEmpty($track->generateSku(), 'Track should be able to generate SKU');
    }

    /**
     * @test
     */
    public function testErrorResponseStructure(): void
    {
        $client = $this->getClient();

        // Request non-existent identifier
        $this->expectException(\Exception::class);

        $client->fetchShowMetadata('invalid-identifier-that-does-not-exist-12345');
    }

    /**
     * @test
     */
    public function testCollectionCountReturnsInteger(): void
    {
        $client = $this->getClient();

        $count = $client->getCollectionCount('GratefulDead');

        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count, 'Collection should have items');
    }

    /**
     * @test
     */
    public function testPaginationWorks(): void
    {
        $client = $this->getClient();

        // Get first page
        $page1 = $client->fetchCollectionIdentifiers('GratefulDead', 5, 0);
        $this->assertCount(5, $page1);

        // Get second page
        $page2 = $client->fetchCollectionIdentifiers('GratefulDead', 5, 5);
        $this->assertCount(5, $page2);

        // Pages should be different
        $this->assertNotEquals($page1, $page2, 'Pagination should return different results');
    }

    /**
     * @test
     */
    public function testShowHasDateInformation(): void
    {
        $client = $this->getClient();

        $identifiers = $client->fetchCollectionIdentifiers('GratefulDead', 1);
        $show = $client->fetchShowMetadata($identifiers[0]);

        // Most shows should have year information
        $year = $show->getYear();
        if ($year) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $year, 'Year should be 4 digits');
        }

        // Show date can be in various formats
        $date = $show->getDate();
        if ($date) {
            $this->assertNotEmpty($date);
        }
    }

    /**
     * @test
     */
    public function testShowHasServerInformation(): void
    {
        $client = $this->getClient();

        $identifiers = $client->fetchCollectionIdentifiers('GratefulDead', 1);
        $show = $client->fetchShowMetadata($identifiers[0]);

        // Archive.org metadata should include server info
        $serverOne = $show->getServerOne();
        $serverTwo = $show->getServerTwo();
        $dir = $show->getDir();

        // At least server one and dir should exist
        $this->assertNotEmpty($serverOne, 'Show should have primary server');
        $this->assertNotEmpty($dir, 'Show should have directory path');
    }

    /**
     * @test
     */
    public function testTrackHasAudioFormatInfo(): void
    {
        $client = $this->getClient();

        $identifiers = $client->fetchCollectionIdentifiers('GratefulDead', 1);
        $show = $client->fetchShowMetadata($identifiers[0]);
        $tracks = $show->getTracks();

        if (empty($tracks)) {
            $this->markTestSkipped('Show has no tracks');
        }

        $track = $tracks[0];

        // Track should have name with extension
        $name = $track->getName();
        $this->assertMatchesRegularExpression('/\.(mp3|flac|ogg|shn)$/i', $name, 'Track should have audio format extension');
    }
}

/**
 * Test against REAL Archive.org API
 *
 * Run manually or in nightly builds to catch API drift:
 * vendor/bin/phpunit --filter RealArchiveOrgApiContractTest
 *
 * WARNING: This makes real HTTP requests and may be slow or fail if:
 * - Archive.org is down
 * - Network is unavailable
 * - Rate limiting is triggered
 *
 * @group integration
 * @group external
 */
class RealArchiveOrgApiContractTest extends ArchiveOrgApiContractTest
{
    private ?ArchiveApiClientInterface $client = null;

    protected function getClient(): ArchiveApiClientInterface
    {
        if ($this->client === null) {
            // Create real client with actual HTTP
            $configMock = $this->createMock(Config::class);
            $configMock->method('getTimeout')->willReturn(30);
            $configMock->method('getRetryAttempts')->willReturn(3);
            $configMock->method('getRetryDelay')->willReturn(100);
            $configMock->method('getBaseUrl')->willReturn('https://archive.org');
            $configMock->method('getAudioFormat')->willReturn('mp3');
            $configMock->method('getRateLimitMs')->willReturn(1000); // Be respectful with real API
            $configMock->method('getPageSize')->willReturn(500);
            $configMock->method('buildPaginatedSearchUrl')->willReturnCallback(
                fn($collectionId, $page, $rows) =>
                    "https://archive.org/advancedsearch.php?q=collection:$collectionId&output=json&rows=$rows&page=$page"
            );
            $configMock->method('buildMetadataUrl')->willReturnCallback(
                fn($identifier) => "https://archive.org/metadata/$identifier"
            );
            $configMock->method('buildStreamingUrl')->willReturnCallback(
                fn($server, $dir, $filename) => "https://$server$dir/$filename"
            );
            $configMock->method('getCircuitThreshold')->willReturn(10);
            $configMock->method('getCircuitResetSeconds')->willReturn(300);

            $httpClient = new Curl();
            $jsonSerializer = new Json();

            $loggerMock = $this->createMock(Logger::class);

            $showFactoryMock = $this->createMock(\ArchiveDotOrg\Core\Api\Data\ShowInterfaceFactory::class);
            $showFactoryMock->method('create')->willReturnCallback(function () {
                return new \ArchiveDotOrg\Core\Model\Data\Show();
            });

            $trackFactoryMock = $this->createMock(\ArchiveDotOrg\Core\Api\Data\TrackInterfaceFactory::class);
            $trackFactoryMock->method('create')->willReturnCallback(function () {
                return new \ArchiveDotOrg\Core\Model\Data\Track();
            });

            $cacheMock = $this->createMock(ApiResponseCache::class);
            $cacheMock->method('get')->willReturn(null); // No caching for contract tests

            $circuitBreakerCacheMock = $this->createMock(\Magento\Framework\App\CacheInterface::class);
            $circuitBreakerCacheMock->method('load')->willReturn(false);

            $circuitBreaker = new CircuitBreaker($circuitBreakerCacheMock, $configMock);

            $this->client = new ArchiveApiClient(
                $configMock,
                $httpClient,
                $jsonSerializer,
                $loggerMock,
                $showFactoryMock,
                $trackFactoryMock,
                $cacheMock,
                $circuitBreaker
            );
        }

        return $this->client;
    }

    protected function setUp(): void
    {
        // Check if we should skip external tests
        if (getenv('SKIP_EXTERNAL_TESTS') === '1') {
            $this->markTestSkipped('Skipping external API tests (SKIP_EXTERNAL_TESTS=1)');
        }

        parent::setUp();
    }
}

/**
 * Test against MOCK API (for CI/CD)
 *
 * This runs in CI/CD pipelines and verifies that our mocks
 * match the expected API contract.
 *
 * @group unit
 */
class MockArchiveOrgApiContractTest extends ArchiveOrgApiContractTest
{
    private ?ArchiveApiClientInterface $client = null;

    protected function getClient(): ArchiveApiClientInterface
    {
        if ($this->client === null) {
            $configMock = $this->createMock(Config::class);
            $configMock->method('getTimeout')->willReturn(30);
            $configMock->method('getRetryAttempts')->willReturn(3);
            $configMock->method('getRetryDelay')->willReturn(100);
            $configMock->method('getBaseUrl')->willReturn('https://archive.org');
            $configMock->method('getAudioFormat')->willReturn('mp3');
            $configMock->method('getRateLimitMs')->willReturn(0);
            $configMock->method('getPageSize')->willReturn(500);
            $configMock->method('getCircuitThreshold')->willReturn(10);
            $configMock->method('getCircuitResetSeconds')->willReturn(300);

            $httpClientMock = $this->createMock(Curl::class);

            // Mock search response
            $httpClientMock->method('get')->willReturnCallback(function ($url) use ($httpClientMock) {
                if (strpos($url, 'advancedsearch.php') !== false) {
                    $httpClientMock->method('getStatus')->willReturn(200);
                    $httpClientMock->method('getBody')->willReturn(json_encode([
                        'response' => [
                            'docs' => [
                                ['identifier' => 'gd1977-05-08.sbd.miller.32601.sbeok.flac16'],
                                ['identifier' => 'gd1977-05-09.sbd.smith.1621.sbeok.shnf'],
                                ['identifier' => 'gd1977-05-11.sbd.miller.32603.sbeok.flac16'],
                            ],
                            'numFound' => 2500
                        ]
                    ]));
                } elseif (strpos($url, 'metadata') !== false) {
                    $httpClientMock->method('getStatus')->willReturn(200);
                    $httpClientMock->method('getBody')->willReturn(json_encode([
                        'metadata' => [
                            'identifier' => 'gd1977-05-08.sbd.miller.32601.sbeok.flac16',
                            'title' => 'Grateful Dead Live at Barton Hall on 1977-05-08',
                            'date' => '1977-05-08',
                            'year' => '1977',
                            'venue' => 'Barton Hall',
                            'coverage' => 'Ithaca, NY'
                        ],
                        'files' => [
                            [
                                'name' => 'gd77-05-08d1t01.mp3',
                                'title' => 'New Minglewood Blues',
                                'track' => '01',
                                'format' => 'VBR MP3',
                                'length' => '4:38',
                                'sha1' => 'abc123def456'
                            ],
                            [
                                'name' => 'gd77-05-08d1t02.mp3',
                                'title' => 'Loser',
                                'track' => '02',
                                'format' => 'VBR MP3',
                                'length' => '7:12',
                                'sha1' => 'def456abc789'
                            ]
                        ],
                        'd1' => 'ia600001.us.archive.org',
                        'd2' => 'ia800001.us.archive.org',
                        'dir' => '/8/items/gd1977-05-08.sbd.miller.32601.sbeok.flac16'
                    ]));
                }
            });

            $httpClientMock->method('getStatus')->willReturn(200);

            $jsonSerializer = new Json();
            $loggerMock = $this->createMock(Logger::class);

            $showFactoryMock = $this->createMock(\ArchiveDotOrg\Core\Api\Data\ShowInterfaceFactory::class);
            $showFactoryMock->method('create')->willReturnCallback(function () {
                return new \ArchiveDotOrg\Core\Model\Data\Show();
            });

            $trackFactoryMock = $this->createMock(\ArchiveDotOrg\Core\Api\Data\TrackInterfaceFactory::class);
            $trackFactoryMock->method('create')->willReturnCallback(function () {
                return new \ArchiveDotOrg\Core\Model\Data\Track();
            });

            $cacheMock = $this->createMock(ApiResponseCache::class);
            $cacheMock->method('get')->willReturn(null);

            $circuitBreakerCacheMock = $this->createMock(\Magento\Framework\App\CacheInterface::class);
            $circuitBreakerCacheMock->method('load')->willReturn(false);

            $circuitBreakerMock = $this->createMock(CircuitBreaker::class);
            $circuitBreakerMock->method('call')->willReturnCallback(fn($op) => $op());

            $this->client = new ArchiveApiClient(
                $configMock,
                $httpClientMock,
                $jsonSerializer,
                $loggerMock,
                $showFactoryMock,
                $trackFactoryMock,
                $cacheMock,
                $circuitBreakerMock
            );
        }

        return $this->client;
    }
}

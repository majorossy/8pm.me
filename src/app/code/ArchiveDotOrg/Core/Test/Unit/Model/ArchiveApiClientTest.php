<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\ShowInterfaceFactory;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterfaceFactory;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\ArchiveApiClient;
use ArchiveDotOrg\Core\Model\Cache\ApiResponseCache;
use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\Resilience\CircuitBreaker;
use ArchiveDotOrg\Core\Model\Resilience\CircuitOpenException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArchiveApiClient
 *
 * @covers \ArchiveDotOrg\Core\Model\ArchiveApiClient
 */
class ArchiveApiClientTest extends TestCase
{
    private ArchiveApiClient $apiClient;
    private Config|MockObject $configMock;
    private Curl|MockObject $httpClientMock;
    private Json|MockObject $jsonSerializerMock;
    private Logger|MockObject $loggerMock;
    private ShowInterfaceFactory|MockObject $showFactoryMock;
    private TrackInterfaceFactory|MockObject $trackFactoryMock;
    private ApiResponseCache|MockObject $apiCacheMock;
    private CircuitBreaker|MockObject $circuitBreakerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->httpClientMock = $this->createMock(Curl::class);
        $this->jsonSerializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->showFactoryMock = $this->createMock(ShowInterfaceFactory::class);
        $this->trackFactoryMock = $this->createMock(TrackInterfaceFactory::class);
        $this->apiCacheMock = $this->createMock(ApiResponseCache::class);
        $this->circuitBreakerMock = $this->createMock(CircuitBreaker::class);

        // Default config mock behavior
        $this->configMock->method('getTimeout')->willReturn(30);
        $this->configMock->method('getRetryAttempts')->willReturn(3);
        $this->configMock->method('getRetryDelay')->willReturn(100);
        $this->configMock->method('getBaseUrl')->willReturn('https://archive.org');
        $this->configMock->method('getAudioFormat')->willReturn('mp3');
        $this->configMock->method('getRateLimitMs')->willReturn(0); // No rate limiting in tests
        $this->configMock->method('getPageSize')->willReturn(500);

        // Default circuit breaker: always allow requests
        $this->circuitBreakerMock->method('call')
            ->willReturnCallback(fn(callable $operation) => $operation());

        // Default cache: no caching
        $this->apiCacheMock->method('get')->willReturn(null);

        $this->apiClient = new ArchiveApiClient(
            $this->configMock,
            $this->httpClientMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->showFactoryMock,
            $this->trackFactoryMock,
            $this->apiCacheMock,
            $this->circuitBreakerMock
        );
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersReturnsIdentifiersOnSuccess(): void
    {
        $collectionId = 'TestCollection';
        $expectedIdentifiers = ['show1', 'show2', 'show3'];

        $this->configMock->method('buildPaginatedSearchUrl')
            ->willReturn('https://archive.org/search?collection=' . $collectionId);

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{"response":{"docs":[{"identifier":"show1"},{"identifier":"show2"},{"identifier":"show3"}],"numFound":3}}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn([
                'response' => [
                    'docs' => [
                        ['identifier' => 'show1'],
                        ['identifier' => 'show2'],
                        ['identifier' => 'show3']
                    ],
                    'numFound' => 3
                ]
            ]);

        $result = $this->apiClient->fetchCollectionIdentifiers($collectionId);

        $this->assertEquals($expectedIdentifiers, $result);
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersAppliesLimitAndOffset(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');
        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        // With offset=1 and pageSize=500, we're on page 1 but skip 1 item
        // API returns full page data, client-side skips within-page offset
        $this->jsonSerializerMock->method('unserialize')
            ->willReturn([
                'response' => [
                    'docs' => [
                        ['identifier' => 'show1'], // Will be skipped (offset=1)
                        ['identifier' => 'show2'],
                        ['identifier' => 'show3']
                    ],
                    'numFound' => 5
                ]
            ]);

        $result = $this->apiClient->fetchCollectionIdentifiers($collectionId, 2, 1);

        // After skipping 1 item (offset), we get the next 2 items (limit)
        $this->assertEquals(['show2', 'show3'], $result);
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersThrowsOnInvalidResponse(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');
        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['response' => []]); // Missing 'docs'

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid response format');

        $this->apiClient->fetchCollectionIdentifiers($collectionId);
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersRetriesOn5xxError(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');
        $this->configMock->method('getRetryAttempts')->willReturn(3);
        $this->configMock->method('getRetryDelay')->willReturn(1); // 1ms for fast test

        // First two calls fail with 500, third succeeds
        $statusSequence = [500, 500, 200];
        $callCount = 0;

        $this->httpClientMock->method('getStatus')
            ->willReturnCallback(function () use (&$callCount, $statusSequence) {
                return $statusSequence[$callCount++] ?? 200;
            });

        $this->httpClientMock->method('getBody')
            ->willReturn('{"response":{"docs":[{"identifier":"show1"}],"numFound":1}}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['response' => ['docs' => [['identifier' => 'show1']], 'numFound' => 1]]);

        $this->loggerMock->expects($this->atLeast(1))
            ->method('debug');

        $result = $this->apiClient->fetchCollectionIdentifiers($collectionId);

        $this->assertEquals(['show1'], $result);
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersDoesNotRetryOn4xxError(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');

        $this->httpClientMock->method('getStatus')->willReturn(404);
        $this->httpClientMock->expects($this->once())->method('get'); // Should only be called once

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('API error: HTTP 404');

        $this->apiClient->fetchCollectionIdentifiers($collectionId);
    }

    /**
     * @test
     */
    public function fetchCollectionIdentifiersThrowsAfterMaxRetries(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');
        $this->configMock->method('getRetryAttempts')->willReturn(3);
        $this->configMock->method('getRetryDelay')->willReturn(1);

        $this->httpClientMock->method('getStatus')->willReturn(500);
        $this->httpClientMock->expects($this->exactly(3))->method('get');

        $this->expectException(LocalizedException::class);

        $this->apiClient->fetchCollectionIdentifiers($collectionId);
    }

    /**
     * @test
     */
    public function fetchShowMetadataReturnsShowInterface(): void
    {
        $identifier = 'test-show-2023';

        $showMock = $this->createMock(ShowInterface::class);
        $this->showFactoryMock->method('create')->willReturn($showMock);

        $this->configMock->method('buildMetadataUrl')
            ->with($identifier)
            ->willReturn('https://archive.org/metadata/' . $identifier);

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn([
                'metadata' => [
                    'title' => 'Test Show',
                    'date' => '2023-01-15',
                    'venue' => 'Test Venue'
                ],
                'files' => [],
                'd1' => 'ia600001.us.archive.org',
                'd2' => 'ia800001.us.archive.org',
                'dir' => '/8/items/test-show-2023'
            ]);

        $showMock->expects($this->once())->method('setIdentifier')->with($identifier);
        $showMock->expects($this->once())->method('setTitle')->with('Test Show');
        $showMock->expects($this->once())->method('setTracks')->with([]);

        $result = $this->apiClient->fetchShowMetadata($identifier);

        $this->assertSame($showMock, $result);
    }

    /**
     * @test
     */
    public function fetchShowMetadataParsesTracks(): void
    {
        $identifier = 'test-show-2023';

        $showMock = $this->createMock(ShowInterface::class);
        $trackMock = $this->createMock(TrackInterface::class);

        $this->showFactoryMock->method('create')->willReturn($showMock);
        $this->trackFactoryMock->method('create')->willReturn($trackMock);

        $this->configMock->method('buildMetadataUrl')->willReturn('https://archive.org/metadata/' . $identifier);
        $this->configMock->method('getAudioFormat')->willReturn('mp3');

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn([
                'metadata' => ['title' => 'Test Show'],
                'files' => [
                    ['name' => 'track01.mp3', 'title' => 'Song One', 'track' => 1],
                    ['name' => 'track02.flac', 'title' => 'Song Two'], // Different format, should be skipped
                    ['name' => 'track03.mp3', 'title' => 'Song Three', 'track' => 3]
                ]
            ]);

        $showMock->expects($this->once())
            ->method('setTracks')
            ->with($this->callback(function ($tracks) {
                return count($tracks) === 2; // Only mp3 files
            }));

        $this->apiClient->fetchShowMetadata($identifier);
    }

    /**
     * @test
     */
    public function testConnectionReturnsTrueOnSuccess(): void
    {
        $this->httpClientMock->method('getStatus')->willReturn(200);

        $result = $this->apiClient->testConnection();

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testConnectionReturnsTrueOn404(): void
    {
        // 404 means server is responding, just no resource at that path
        $this->httpClientMock->method('getStatus')->willReturn(404);

        $result = $this->apiClient->testConnection();

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testConnectionReturnsFalseOn500(): void
    {
        $this->httpClientMock->method('getStatus')->willReturn(500);

        $result = $this->apiClient->testConnection();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testConnectionReturnsFalseOnException(): void
    {
        $this->httpClientMock->method('get')
            ->willThrowException(new \Exception('Connection timeout'));

        $this->loggerMock->expects($this->once())
            ->method('logApiError');

        $result = $this->apiClient->testConnection();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function getCollectionCountReturnsCorrectCount(): void
    {
        $collectionId = 'TestCollection';

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['response' => ['numFound' => 1500]]);

        $result = $this->apiClient->getCollectionCount($collectionId);

        $this->assertEquals(1500, $result);
    }

    /**
     * @test
     */
    public function getCollectionCountReturnsZeroOnMissingData(): void
    {
        $collectionId = 'TestCollection';

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['response' => []]);

        $result = $this->apiClient->getCollectionCount($collectionId);

        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function parseJsonResponseThrowsOnInvalidJson(): void
    {
        $collectionId = 'TestCollection';

        $this->configMock->method('buildPaginatedSearchUrl')->willReturn('https://archive.org/search');
        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('invalid json');

        $this->jsonSerializerMock->method('unserialize')
            ->willThrowException(new \InvalidArgumentException('Invalid JSON'));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Failed to parse API response');

        $this->apiClient->fetchCollectionIdentifiers($collectionId);
    }

    /**
     * @test
     */
    public function fetchShowMetadataUsesCacheWhenAvailable(): void
    {
        $identifier = 'test-show-2023';

        $showMock = $this->createMock(ShowInterface::class);
        $this->showFactoryMock->method('create')->willReturn($showMock);

        // Return cached data
        $this->apiCacheMock = $this->createMock(ApiResponseCache::class);
        $this->apiCacheMock->method('get')
            ->with($identifier, 'metadata')
            ->willReturn([
                'metadata' => ['title' => 'Cached Show'],
                'files' => [],
                'd1' => 'ia600001.us.archive.org',
                'd2' => 'ia800001.us.archive.org',
                'dir' => '/8/items/test-show-2023'
            ]);

        // HTTP client should NOT be called
        $this->httpClientMock->expects($this->never())->method('get');

        // Recreate API client with the new cache mock
        $apiClient = new ArchiveApiClient(
            $this->configMock,
            $this->httpClientMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->showFactoryMock,
            $this->trackFactoryMock,
            $this->apiCacheMock,
            $this->circuitBreakerMock
        );

        $showMock->expects($this->once())->method('setTitle')->with('Cached Show');

        $apiClient->fetchShowMetadata($identifier);
    }

    /**
     * @test
     */
    public function fetchShowMetadataCachesResponse(): void
    {
        $identifier = 'test-show-2023';

        $showMock = $this->createMock(ShowInterface::class);
        $this->showFactoryMock->method('create')->willReturn($showMock);

        $this->configMock->method('buildMetadataUrl')
            ->with($identifier)
            ->willReturn('https://archive.org/metadata/' . $identifier);

        $this->httpClientMock->method('getStatus')->willReturn(200);
        $this->httpClientMock->method('getBody')->willReturn('{}');

        $responseData = [
            'metadata' => ['title' => 'Test Show'],
            'files' => [],
            'd1' => 'ia600001.us.archive.org'
        ];

        $this->jsonSerializerMock->method('unserialize')->willReturn($responseData);

        // Verify cache is set with the response data
        $this->apiCacheMock->expects($this->once())
            ->method('set')
            ->with($identifier, $responseData, 'metadata');

        $this->apiClient->fetchShowMetadata($identifier);
    }

    /**
     * @test
     */
    public function circuitBreakerPreventsCallsWhenOpen(): void
    {
        // Create a circuit breaker that throws
        $this->circuitBreakerMock = $this->createMock(CircuitBreaker::class);
        $this->circuitBreakerMock->method('call')
            ->willThrowException(CircuitOpenException::fromString('Circuit breaker is open'));

        $apiClient = new ArchiveApiClient(
            $this->configMock,
            $this->httpClientMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->showFactoryMock,
            $this->trackFactoryMock,
            $this->apiCacheMock,
            $this->circuitBreakerMock
        );

        $this->configMock->method('buildMetadataUrl')->willReturn('https://archive.org/metadata/test');

        // HTTP client should NOT be called when circuit is open
        $this->httpClientMock->expects($this->never())->method('get');

        $this->expectException(CircuitOpenException::class);

        $apiClient->fetchShowMetadata('test-identifier');
    }
}

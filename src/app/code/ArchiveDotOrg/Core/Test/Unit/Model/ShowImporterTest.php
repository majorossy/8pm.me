<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Api\CategoryAssignmentServiceInterface;
use ArchiveDotOrg\Core\Api\Data\ImportResultInterface;
use ArchiveDotOrg\Core\Api\Data\ImportResultInterfaceFactory;
use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;
use ArchiveDotOrg\Core\Api\TrackImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\ConcurrentApiClient;
use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\ShowImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ShowImporter
 *
 * @covers \ArchiveDotOrg\Core\Model\ShowImporter
 */
class ShowImporterTest extends TestCase
{
    private ShowImporter $showImporter;
    private ArchiveApiClientInterface|MockObject $apiClientMock;
    private ConcurrentApiClient|MockObject $concurrentApiClientMock;
    private TrackImporterInterface|MockObject $trackImporterMock;
    private AttributeOptionManagerInterface|MockObject $attributeOptionManagerMock;
    private CategoryAssignmentServiceInterface|MockObject $categoryAssignmentServiceMock;
    private ImportResultInterfaceFactory|MockObject $resultFactoryMock;
    private Config|MockObject $configMock;
    private Logger|MockObject $loggerMock;
    private ImportResultInterface|MockObject $resultMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(ArchiveApiClientInterface::class);
        $this->concurrentApiClientMock = $this->createMock(ConcurrentApiClient::class);
        $this->trackImporterMock = $this->createMock(TrackImporterInterface::class);
        $this->attributeOptionManagerMock = $this->createMock(AttributeOptionManagerInterface::class);
        $this->categoryAssignmentServiceMock = $this->createMock(CategoryAssignmentServiceInterface::class);
        $this->resultFactoryMock = $this->createMock(ImportResultInterfaceFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->resultMock = $this->createMock(ImportResultInterface::class);
        $this->resultFactoryMock->method('create')->willReturn($this->resultMock);

        $this->configMock->method('getBatchSize')->willReturn(10);

        $this->showImporter = new ShowImporter(
            $this->apiClientMock,
            $this->concurrentApiClientMock,
            $this->trackImporterMock,
            $this->attributeOptionManagerMock,
            $this->categoryAssignmentServiceMock,
            $this->resultFactoryMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * @test
     */
    public function importByCollectionProcessesAllShows(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $identifiers = ['show1', 'show2', 'show3'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->with($collectionId, null, null)
            ->willReturn($identifiers);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show Title');

        $this->apiClientMock->method('fetchShowMetadata')
            ->willReturn($showMock);

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 5, 'updated' => 0, 'skipped' => 0, 'product_ids' => [1, 2, 3, 4, 5]]);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $this->resultMock->expects($this->exactly(3))
            ->method('incrementShowsProcessed');

        $result = $this->showImporter->importByCollection($artistName, $collectionId);

        $this->assertSame($this->resultMock, $result);
    }

    /**
     * @test
     */
    public function importByCollectionAppliesLimitAndOffset(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $limit = 5;
        $offset = 10;

        $this->apiClientMock->expects($this->once())
            ->method('fetchCollectionIdentifiers')
            ->with($collectionId, $limit, $offset)
            ->willReturn(['show1']);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show Title');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);
        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'product_ids' => []]);
        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')->willReturn(100);

        $this->showImporter->importByCollection($artistName, $collectionId, $limit, $offset);
    }

    /**
     * @test
     */
    public function importByCollectionCallsProgressCallback(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $identifiers = ['show1', 'show2'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($identifiers);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show Title');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);
        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'product_ids' => []]);
        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')->willReturn(100);

        $callbackCalls = [];
        $progressCallback = function (int $total, int $current, string $message) use (&$callbackCalls) {
            $callbackCalls[] = ['total' => $total, 'current' => $current, 'message' => $message];
        };

        $this->showImporter->importByCollection($artistName, $collectionId, null, null, $progressCallback);

        // Should have initial call (current=0) plus one call per show
        $this->assertCount(3, $callbackCalls);
        $this->assertEquals(2, $callbackCalls[0]['total']);
        $this->assertEquals(0, $callbackCalls[0]['current']);
    }

    /**
     * @test
     */
    public function importByCollectionHandlesShowProcessingError(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1', 'show2']);

        $this->apiClientMock->method('fetchShowMetadata')
            ->willThrowException(new \Exception('API Error'));

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')->willReturn(100);

        $this->resultMock->expects($this->exactly(2))
            ->method('addError');

        $this->loggerMock->expects($this->exactly(2))
            ->method('logImportError');

        $this->showImporter->importByCollection($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function importByCollectionClearsCachesBetweenBatches(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        // Create 15 identifiers to trigger batch processing with batch size of 10
        $identifiers = array_map(fn($i) => "show$i", range(1, 15));

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($identifiers);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show Title');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);
        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'product_ids' => []]);
        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')->willReturn(100);

        // Should clear caches after each batch (2 batches for 15 items with batch size 10)
        $this->attributeOptionManagerMock->expects($this->exactly(2))
            ->method('clearCache');
        $this->categoryAssignmentServiceMock->expects($this->exactly(2))
            ->method('clearCache');

        $this->showImporter->importByCollection($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function importShowProcessesSingleShow(): void
    {
        $identifier = 'test-show-2023';
        $artistName = 'Test Artist';

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn($identifier);
        $showMock->method('getTitle')->willReturn('Test Show');

        $this->apiClientMock->expects($this->once())
            ->method('fetchShowMetadata')
            ->with($identifier)
            ->willReturn($showMock);

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 10, 'updated' => 2, 'skipped' => 1, 'product_ids' => [1, 2, 3]]);

        $this->resultMock->expects($this->once())->method('incrementShowsProcessed');
        $this->resultMock->expects($this->exactly(10))->method('incrementTracksCreated');
        $this->resultMock->expects($this->exactly(2))->method('incrementTracksUpdated');
        $this->resultMock->expects($this->once())->method('incrementTracksSkipped');

        $result = $this->showImporter->importShow($identifier, $artistName);

        $this->assertSame($this->resultMock, $result);
    }

    /**
     * @test
     */
    public function dryRunDoesNotCallTrackImporter(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1']);

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn('test-sku-123');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([$trackMock]);

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // Dry run should only check if product exists, not actually import
        $this->trackImporterMock->expects($this->once())
            ->method('productExists')
            ->with('test-sku-123')
            ->willReturn(false);

        // importShowTracks should NOT be called during dry run
        $this->trackImporterMock->expects($this->never())
            ->method('importShowTracks');

        $this->resultMock->expects($this->once())->method('incrementShowsProcessed');
        $this->resultMock->expects($this->once())->method('incrementTracksCreated');

        $this->showImporter->dryRun($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function dryRunCountsExistingProductsAsUpdates(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1']);

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn('test-sku-123');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([$trackMock]);

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        $this->trackImporterMock->method('productExists')
            ->with('test-sku-123')
            ->willReturn(true); // Product exists

        $this->resultMock->expects($this->once())->method('incrementTracksUpdated');
        $this->resultMock->expects($this->never())->method('incrementTracksCreated');

        $this->showImporter->dryRun($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function importByCollectionAssignsProductsToCategories(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $artistCategoryId = 100;
        $showCategoryId = 200;
        $productIds = [1, 2, 3];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1']);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show Title');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 3, 'updated' => 0, 'skipped' => 0, 'product_ids' => $productIds]);

        $this->categoryAssignmentServiceMock->expects($this->once())
            ->method('getOrCreateArtistCategory')
            ->with($artistName, $collectionId)
            ->willReturn($artistCategoryId);

        $this->categoryAssignmentServiceMock->expects($this->once())
            ->method('bulkAssignToCategory')
            ->with($productIds, $artistCategoryId);

        $this->categoryAssignmentServiceMock->expects($this->once())
            ->method('getOrCreateShowCategory')
            ->with('show1', 'Show Title', $artistCategoryId)
            ->willReturn($showCategoryId);

        $this->showImporter->importByCollection($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function testPartialFailureRecovery(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $identifiers = ['show1', 'show2', 'show3', 'show4', 'show5',
                        'show6', 'show7', 'show8', 'show9', 'show10'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($identifiers);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        // Mock shows - show6 will fail
        $callCount = 0;
        $this->apiClientMock->method('fetchShowMetadata')
            ->willReturnCallback(function ($identifier) use (&$callCount) {
                $callCount++;
                if ($identifier === 'show6') {
                    throw new \Exception('API error for show6');
                }

                $showMock = $this->createMock(ShowInterface::class);
                $showMock->method('getTracks')->willReturn([]);
                $showMock->method('getIdentifier')->willReturn($identifier);
                $showMock->method('getTitle')->willReturn("Show $identifier");
                return $showMock;
            });

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 5, 'updated' => 0, 'skipped' => 0, 'product_ids' => [1, 2, 3, 4, 5]]);

        // Should process 9 successful shows
        $this->resultMock->expects($this->exactly(9))
            ->method('incrementShowsProcessed');

        // Should log 1 error for show6
        $this->loggerMock->expects($this->once())
            ->method('logImportError')
            ->with($this->stringContains('show6'));

        $this->resultMock->expects($this->once())
            ->method('addError')
            ->with($this->stringContains('show6'));

        $this->showImporter->importByCollection($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function testCorruptDataHandling(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1', 'show2']);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        // First show returns malformed data
        $this->apiClientMock->method('fetchShowMetadata')
            ->willReturnCallback(function ($identifier) {
                if ($identifier === 'show1') {
                    throw new \InvalidArgumentException('Failed to parse API response: Invalid JSON');
                }

                $showMock = $this->createMock(ShowInterface::class);
                $showMock->method('getTracks')->willReturn([]);
                $showMock->method('getIdentifier')->willReturn($identifier);
                $showMock->method('getTitle')->willReturn('Show 2');
                return $showMock;
            });

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 3, 'updated' => 0, 'skipped' => 0, 'product_ids' => [1, 2, 3]]);

        // Should log error with identifier
        $this->loggerMock->expects($this->once())
            ->method('logImportError')
            ->with(
                $this->stringContains('show1'),
                $this->callback(function ($context) {
                    return isset($context['exception']) &&
                           strpos($context['exception'], 'Invalid JSON') !== false;
                })
            );

        // Should process the second show successfully
        $this->resultMock->expects($this->once())
            ->method('incrementShowsProcessed');

        $this->showImporter->importByCollection($artistName, $collectionId);
    }

    /**
     * @test
     */
    public function testDiskSpaceExhaustion(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1']);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Show 1');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // Simulate disk full during track import
        $this->trackImporterMock->method('importShowTracks')
            ->willThrowException(new \RuntimeException('Failed to write file: disk full'));

        // Should log error with clear message
        $this->loggerMock->expects($this->once())
            ->method('logImportError')
            ->with(
                $this->stringContains('show1'),
                $this->callback(function ($context) {
                    return isset($context['exception']) &&
                           strpos($context['exception'], 'disk full') !== false;
                })
            );

        $this->resultMock->expects($this->once())
            ->method('addError')
            ->with($this->stringContains('disk full'));

        $this->showImporter->importByCollection($artistName, $collectionId);
    }
}

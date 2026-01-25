<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\BulkProductImporter;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BulkProductImporter
 *
 * @covers \ArchiveDotOrg\Core\Model\BulkProductImporter
 */
class BulkProductImporterTest extends TestCase
{
    private BulkProductImporter $bulkProductImporter;
    private ProductResource|MockObject $productResourceMock;
    private ProductCollectionFactory|MockObject $productCollectionFactoryMock;
    private AttributeOptionManagerInterface|MockObject $attributeOptionManagerMock;
    private IndexerRegistry|MockObject $indexerRegistryMock;
    private ResourceConnection|MockObject $resourceConnectionMock;
    private Config|MockObject $configMock;
    private Logger|MockObject $loggerMock;
    private AdapterInterface|MockObject $connectionMock;

    protected function setUp(): void
    {
        $this->productResourceMock = $this->createMock(ProductResource::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->attributeOptionManagerMock = $this->createMock(AttributeOptionManagerInterface::class);
        $this->indexerRegistryMock = $this->createMock(IndexerRegistry::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->resourceConnectionMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceConnectionMock->method('getTableName')->willReturnCallback(fn($name) => $name);

        $this->configMock->method('getBatchSize')->willReturn(10);
        $this->configMock->method('getAttributeSetId')->willReturn(4);
        $this->configMock->method('getDefaultWebsiteId')->willReturn(1);

        $this->bulkProductImporter = new BulkProductImporter(
            $this->productResourceMock,
            $this->productCollectionFactoryMock,
            $this->attributeOptionManagerMock,
            $this->indexerRegistryMock,
            $this->resourceConnectionMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    private function createShowMock(string $identifier, array $tracks = []): MockObject
    {
        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn($identifier);
        $showMock->method('getTitle')->willReturn("Test Show $identifier");
        $showMock->method('getYear')->willReturn('2023');
        $showMock->method('getVenue')->willReturn('Test Venue');
        $showMock->method('getTracks')->willReturn($tracks);
        return $showMock;
    }

    private function createTrackMock(string $sku, string $title): MockObject
    {
        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturn($title);
        $trackMock->method('getLength')->willReturn('5:00');
        $trackMock->method('generateUrlKey')->willReturn(strtolower(str_replace(' ', '-', $title)));
        $trackMock->method('getName')->willReturn('track.mp3');
        return $trackMock;
    }

    /**
     * @test
     */
    public function importBulkCreatesNewProducts(): void
    {
        $track1 = $this->createTrackMock('sku-1', 'Track One');
        $track2 = $this->createTrackMock('sku-2', 'Track Two');
        $show = $this->createShowMock('show1', [$track1, $track2]);

        // Mock collection for SKU existence check (empty = no existing)
        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $this->connectionMock->expects($this->exactly(2))
            ->method('insert')
            ->with('catalog_product_entity', $this->anything());

        $this->connectionMock->method('lastInsertId')
            ->willReturnOnConsecutiveCalls('1', '2');

        $result = $this->bulkProductImporter->importBulk([$show], 'Test Artist');

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['skipped']);
    }

    /**
     * @test
     */
    public function importBulkUpdatesExistingProducts(): void
    {
        $track = $this->createTrackMock('existing-sku', 'Existing Track');
        $show = $this->createShowMock('show1', [$track]);

        // Mock existing product
        $existingProductMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $existingProductMock->method('getSku')->willReturn('existing-sku');
        $existingProductMock->method('getId')->willReturn(123);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$existingProductMock]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);

        // Should not insert new product
        $this->connectionMock->expects($this->never())
            ->method('insert')
            ->with('catalog_product_entity', $this->anything());

        $result = $this->bulkProductImporter->importBulk([$show], 'Test Artist');

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * @test
     */
    public function importBulkSkipsTracksWithoutSku(): void
    {
        $validTrack = $this->createTrackMock('valid-sku', 'Valid Track');
        $invalidTrack = $this->createTrackMock('', 'Invalid Track'); // Empty SKU

        $show = $this->createShowMock('show1', [$validTrack, $invalidTrack]);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $this->connectionMock->method('lastInsertId')->willReturn('1');

        $result = $this->bulkProductImporter->importBulk([$show], 'Test Artist');

        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * @test
     */
    public function importBulkCallsProgressCallback(): void
    {
        $track = $this->createTrackMock('sku-1', 'Track One');
        $show = $this->createShowMock('show1', [$track]);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);
        $this->connectionMock->method('lastInsertId')->willReturn('1');

        $progressCalls = [];
        $progressCallback = function (int $total, int $current, string $title) use (&$progressCalls) {
            $progressCalls[] = compact('total', 'current', 'title');
        };

        $this->bulkProductImporter->importBulk([$show], 'Test Artist', $progressCallback);

        $this->assertCount(1, $progressCalls);
        $this->assertEquals(1, $progressCalls[0]['total']);
        $this->assertEquals(1, $progressCalls[0]['current']);
        $this->assertEquals('Track One', $progressCalls[0]['title']);
    }

    /**
     * @test
     */
    public function importBulkHandlesExceptions(): void
    {
        $track = $this->createTrackMock('sku-1', 'Track One');
        $show = $this->createShowMock('show1', [$track]);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $this->connectionMock->method('insert')
            ->willThrowException(new \Exception('Database error'));

        $this->loggerMock->expects($this->once())
            ->method('logImportError')
            ->with('Bulk import track error', $this->anything());

        $result = $this->bulkProductImporter->importBulk([$show], 'Test Artist');

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
    }

    /**
     * @test
     */
    public function importBulkClearsCachesPeriodically(): void
    {
        // Create 15 tracks to trigger batch processing
        $tracks = [];
        for ($i = 1; $i <= 15; $i++) {
            $tracks[] = $this->createTrackMock("sku-$i", "Track $i");
        }
        $show = $this->createShowMock('show1', $tracks);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);
        $this->connectionMock->method('lastInsertId')
            ->willReturnCallback(fn() => (string) rand(1, 1000));

        // With batch size 10 and 15 items, cache should be cleared once (after item 10)
        $this->attributeOptionManagerMock->expects($this->once())
            ->method('clearCache');

        $this->bulkProductImporter->importBulk([$show], 'Test Artist');
    }

    /**
     * @test
     */
    public function prepareIndexersSetsScheduledMode(): void
    {
        $indexerMock = $this->createMock(Indexer::class);
        $indexerMock->method('isScheduled')->willReturn(false);
        $indexerMock->expects($this->once())->method('setScheduled')->with(true);

        $this->indexerRegistryMock->method('get')
            ->willReturn($indexerMock);

        $result = $this->bulkProductImporter->prepareIndexers();

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function prepareIndexersPreservesOriginalMode(): void
    {
        $scheduledIndexer = $this->createMock(Indexer::class);
        $scheduledIndexer->method('isScheduled')->willReturn(true);
        $scheduledIndexer->expects($this->never())->method('setScheduled');

        $this->indexerRegistryMock->method('get')
            ->willReturn($scheduledIndexer);

        $result = $this->bulkProductImporter->prepareIndexers();

        // Should record that it was already scheduled
        $this->assertNotEmpty($result);
    }

    /**
     * @test
     */
    public function restoreIndexersRestoresOriginalMode(): void
    {
        $indexerMock = $this->createMock(Indexer::class);
        $indexerMock->expects($this->once())->method('setScheduled')->with(false);

        $this->indexerRegistryMock->method('get')
            ->willReturn($indexerMock);

        // Original mode was not scheduled (false)
        $originalModes = ['catalog_product_flat' => false];

        $this->bulkProductImporter->restoreIndexers($originalModes);
    }

    /**
     * @test
     */
    public function restoreIndexersKeepsScheduledIfWasScheduled(): void
    {
        $indexerMock = $this->createMock(Indexer::class);
        // Should NOT call setScheduled when original was true
        $indexerMock->expects($this->never())->method('setScheduled');

        $this->indexerRegistryMock->method('get')
            ->willReturn($indexerMock);

        // Original mode was scheduled (true)
        $originalModes = ['catalog_product_flat' => true];

        $this->bulkProductImporter->restoreIndexers($originalModes);
    }

    /**
     * @test
     */
    public function reindexAllSkipsScheduledIndexers(): void
    {
        $scheduledIndexer = $this->createMock(Indexer::class);
        $scheduledIndexer->method('isScheduled')->willReturn(true);
        $scheduledIndexer->expects($this->never())->method('reindexAll');

        $this->indexerRegistryMock->method('get')
            ->willReturn($scheduledIndexer);

        $this->bulkProductImporter->reindexAll();
    }

    /**
     * @test
     */
    public function reindexAllReindexesNonScheduledIndexers(): void
    {
        $nonScheduledIndexer = $this->createMock(Indexer::class);
        $nonScheduledIndexer->method('isScheduled')->willReturn(false);
        $nonScheduledIndexer->expects($this->atLeastOnce())->method('reindexAll');

        $this->indexerRegistryMock->method('get')
            ->willReturn($nonScheduledIndexer);

        $this->bulkProductImporter->reindexAll();
    }

    /**
     * @test
     */
    public function importBulkPrefetchesAttributeOptions(): void
    {
        $track = $this->createTrackMock('sku-1', 'Track One');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Test Show');
        $showMock->method('getYear')->willReturn('2023');
        $showMock->method('getVenue')->willReturn('Test Venue');
        $showMock->method('getTaper')->willReturn('Test Taper');
        $showMock->method('getTracks')->willReturn([$track]);

        $collectionMock = $this->createMock(ProductCollection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->productCollectionFactoryMock->method('create')->willReturn($collectionMock);
        $this->connectionMock->method('lastInsertId')->willReturn('1');

        // Should prefetch attribute options for years, venues, tapers
        $this->attributeOptionManagerMock->expects($this->exactly(3))
            ->method('bulkGetOrCreateOptionIds')
            ->withConsecutive(
                ['show_year', ['2023']],
                ['show_venue', ['Test Venue']],
                ['show_taper', ['Test Taper']]
            );

        // Also get artist collection option
        $this->attributeOptionManagerMock->expects($this->once())
            ->method('getOrCreateOptionId')
            ->with('archive_collection', 'Test Artist');

        $this->bulkProductImporter->importBulk([$showMock], 'Test Artist');
    }
}

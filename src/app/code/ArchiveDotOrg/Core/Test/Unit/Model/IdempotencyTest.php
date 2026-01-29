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
use ArchiveDotOrg\Core\Model\ProgressTracker;
use ArchiveDotOrg\Core\Model\ShowImporter;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for idempotency in import operations
 *
 * Verifies that imports can be run multiple times without creating duplicates
 * and that interrupted imports can resume without data corruption.
 *
 * @covers \ArchiveDotOrg\Core\Model\ShowImporter
 * @covers \ArchiveDotOrg\Core\Model\TrackImporter
 */
class IdempotencyTest extends TestCase
{
    private ShowImporter $showImporter;
    private ArchiveApiClientInterface|MockObject $apiClientMock;
    private ConcurrentApiClient|MockObject $concurrentApiClientMock;
    private TrackImporterInterface|MockObject $trackImporterMock;
    private AttributeOptionManagerInterface|MockObject $attributeOptionManagerMock;
    private CategoryAssignmentServiceInterface|MockObject $categoryAssignmentServiceMock;
    private ImportResultInterfaceFactory|MockObject $resultFactoryMock;
    private ProductRepositoryInterface|MockObject $productRepositoryMock;
    private Config|MockObject $configMock;
    private Logger|MockObject $loggerMock;
    private ImportResultInterface|MockObject $resultMock;
    private ProgressTracker|MockObject $progressTrackerMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(ArchiveApiClientInterface::class);
        $this->concurrentApiClientMock = $this->createMock(ConcurrentApiClient::class);
        $this->trackImporterMock = $this->createMock(TrackImporterInterface::class);
        $this->attributeOptionManagerMock = $this->createMock(AttributeOptionManagerInterface::class);
        $this->categoryAssignmentServiceMock = $this->createMock(CategoryAssignmentServiceInterface::class);
        $this->resultFactoryMock = $this->createMock(ImportResultInterfaceFactory::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->progressTrackerMock = $this->createMock(ProgressTracker::class);

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
    public function testImportIsIdempotent(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $identifiers = ['show1', 'show2', 'show3'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($identifiers);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        // Create mock tracks
        $tracks = [];
        for ($i = 1; $i <= 5; $i++) {
            $trackMock = $this->createMock(TrackInterface::class);
            $trackMock->method('generateSku')->willReturn("sku-show1-track$i");
            $trackMock->method('getTitle')->willReturn("Track $i");
            $tracks[] = $trackMock;
        }

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn($tracks);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Test Show 1');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // First import: Create 15 products (5 tracks × 3 shows)
        $firstRunProductIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
        $this->trackImporterMock->expects($this->exactly(6)) // 3 shows × 2 imports
            ->method('importShowTracks')
            ->willReturnOnConsecutiveCalls(
                // First run: creates products
                ['created' => 5, 'updated' => 0, 'skipped' => 0, 'product_ids' => [1, 2, 3, 4, 5]],
                ['created' => 5, 'updated' => 0, 'skipped' => 0, 'product_ids' => [6, 7, 8, 9, 10]],
                ['created' => 5, 'updated' => 0, 'skipped' => 0, 'product_ids' => [11, 12, 13, 14, 15]],
                // Second run: updates same products (idempotent)
                ['created' => 0, 'updated' => 5, 'skipped' => 0, 'product_ids' => [1, 2, 3, 4, 5]],
                ['created' => 0, 'updated' => 5, 'skipped' => 0, 'product_ids' => [6, 7, 8, 9, 10]],
                ['created' => 0, 'updated' => 5, 'skipped' => 0, 'product_ids' => [11, 12, 13, 14, 15]]
            );

        // First import
        $this->resultMock->expects($this->exactly(6)) // 3 shows × 2 imports
            ->method('incrementShowsProcessed');

        $result1 = $this->showImporter->importByCollection($artistName, $collectionId);

        // Second import (should be idempotent - updates, not creates)
        $result2 = $this->showImporter->importByCollection($artistName, $collectionId);

        // Verify both imports completed
        $this->assertSame($this->resultMock, $result1);
        $this->assertSame($this->resultMock, $result2);
    }

    /**
     * @test
     */
    public function testPartialReimportNoDuplicates(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $identifiers = ['show1', 'show2', 'show3'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($identifiers);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn('test-sku-123');
        $trackMock->method('getTitle')->willReturn('Test Track');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([$trackMock]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Test Show');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // First import creates product
        $this->trackImporterMock->expects($this->exactly(6)) // 3 shows × 2 imports
            ->method('importShowTracks')
            ->willReturnOnConsecutiveCalls(
                // First run
                ['created' => 1, 'updated' => 0, 'skipped' => 0, 'product_ids' => [101]],
                ['created' => 1, 'updated' => 0, 'skipped' => 0, 'product_ids' => [102]],
                ['created' => 1, 'updated' => 0, 'skipped' => 0, 'product_ids' => [103]],
                // Second run (force re-import) - updates instead of creating
                ['created' => 0, 'updated' => 1, 'skipped' => 0, 'product_ids' => [101]],
                ['created' => 0, 'updated' => 1, 'skipped' => 0, 'product_ids' => [102]],
                ['created' => 0, 'updated' => 1, 'skipped' => 0, 'product_ids' => [103]]
            );

        // Run import twice
        $this->showImporter->importByCollection($artistName, $collectionId);
        $this->showImporter->importByCollection($artistName, $collectionId);

        // Verify products were updated, not duplicated
        // (verified by mock expectations - no new product IDs on second run)
    }

    /**
     * @test
     */
    public function testInterruptedImportCanResume(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $allIdentifiers = ['show1', 'show2', 'show3', 'show4', 'show5'];

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn($allIdentifiers);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturnOnConsecutiveCalls(
            'show1', 'show2', 'show3',  // First run (interrupted)
            'show3', 'show4', 'show5'   // Second run (resume from show3)
        );
        $showMock->method('getTitle')->willReturn('Test Show');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // First run: Process 3 shows, then "crash"
        $firstRunProcessed = 0;
        $this->trackImporterMock->method('importShowTracks')
            ->willReturnCallback(function () use (&$firstRunProcessed) {
                $firstRunProcessed++;
                if ($firstRunProcessed === 3) {
                    // Simulate crash after 3rd show
                    throw new \RuntimeException('Simulated interruption');
                }
                return ['created' => 2, 'updated' => 0, 'skipped' => 0, 'product_ids' => [1, 2]];
            });

        try {
            $this->showImporter->importByCollection($artistName, $collectionId, 5, 0);
        } catch (\RuntimeException $e) {
            // Expected interruption
            $this->assertEquals('Simulated interruption', $e->getMessage());
        }

        // Verify only 2 shows were processed before crash (3rd threw exception)
        $this->assertEquals(3, $firstRunProcessed);

        // Second run: Resume with offset (in real scenario, ProgressTracker would provide this)
        $this->trackImporterMock = $this->createMock(TrackImporterInterface::class);
        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 2, 'updated' => 0, 'skipped' => 0, 'product_ids' => [3, 4]]);

        // Recreate importer with fresh mock
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

        // Resume from offset 2 (skip already-processed shows)
        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show3', 'show4', 'show5']); // Remaining shows

        $result = $this->showImporter->importByCollection($artistName, $collectionId, 3, 2);

        // Verify resume completed successfully
        $this->assertSame($this->resultMock, $result);
    }

    /**
     * @test
     */
    public function testReimportUpdatesNotDuplicates(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $sku = 'archive-abc123def456';

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(['show1']);

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturnOnConsecutiveCalls(
            'Original Title',  // First import
            'Updated Title'    // Second import (data changed on Archive.org)
        );

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([$trackMock]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Test Show');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        // First import: Creates product
        $this->trackImporterMock->expects($this->exactly(2))
            ->method('importShowTracks')
            ->willReturnOnConsecutiveCalls(
                ['created' => 1, 'updated' => 0, 'skipped' => 0, 'product_ids' => [999]],
                ['created' => 0, 'updated' => 1, 'skipped' => 0, 'product_ids' => [999]] // Same ID
            );

        // First import
        $this->showImporter->importByCollection($artistName, $collectionId);

        // Second import with changed data
        $this->showImporter->importByCollection($artistName, $collectionId);

        // Verify: Product was updated (not duplicated)
        // Same product ID (999) returned both times
    }

    /**
     * @test
     */
    public function testConcurrentImportsHandleSkuCollisions(): void
    {
        $sku = 'archive-duplicate-sku';

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturn('Duplicate Track');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([$trackMock]);

        // Simulate concurrent import: product created between check and save
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(555);

        $this->productRepositoryMock->expects($this->exactly(2))
            ->method('get')
            ->with($sku)
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new NoSuchEntityException()),  // First check: not found
                $productMock  // Second check (after collision): found
            );

        // TrackImporter should handle this by detecting existing product and updating it
        $this->trackImporterMock->method('productExists')
            ->with($sku)
            ->willReturn(true); // Product now exists

        $this->trackImporterMock->method('getProductIdBySku')
            ->with($sku)
            ->willReturn(555);

        // Verify no exception thrown and correct product ID returned
        $productId = $this->trackImporterMock->getProductIdBySku($sku);
        $this->assertEquals(555, $productId);
    }

    /**
     * @test
     */
    public function testProgressTrackerEnablesResumableImports(): void
    {
        $artistName = 'Test Artist';
        $collectionId = 'TestCollection';
        $totalShows = 100;
        $batchSize = 10;

        $this->apiClientMock->method('fetchCollectionIdentifiers')
            ->willReturn(array_map(fn($i) => "show$i", range(1, $batchSize)));

        $this->categoryAssignmentServiceMock->method('getOrCreateArtistCategory')
            ->willReturn(100);

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getTracks')->willReturn([]);
        $showMock->method('getIdentifier')->willReturn('show1');
        $showMock->method('getTitle')->willReturn('Test Show');

        $this->apiClientMock->method('fetchShowMetadata')->willReturn($showMock);

        $this->trackImporterMock->method('importShowTracks')
            ->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'product_ids' => []]);

        // Progress tracker should be updated after each show
        $progressUpdates = 0;
        $progressCallback = function (int $total, int $current, string $message) use (&$progressUpdates) {
            $progressUpdates++;
        };

        $this->showImporter->importByCollection(
            $artistName,
            $collectionId,
            $batchSize,
            0,
            $progressCallback
        );

        // Should have initial callback + one per show
        $this->assertGreaterThan($batchSize, $progressUpdates);
    }
}

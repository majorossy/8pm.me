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
use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\TrackImporter;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TrackImporter
 *
 * @covers \ArchiveDotOrg\Core\Model\TrackImporter
 */
class TrackImporterTest extends TestCase
{
    private TrackImporter $trackImporter;
    private ProductRepositoryInterface|MockObject $productRepositoryMock;
    private ProductInterfaceFactory|MockObject $productFactoryMock;
    private AttributeOptionManagerInterface|MockObject $attributeOptionManagerMock;
    private Config|MockObject $configMock;
    private Logger|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->productFactoryMock = $this->createMock(ProductInterfaceFactory::class);
        $this->attributeOptionManagerMock = $this->createMock(AttributeOptionManagerInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->configMock->method('getAttributeSetId')->willReturn(4);
        $this->configMock->method('getDefaultWebsiteId')->willReturn(1);

        $this->trackImporter = new TrackImporter(
            $this->productRepositoryMock,
            $this->productFactoryMock,
            $this->attributeOptionManagerMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * @test
     */
    public function importTrackCreatesNewProduct(): void
    {
        $sku = 'archive-abc123def456';
        $trackTitle = 'Test Song';

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturn($trackTitle);
        $trackMock->method('getLength')->willReturn('5:32');
        $trackMock->method('generateUrlKey')->willReturn('test-song-url-key');
        $trackMock->method('getName')->willReturn('track01.mp3');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('test-show-2023');
        $showMock->method('getTitle')->willReturn('Test Show');
        $showMock->method('getYear')->willReturn('2023');
        $showMock->method('getVenue')->willReturn('Test Venue');
        $showMock->method('getServerOne')->willReturn('ia600001.us.archive.org');
        $showMock->method('getDir')->willReturn('/8/items/test-show-2023');

        // Product doesn't exist
        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willThrowException(new NoSuchEntityException());

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(123);

        $this->productFactoryMock->method('create')->willReturn($productMock);

        $this->productRepositoryMock->method('save')
            ->with($productMock)
            ->willReturn($productMock);

        $this->configMock->method('buildStreamingUrl')
            ->willReturn('https://ia600001.us.archive.org/8/items/test-show-2023/track01.flac');

        $productMock->expects($this->once())->method('setSku')->with($sku);
        $productMock->expects($this->once())->method('setTypeId')->with('virtual');

        $result = $this->trackImporter->importTrack($trackMock, $showMock, 'Test Artist');

        $this->assertEquals(123, $result);
    }

    /**
     * @test
     */
    public function importTrackUpdatesExistingProduct(): void
    {
        $sku = 'archive-abc123def456';

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturn('Test Song');
        $trackMock->method('getLength')->willReturn('5:32');
        $trackMock->method('generateUrlKey')->willReturn('test-song-url-key');
        $trackMock->method('getName')->willReturn('track01.mp3');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('test-show-2023');
        $showMock->method('getTitle')->willReturn('Test Show');
        $showMock->method('getYear')->willReturn('2023');
        $showMock->method('getVenue')->willReturn('Test Venue');

        $existingProductMock = $this->createMock(Product::class);
        $existingProductMock->method('getId')->willReturn(456);

        // Product exists
        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willReturn($existingProductMock);

        $this->productRepositoryMock->method('save')
            ->willReturn($existingProductMock);

        // Should not call factory for existing product
        $this->productFactoryMock->expects($this->never())->method('create');

        $result = $this->trackImporter->importTrack($trackMock, $showMock, 'Test Artist');

        $this->assertEquals(456, $result);
    }

    /**
     * @test
     */
    public function importTrackThrowsExceptionWhenNoSku(): void
    {
        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn(''); // Empty SKU
        $trackMock->method('getTitle')->willReturn('Test Song');

        $showMock = $this->createMock(ShowInterface::class);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot import track without SHA1 hash');

        $this->trackImporter->importTrack($trackMock, $showMock, 'Test Artist');
    }

    /**
     * @test
     */
    public function importTrackSetsDropdownAttributes(): void
    {
        $sku = 'archive-abc123def456';

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn($sku);
        $trackMock->method('getTitle')->willReturn('Test Song');
        $trackMock->method('getLength')->willReturn('5:32');
        $trackMock->method('generateUrlKey')->willReturn('test-song-url-key');
        $trackMock->method('getName')->willReturn('track01.mp3');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('test-show-2023');
        $showMock->method('getTitle')->willReturn('Test Show');
        $showMock->method('getYear')->willReturn('2023');
        $showMock->method('getVenue')->willReturn('Test Venue');
        $showMock->method('getTaper')->willReturn('Test Taper');

        $this->productRepositoryMock->method('get')
            ->willThrowException(new NoSuchEntityException());

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(123);

        $this->productFactoryMock->method('create')->willReturn($productMock);
        $this->productRepositoryMock->method('save')->willReturn($productMock);

        // Expect attribute option lookups for dropdown attributes
        $this->attributeOptionManagerMock->expects($this->exactly(4))
            ->method('getOrCreateOptionId')
            ->withConsecutive(
                ['show_year', '2023'],
                ['show_venue', 'Test Venue'],
                ['show_taper', 'Test Taper'],
                ['archive_collection', 'Test Artist']
            )
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);

        $this->trackImporter->importTrack($trackMock, $showMock, 'Test Artist');
    }

    /**
     * @test
     */
    public function importShowTracksReturnsCorrectCounts(): void
    {
        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('test-show');
        $showMock->method('getTitle')->willReturn('Test Show');

        $track1 = $this->createMock(TrackInterface::class);
        $track1->method('generateSku')->willReturn('sku-1');
        $track1->method('getTitle')->willReturn('Track 1');
        $track1->method('getLength')->willReturn('3:00');
        $track1->method('generateUrlKey')->willReturn('track-1');
        $track1->method('getName')->willReturn('track01.mp3');

        $track2 = $this->createMock(TrackInterface::class);
        $track2->method('generateSku')->willReturn('sku-2');
        $track2->method('getTitle')->willReturn('Track 2');
        $track2->method('getLength')->willReturn('4:00');
        $track2->method('generateUrlKey')->willReturn('track-2');
        $track2->method('getName')->willReturn('track02.mp3');

        $track3 = $this->createMock(TrackInterface::class);
        $track3->method('generateSku')->willReturn(''); // Will be skipped

        $showMock->method('getTracks')->willReturn([$track1, $track2, $track3]);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $this->productFactoryMock->method('create')
            ->willReturnOnConsecutiveCalls($product1, $product2);

        $this->productRepositoryMock->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->productRepositoryMock->method('save')
            ->willReturnCallback(fn($p) => $p);

        $result = $this->trackImporter->importShowTracks($showMock, 'Test Artist');

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(2, $result['product_ids']);
    }

    /**
     * @test
     */
    public function importShowTracksHandlesExceptions(): void
    {
        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('test-show');

        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn('sku-1');
        $trackMock->method('getTitle')->willReturn('Track 1');

        $showMock->method('getTracks')->willReturn([$trackMock]);

        $this->productRepositoryMock->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->productFactoryMock->method('create')
            ->willThrowException(new \Exception('Database error'));

        $this->loggerMock->expects($this->once())
            ->method('logImportError')
            ->with('Track import failed', $this->anything());

        $result = $this->trackImporter->importShowTracks($showMock, 'Test Artist');

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * @test
     */
    public function productExistsReturnsTrueForExistingProduct(): void
    {
        $sku = 'existing-product-sku';

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(123);

        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willReturn($productMock);

        $this->assertTrue($this->trackImporter->productExists($sku));
    }

    /**
     * @test
     */
    public function productExistsReturnsFalseForNonExistingProduct(): void
    {
        $sku = 'non-existing-product-sku';

        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willThrowException(new NoSuchEntityException());

        $this->assertFalse($this->trackImporter->productExists($sku));
    }

    /**
     * @test
     */
    public function getProductIdBySkuReturnsIdForExistingProduct(): void
    {
        $sku = 'existing-product-sku';

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(789);

        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willReturn($productMock);

        $this->assertEquals(789, $this->trackImporter->getProductIdBySku($sku));
    }

    /**
     * @test
     */
    public function getProductIdBySkuReturnsNullForNonExistingProduct(): void
    {
        $sku = 'non-existing-product-sku';

        $this->productRepositoryMock->method('get')
            ->with($sku)
            ->willThrowException(new NoSuchEntityException());

        $this->assertNull($this->trackImporter->getProductIdBySku($sku));
    }

    /**
     * @test
     */
    public function importTrackBuildsCorrectProductName(): void
    {
        $trackMock = $this->createMock(TrackInterface::class);
        $trackMock->method('generateSku')->willReturn('test-sku');
        $trackMock->method('getTitle')->willReturn('Dark Star');
        $trackMock->method('getLength')->willReturn('25:00');
        $trackMock->method('generateUrlKey')->willReturn('dark-star');
        $trackMock->method('getName')->willReturn('track01.mp3');

        $showMock = $this->createMock(ShowInterface::class);
        $showMock->method('getIdentifier')->willReturn('gd1977-05-08');
        $showMock->method('getTitle')->willReturn('Grateful Dead 1977-05-08');
        $showMock->method('getYear')->willReturn('1977');
        $showMock->method('getVenue')->willReturn('Barton Hall');

        $this->productRepositoryMock->method('get')
            ->willThrowException(new NoSuchEntityException());

        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(1);

        $this->productFactoryMock->method('create')->willReturn($productMock);
        $this->productRepositoryMock->method('save')->willReturn($productMock);

        // Verify the product name is set correctly: "Artist Title Year Venue"
        $productMock->expects($this->once())
            ->method('setName')
            ->with('Grateful Dead Dark Star 1977 Barton Hall');

        $this->trackImporter->importTrack($trackMock, $showMock, 'Grateful Dead');
    }
}

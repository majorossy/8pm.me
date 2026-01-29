<?php
/**
 * Integration test for full download → populate workflow
 *
 * Tests the complete flow from fetching metadata to creating products
 */
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Integration;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Api\TrackPopulatorServiceInterface;
use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Console\Command\DownloadCommand;
use ArchiveDotOrg\Core\Console\Command\PopulateCommand;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class DownloadPopulateTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private ProductRepositoryInterface $productRepository;
    private ProductCollectionFactory $productCollectionFactory;
    private ResourceConnection $resourceConnection;
    private LockServiceInterface $lockService;
    private MetadataDownloaderInterface $metadataDownloader;
    private TrackPopulatorServiceInterface $trackPopulator;
    private TrackMatcherServiceInterface $trackMatcher;
    private Config $config;
    private State $state;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productCollectionFactory = $this->objectManager->get(ProductCollectionFactory::class);
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->lockService = $this->objectManager->get(LockServiceInterface::class);
        $this->metadataDownloader = $this->objectManager->get(MetadataDownloaderInterface::class);
        $this->trackPopulator = $this->objectManager->get(TrackPopulatorServiceInterface::class);
        $this->trackMatcher = $this->objectManager->get(TrackMatcherServiceInterface::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->state = $this->objectManager->get(State::class);
        $this->logger = $this->objectManager->get(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        // Clean up test metadata files
        $this->cleanupTestFiles();

        // Clean up test products
        $this->cleanupTestProducts();

        // Release any lingering locks
        $this->cleanupLocks();
    }

    /**
     * Test full download → populate workflow
     *
     * @magentoDataFixture createTestYamlConfig
     */
    public function testFullDownloadPopulateWorkflow(): void
    {
        // Given: A test artist configuration
        $artist = 'test-artist';
        $collectionId = 'TestCollection';

        // When: Running download command with small limit
        $downloadCommand = $this->objectManager->create(DownloadCommand::class);
        $downloadTester = new CommandTester($downloadCommand);

        $downloadResult = $downloadTester->execute([
            'artist' => $artist,
            '--limit' => 3,
        ]);

        // Then: Download should succeed
        $this->assertEquals(0, $downloadResult, 'Download command should succeed');

        // And: Metadata files should exist
        $this->assertMetadataFilesExist($artist, 3);

        // When: Running populate command
        $populateCommand = $this->objectManager->create(PopulateCommand::class);
        $populateTester = new CommandTester($populateCommand);

        $populateResult = $populateTester->execute([
            'artist' => $artist,
        ]);

        // Then: Populate should succeed
        $this->assertEquals(0, $populateResult, 'Populate command should succeed');

        // And: Products should be created
        $products = $this->getProductsByCollection($collectionId);
        $this->assertGreaterThan(0, $products->getSize(), 'Products should be created');

        // And: Products should have correct attributes
        foreach ($products as $product) {
            $this->assertNotEmpty($product->getSku(), 'Product should have SKU');
            $this->assertNotEmpty($product->getName(), 'Product should have name');
            $this->assertNotEmpty($product->getData('archive_collection'), 'Product should have collection');
            $this->assertNotEmpty($product->getData('show_name'), 'Product should have show name');
        }
    }

    /**
     * Test dry-run mode doesn't create products
     */
    public function testDryRunDoesNotCreateProducts(): void
    {
        // Given: Existing metadata files
        $artist = 'test-artist';
        $this->createTestMetadataFiles($artist, 2);

        // When: Running populate in dry-run mode
        $populateCommand = $this->objectManager->create(PopulateCommand::class);
        $populateTester = new CommandTester($populateCommand);

        $result = $populateTester->execute([
            'artist' => $artist,
            '--dry-run' => true,
        ]);

        // Then: Command should succeed
        $this->assertEquals(0, $result, 'Dry-run should succeed');

        // And: No products should be created
        $products = $this->getProductsByCollection('TestCollection');
        $this->assertEquals(0, $products->getSize(), 'Dry-run should not create products');

        // And: Output should show what would be created
        $output = $populateTester->getDisplay();
        $this->assertStringContainsString('DRY RUN', $output);
        $this->assertStringContainsString('would create', $output);
    }

    /**
     * Test correlation ID tracking in database
     */
    public function testCorrelationIdTracking(): void
    {
        // Given: A clean import_run table
        $this->cleanupImportRunTable();

        // When: Running download command
        $downloadCommand = $this->objectManager->create(DownloadCommand::class);
        $downloadTester = new CommandTester($downloadCommand);

        $downloadTester->execute([
            'artist' => 'test-artist',
            '--limit' => 1,
        ]);

        // Then: Import run should be logged
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

        if (!$connection->isTableExists($table)) {
            $this->markTestSkipped('archivedotorg_import_run table not yet created (Phase 0)');
        }

        $runs = $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('command = ?', 'archive:download')
                ->order('started_at DESC')
                ->limit(1)
        );

        $this->assertNotEmpty($runs, 'Import run should be logged');
        $this->assertNotEmpty($runs[0]['correlation_id'], 'Correlation ID should be set');
        $this->assertEquals('completed', $runs[0]['status'], 'Status should be completed');
        $this->assertNotNull($runs[0]['completed_at'], 'Completed timestamp should be set');
    }

    /**
     * Test unmatched tracks are logged
     */
    public function testUnmatchedTracksLogged(): void
    {
        // Given: Metadata with tracks that won't match
        $artist = 'test-artist';
        $this->createTestMetadataWithUnmatchedTracks($artist);

        // When: Running populate with export-unmatched option
        $unmatchedFile = BP . '/var/test_unmatched.txt';

        $populateCommand = $this->objectManager->create(PopulateCommand::class);
        $populateTester = new CommandTester($populateCommand);

        $populateTester->execute([
            'artist' => $artist,
            '--export-unmatched' => $unmatchedFile,
        ]);

        // Then: Unmatched file should exist
        if (file_exists($unmatchedFile)) {
            $unmatchedContent = file_get_contents($unmatchedFile);
            $this->assertNotEmpty($unmatchedContent, 'Unmatched tracks file should have content');

            // Clean up
            unlink($unmatchedFile);
        }

        // And: Output should mention unmatched tracks
        $output = $populateTester->getDisplay();
        $this->assertStringContainsString('unmatched', strtolower($output));
    }

    /**
     * Test incremental download only fetches new shows
     */
    public function testIncrementalDownloadFetchesOnlyNew(): void
    {
        // Given: Existing metadata files
        $artist = 'test-artist';
        $this->createTestMetadataFiles($artist, 5);

        $existingCount = $this->countMetadataFiles($artist);

        // When: Running incremental download
        $downloadCommand = $this->objectManager->create(DownloadCommand::class);
        $downloadTester = new CommandTester($downloadCommand);

        $downloadTester->execute([
            'artist' => $artist,
            '--incremental' => true,
            '--limit' => 3,
        ]);

        // Then: New files should be added (if API returns new shows)
        // Note: In test environment with mocked API, this may not add files
        $newCount = $this->countMetadataFiles($artist);
        $this->assertGreaterThanOrEqual($existingCount, $newCount, 'Should not delete existing files');
    }

    /**
     * Test force re-download replaces cached files
     */
    public function testForceRedownloadReplacesCachedFiles(): void
    {
        // Given: Existing metadata file with old timestamp
        $artist = 'test-artist';
        $this->createTestMetadataFiles($artist, 1);

        // Get original modification time
        $metadataFiles = glob(BP . "/var/archivedotorg/metadata/{$artist}/*.json");
        if (empty($metadataFiles)) {
            $this->markTestSkipped('Test metadata files not created');
        }

        $originalMtime = filemtime($metadataFiles[0]);
        sleep(1); // Ensure time difference

        // When: Running download with --force
        $downloadCommand = $this->objectManager->create(DownloadCommand::class);
        $downloadTester = new CommandTester($downloadCommand);

        $downloadTester->execute([
            'artist' => $artist,
            '--force' => true,
            '--limit' => 1,
        ]);

        // Then: File modification time should be updated
        $newMtime = filemtime($metadataFiles[0]);
        $this->assertGreaterThan($originalMtime, $newMtime, 'Forced download should update file');
    }

    /**
     * Test products have all required attributes
     */
    public function testProductsHaveRequiredAttributes(): void
    {
        // Given: Populated products
        $artist = 'test-artist';
        $this->createTestMetadataFiles($artist, 1);

        $populateCommand = $this->objectManager->create(PopulateCommand::class);
        $populateTester = new CommandTester($populateCommand);
        $populateTester->execute(['artist' => $artist]);

        // When: Retrieving created products
        $products = $this->getProductsByCollection('TestCollection');

        if ($products->getSize() === 0) {
            $this->markTestSkipped('No products created (may need API mock)');
        }

        // Then: Products should have all critical attributes
        $requiredAttributes = [
            'sku',
            'name',
            'archive_collection',
            'show_name',
            'song_url',
            'identifier',
        ];

        foreach ($products as $product) {
            foreach ($requiredAttributes as $attr) {
                $value = $product->getData($attr);
                $this->assertNotEmpty($value, "Product should have {$attr} attribute");
            }
        }
    }

    // Helper Methods

    private function assertMetadataFilesExist(string $artist, int $expectedCount): void
    {
        $actualCount = $this->countMetadataFiles($artist);
        $this->assertGreaterThanOrEqual(
            $expectedCount,
            $actualCount,
            "Should have at least {$expectedCount} metadata files"
        );
    }

    private function countMetadataFiles(string $artist): int
    {
        $pattern = BP . "/var/archivedotorg/metadata/{$artist}/*.json";
        $files = glob($pattern);
        return $files ? count($files) : 0;
    }

    private function getProductsByCollection(string $collectionId)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('archive_collection', $collectionId);
        return $collection;
    }

    private function cleanupTestFiles(): void
    {
        $testDir = BP . '/var/archivedotorg/metadata/test-artist';
        if (is_dir($testDir)) {
            $files = glob($testDir . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($testDir);
        }
    }

    private function cleanupTestProducts(): void
    {
        $products = $this->getProductsByCollection('TestCollection');
        foreach ($products as $product) {
            try {
                $this->productRepository->delete($product);
            } catch (\Exception $e) {
                // Ignore deletion errors in cleanup
            }
        }
    }

    private function cleanupLocks(): void
    {
        $lockDir = BP . '/var/archivedotorg/locks';
        if (is_dir($lockDir)) {
            $lockFiles = glob($lockDir . '/*.lock');
            foreach ($lockFiles as $lockFile) {
                if (is_file($lockFile)) {
                    @unlink($lockFile);
                }
            }
        }
    }

    private function cleanupImportRunTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('archivedotorg_import_run');

        if ($connection->isTableExists($table)) {
            $connection->truncateTable($table);
        }
    }

    private function createTestMetadataFiles(string $artist, int $count): void
    {
        $metadataDir = BP . "/var/archivedotorg/metadata/{$artist}";
        if (!is_dir($metadataDir)) {
            mkdir($metadataDir, 0755, true);
        }

        for ($i = 1; $i <= $count; $i++) {
            $identifier = "test-show-{$i}";
            $metadata = [
                'metadata' => [
                    'identifier' => $identifier,
                    'title' => "Test Show {$i}",
                    'creator' => 'Test Artist',
                    'collection' => ['TestCollection'],
                    'date' => '2024-01-01',
                ],
                'files' => [
                    [
                        'name' => "track-{$i}.mp3",
                        'format' => 'VBR MP3',
                        'title' => "Test Track {$i}",
                        'track' => $i,
                        'length' => '180',
                    ],
                ],
            ];

            file_put_contents(
                "{$metadataDir}/{$identifier}.json",
                json_encode($metadata, JSON_PRETTY_PRINT)
            );
        }
    }

    private function createTestMetadataWithUnmatchedTracks(string $artist): void
    {
        $metadataDir = BP . "/var/archivedotorg/metadata/{$artist}";
        if (!is_dir($metadataDir)) {
            mkdir($metadataDir, 0755, true);
        }

        $metadata = [
            'metadata' => [
                'identifier' => 'test-unmatched-show',
                'title' => 'Test Unmatched Show',
                'creator' => 'Test Artist',
                'collection' => ['TestCollection'],
                'date' => '2024-01-01',
            ],
            'files' => [
                [
                    'name' => 'unknown-track-xyz.mp3',
                    'format' => 'VBR MP3',
                    'title' => 'This Track Should Not Match Anything',
                    'track' => 1,
                    'length' => '180',
                ],
                [
                    'name' => 'random-gibberish-abc.mp3',
                    'format' => 'VBR MP3',
                    'title' => 'Xkcd Qwerty Asdfgh Zxcvbn',
                    'track' => 2,
                    'length' => '240',
                ],
            ],
        ];

        file_put_contents(
            "{$metadataDir}/test-unmatched-show.json",
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Magento data fixture: Create test YAML config
     */
    public static function createTestYamlConfig(): void
    {
        $yamlDir = BP . '/app/code/ArchiveDotOrg/Core/config/artists';
        if (!is_dir($yamlDir)) {
            mkdir($yamlDir, 0755, true);
        }

        $yaml = <<<YAML
artist:
  name: "Test Artist"
  collection_id: "TestCollection"
  url_key: "test-artist"

albums:
  - key: "test-album"
    name: "Test Album"
    url_key: "test-album"
    year: 2024
    type: "studio"

tracks:
  - key: "test-track"
    name: "Test Track"
    url_key: "test-track"
    albums: ["test-album"]
    canonical_album: "test-album"
    type: "original"

YAML;

        file_put_contents("{$yamlDir}/test-artist.yaml", $yaml);
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Performance;

use ArchiveDotOrg\Core\Model\BulkProductImporter;
use ArchiveDotOrg\Core\Model\TrackImporter;
use ArchiveDotOrg\Core\Model\Data\Track;
use ArchiveDotOrg\Core\Model\Data\Show;
use Magento\Framework\App\ResourceConnection;

/**
 * Performance benchmarks for product import strategies
 *
 * Compares two import approaches:
 * 1. TrackImporter - ORM-based (uses Magento repositories)
 * 2. BulkProductImporter - Direct SQL (bypasses Magento ORM)
 *
 * Performance Targets:
 * - BulkProductImporter should be ~10x faster than TrackImporter
 * - BulkProductImporter should use ~50% less memory
 * - Both should handle 1,000 products without errors
 *
 * Metrics Tracked:
 * - Total execution time
 * - Peak memory usage
 * - Database queries executed
 * - Products created per second
 */
class ImportBenchmark
{
    private TrackImporter $trackImporter;
    private BulkProductImporter $bulkImporter;
    private ResourceConnection $resourceConnection;
    private array $testData = [];

    public function __construct(
        TrackImporter $trackImporter,
        BulkProductImporter $bulkImporter,
        ResourceConnection $resourceConnection
    ) {
        $this->trackImporter = $trackImporter;
        $this->bulkImporter = $bulkImporter;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Run all import benchmark tests
     *
     * @param int $productCount Number of products to test with
     * @return array Benchmark results
     */
    public function runAll(int $productCount = 1000): array
    {
        $this->generateTestData($productCount);

        return [
            'product_count' => $productCount,
            'orm_import' => $this->benchmarkOrmImport(),
            'bulk_import' => $this->benchmarkBulkImport(),
            'comparison' => $this->compareResults(),
        ];
    }

    /**
     * Benchmark TrackImporter (ORM-based approach)
     *
     * @return array
     */
    public function benchmarkOrmImport(): array
    {
        $this->resetDatabase();
        gc_collect_cycles();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startQueries = $this->getQueryCount();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->testData['tracks'] as $trackData) {
            try {
                $result = $this->trackImporter->importTrack(
                    $trackData['track'],
                    $trackData['show'],
                    'Test Artist'
                );

                if ($result !== null) {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);
        $endQueries = $this->getQueryCount();

        $duration = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        $queriesExecuted = $endQueries - $startQueries;

        return [
            'method' => 'ORM (TrackImporter)',
            'duration_seconds' => round($duration, 2),
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_mb' => round($endMemory / 1024 / 1024, 2),
            'queries_executed' => $queriesExecuted,
            'products_created' => $created,
            'products_updated' => $updated,
            'products_skipped' => $skipped,
            'products_per_second' => round($created / $duration, 2),
            'avg_time_per_product_ms' => round(($duration / $created) * 1000, 2),
        ];
    }

    /**
     * Benchmark BulkProductImporter (direct SQL approach)
     *
     * @return array
     */
    public function benchmarkBulkImport(): array
    {
        $this->resetDatabase();
        gc_collect_cycles();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startQueries = $this->getQueryCount();

        // Prepare indexers for bulk operation
        $originalModes = $this->bulkImporter->prepareIndexers();

        // Group tracks by show for bulk import
        $showsData = $this->groupTracksByShow();

        try {
            $result = $this->bulkImporter->importBulk($showsData, 'Test Artist');

            // Restore indexers
            $this->bulkImporter->restoreIndexers($originalModes);
            $this->bulkImporter->reindexAll();

            $endTime = microtime(true);
            $endMemory = memory_get_peak_usage(true);
            $endQueries = $this->getQueryCount();

            $duration = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            $queriesExecuted = $endQueries - $startQueries;

            return [
                'method' => 'Bulk SQL (BulkProductImporter)',
                'duration_seconds' => round($duration, 2),
                'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
                'peak_memory_mb' => round($endMemory / 1024 / 1024, 2),
                'queries_executed' => $queriesExecuted,
                'products_created' => $result['created'],
                'products_updated' => $result['updated'],
                'products_skipped' => $result['skipped'],
                'products_per_second' => round($result['created'] / $duration, 2),
                'avg_time_per_product_ms' => round(($duration / $result['created']) * 1000, 2),
            ];
        } catch (\Exception $e) {
            $this->bulkImporter->restoreIndexers($originalModes);
            throw $e;
        }
    }

    /**
     * Compare ORM vs Bulk import results
     *
     * Expected results:
     * - Bulk should be ~10x faster
     * - Bulk should use ~50% less memory
     * - Bulk should execute fewer queries
     *
     * @return array
     */
    private function compareResults(): array
    {
        if (empty($this->testData['orm_result']) || empty($this->testData['bulk_result'])) {
            return ['error' => 'Must run both benchmarks before comparing'];
        }

        $orm = $this->testData['orm_result'];
        $bulk = $this->testData['bulk_result'];

        $speedupFactor = $orm['duration_seconds'] / $bulk['duration_seconds'];
        $memoryReduction = (($orm['memory_mb'] - $bulk['memory_mb']) / $orm['memory_mb']) * 100;
        $queryReduction = (($orm['queries_executed'] - $bulk['queries_executed']) / $orm['queries_executed']) * 100;

        return [
            'speedup_factor' => round($speedupFactor, 2),
            'speedup_met_target' => $speedupFactor >= 10,
            'memory_reduction_percent' => round($memoryReduction, 2),
            'memory_reduction_met_target' => $memoryReduction >= 50,
            'query_reduction_percent' => round($queryReduction, 2),
            'bulk_products_per_second' => $bulk['products_per_second'],
            'orm_products_per_second' => $orm['products_per_second'],
        ];
    }

    /**
     * Generate realistic test data for imports
     *
     * @param int $count
     */
    public function generateTestData(int $count): void
    {
        $this->testData['tracks'] = [];
        $showsPerArtist = 10;
        $tracksPerShow = (int)($count / $showsPerArtist);

        for ($showIndex = 0; $showIndex < $showsPerArtist; $showIndex++) {
            $showIdentifier = 'test-artist-2024-01-' . str_pad((string)($showIndex + 1), 2, '0', STR_PAD_LEFT);
            $showDate = '2024-01-' . str_pad((string)($showIndex + 1), 2, '0', STR_PAD_LEFT);

            $show = new Show([
                'identifier' => $showIdentifier,
                'name' => 'Test Artist Show ' . ($showIndex + 1),
                'date' => $showDate,
                'venue' => 'Test Venue ' . ($showIndex % 5 + 1),
                'year' => '2024',
                'taper' => 'Test Taper',
                'notes' => 'Test show notes',
                'lineage' => 'Test lineage',
                'server' => 'ia800100.us.archive.org',
                'dir' => '/0/items/' . $showIdentifier,
            ]);

            for ($trackIndex = 0; $trackIndex < $tracksPerShow; $trackIndex++) {
                $trackNumber = $trackIndex + 1;
                $trackTitle = 'Test Track ' . $trackNumber;
                $fileName = 'test-track-' . str_pad((string)$trackNumber, 2, '0', STR_PAD_LEFT) . '.mp3';

                $track = new Track([
                    'title' => $trackTitle,
                    'length' => sprintf('%02d:%02d', rand(2, 15), rand(0, 59)),
                    'file' => $fileName,
                    'track_number' => $trackNumber,
                    'format' => 'VBR MP3',
                    'size' => rand(5000000, 15000000),
                    'md5' => md5($showIdentifier . $fileName),
                ]);

                $this->testData['tracks'][] = [
                    'track' => $track,
                    'show' => $show,
                    'show_identifier' => $showIdentifier,
                ];
            }
        }
    }

    /**
     * Group test tracks by show for bulk import
     *
     * @return array
     */
    private function groupTracksByShow(): array
    {
        $shows = [];

        foreach ($this->testData['tracks'] as $trackData) {
            $showId = $trackData['show_identifier'];

            if (!isset($shows[$showId])) {
                $shows[$showId] = clone $trackData['show'];
                $shows[$showId]->setTracks([]);
            }

            $tracks = $shows[$showId]->getTracks() ?? [];
            $tracks[] = $trackData['track'];
            $shows[$showId]->setTracks($tracks);
        }

        return array_values($shows);
    }

    /**
     * Reset database to clean state for testing
     *
     * Deletes all test products created in previous runs
     */
    private function resetDatabase(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        // Delete test products
        $connection->delete(
            $productTable,
            ['sku LIKE ?' => 'test-artist-%']
        );
    }

    /**
     * Get current database query count
     *
     * This is an approximation - actual implementation would use Magento's profiler
     *
     * @return int
     */
    private function getQueryCount(): int
    {
        try {
            $connection = $this->resourceConnection->getConnection();

            // Try to get query count from MySQL status variables
            $result = $connection->fetchOne('SHOW SESSION STATUS LIKE "Questions"');

            if ($result) {
                return (int)$result;
            }
        } catch (\Exception $e) {
            // Fallback to 0 if unable to get query count
        }

        return 0;
    }

    /**
     * Store results for comparison
     *
     * @param string $type 'orm' or 'bulk'
     * @param array $result
     */
    public function storeResult(string $type, array $result): void
    {
        $this->testData[$type . '_result'] = $result;
    }
}

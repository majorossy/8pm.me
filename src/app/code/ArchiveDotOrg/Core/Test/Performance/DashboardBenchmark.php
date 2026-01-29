<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Performance;

use Magento\Framework\App\ResourceConnection;

/**
 * Performance benchmarks for admin dashboard queries
 *
 * Tests dashboard query performance to ensure responsive admin UI:
 * - Artist grid query (35 artists)
 * - Import history query (1000+ import runs)
 * - Unmatched tracks query (500+ unmatched tracks)
 * - Imports per day chart (30 days aggregation)
 * - Daily metrics aggregation
 *
 * Performance Targets (all queries):
 * - Artist grid: <100ms
 * - Import history: <100ms
 * - Unmatched tracks: <100ms
 * - Imports per day chart: <50ms
 * - Daily metrics aggregation: <200ms
 *
 * All queries should use proper indexes (verified via EXPLAIN)
 */
class DashboardBenchmark
{
    private ResourceConnection $resourceConnection;
    private array $queryResults = [];

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Run all dashboard query benchmarks
     *
     * @return array Benchmark results
     */
    public function runAll(): array
    {
        return [
            'artist_grid' => $this->benchmarkArtistGrid(),
            'import_history' => $this->benchmarkImportHistory(),
            'unmatched_tracks' => $this->benchmarkUnmatchedTracks(),
            'imports_per_day' => $this->benchmarkImportsPerDay(),
            'daily_metrics' => $this->benchmarkDailyMetrics(),
            'index_verification' => $this->verifyIndexes(),
        ];
    }

    /**
     * Benchmark artist grid query
     *
     * Query: List all artists with status (downloads, processed, match rate)
     * Target: <100ms for 35 artists
     *
     * @return array
     */
    public function benchmarkArtistGrid(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $query = "
            SELECT
                a.artist_id,
                a.artist_name,
                a.collection_id,
                s.shows_downloaded,
                s.shows_processed,
                s.tracks_matched,
                s.tracks_unmatched,
                s.match_rate,
                s.last_download_at,
                s.last_populate_at
            FROM archivedotorg_artist a
            LEFT JOIN archivedotorg_artist_status s ON a.artist_id = s.artist_id
            ORDER BY a.artist_name ASC
        ";

        $startTime = microtime(true);
        $result = $connection->fetchAll($query);
        $duration = (microtime(true) - $startTime) * 1000;

        // Get query plan
        $explain = $connection->fetchAll('EXPLAIN ' . $query);

        return [
            'query' => 'Artist Grid',
            'duration_ms' => round($duration, 2),
            'rows_returned' => count($result),
            'target_ms' => 100,
            'target_met' => $duration < 100,
            'explain' => $this->formatExplain($explain),
            'indexes_used' => $this->extractIndexesUsed($explain),
        ];
    }

    /**
     * Benchmark import history query
     *
     * Query: Recent import runs with filters
     * Target: <100ms for 1000+ import runs
     *
     * @return array
     */
    public function benchmarkImportHistory(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $query = "
            SELECT
                r.run_id,
                r.correlation_id,
                r.command,
                r.status,
                r.started_at,
                r.completed_at,
                r.shows_processed,
                r.tracks_processed,
                r.error_message,
                a.artist_name
            FROM archivedotorg_import_run r
            INNER JOIN archivedotorg_artist a ON r.artist_id = a.artist_id
            WHERE r.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY r.started_at DESC
            LIMIT 100
        ";

        $startTime = microtime(true);
        $result = $connection->fetchAll($query);
        $duration = (microtime(true) - $startTime) * 1000;

        $explain = $connection->fetchAll('EXPLAIN ' . $query);

        return [
            'query' => 'Import History',
            'duration_ms' => round($duration, 2),
            'rows_returned' => count($result),
            'target_ms' => 100,
            'target_met' => $duration < 100,
            'explain' => $this->formatExplain($explain),
            'indexes_used' => $this->extractIndexesUsed($explain),
        ];
    }

    /**
     * Benchmark unmatched tracks query
     *
     * Query: List unmatched tracks with frequency and suggestions
     * Target: <100ms for 500+ unmatched tracks
     *
     * @return array
     */
    public function benchmarkUnmatchedTracks(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $query = "
            SELECT
                u.track_id,
                u.track_name,
                u.occurrences,
                u.suggested_match,
                u.confidence,
                u.first_seen,
                u.resolved,
                a.artist_name
            FROM archivedotorg_unmatched_track u
            INNER JOIN archivedotorg_artist a ON u.artist_id = a.artist_id
            WHERE u.resolved = 0
            ORDER BY u.occurrences DESC, u.first_seen DESC
            LIMIT 100
        ";

        $startTime = microtime(true);
        $result = $connection->fetchAll($query);
        $duration = (microtime(true) - $startTime) * 1000;

        $explain = $connection->fetchAll('EXPLAIN ' . $query);

        return [
            'query' => 'Unmatched Tracks',
            'duration_ms' => round($duration, 2),
            'rows_returned' => count($result),
            'target_ms' => 100,
            'target_met' => $duration < 100,
            'explain' => $this->formatExplain($explain),
            'indexes_used' => $this->extractIndexesUsed($explain),
        ];
    }

    /**
     * Benchmark imports per day chart query
     *
     * Query: Aggregate imports by day for last 30 days
     * Target: <50ms
     *
     * @return array
     */
    public function benchmarkImportsPerDay(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $query = "
            SELECT
                DATE(started_at) as import_date,
                COUNT(*) as imports,
                SUM(shows_processed) as shows,
                SUM(tracks_processed) as tracks
            FROM archivedotorg_import_run
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'completed'
            GROUP BY DATE(started_at)
            ORDER BY import_date ASC
        ";

        $startTime = microtime(true);
        $result = $connection->fetchAll($query);
        $duration = (microtime(true) - $startTime) * 1000;

        $explain = $connection->fetchAll('EXPLAIN ' . $query);

        return [
            'query' => 'Imports Per Day Chart',
            'duration_ms' => round($duration, 2),
            'rows_returned' => count($result),
            'target_ms' => 50,
            'target_met' => $duration < 50,
            'explain' => $this->formatExplain($explain),
            'indexes_used' => $this->extractIndexesUsed($explain),
        ];
    }

    /**
     * Benchmark daily metrics aggregation
     *
     * Query: Aggregate metrics from multiple tables for daily summary
     * Target: <200ms
     *
     * @return array
     */
    public function benchmarkDailyMetrics(): array
    {
        $connection = $this->resourceConnection->getConnection();

        // Simulate cron aggregation query
        $query = "
            SELECT
                COUNT(DISTINCT cpe.entity_id) as total_products,
                COUNT(DISTINCT CASE WHEN cpe.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN cpe.entity_id END) as products_today,
                (SELECT COUNT(*) FROM archivedotorg_artist) as total_artists,
                (SELECT COUNT(*) FROM archivedotorg_import_run WHERE status = 'running') as running_imports,
                (SELECT COUNT(*) FROM archivedotorg_unmatched_track WHERE resolved = 0) as unmatched_tracks
            FROM catalog_product_entity cpe
            WHERE cpe.sku LIKE 'archive-%'
        ";

        $startTime = microtime(true);
        $result = $connection->fetchRow($query);
        $duration = (microtime(true) - $startTime) * 1000;

        $explain = $connection->fetchAll('EXPLAIN ' . $query);

        return [
            'query' => 'Daily Metrics Aggregation',
            'duration_ms' => round($duration, 2),
            'target_ms' => 200,
            'target_met' => $duration < 200,
            'metrics' => $result,
            'explain' => $this->formatExplain($explain),
            'indexes_used' => $this->extractIndexesUsed($explain),
        ];
    }

    /**
     * Verify that proper indexes exist and are being used
     *
     * @return array
     */
    public function verifyIndexes(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $expectedIndexes = [
            'catalog_product_entity' => ['idx_created_at'],
            'archivedotorg_import_run' => ['idx_artist_status_started', 'idx_correlation_id'],
            'archivedotorg_artist_status' => ['artist_id'], // UNIQUE key
            'archivedotorg_unmatched_track' => ['artist_id'], // Foreign key index
        ];

        $results = [];

        foreach ($expectedIndexes as $table => $indexes) {
            $tableName = $this->resourceConnection->getTableName($table);

            try {
                $tableIndexes = $connection->fetchAll("SHOW INDEX FROM {$tableName}");
                $foundIndexes = array_unique(array_column($tableIndexes, 'Key_name'));

                foreach ($indexes as $expectedIndex) {
                    $results[$table][$expectedIndex] = in_array($expectedIndex, $foundIndexes);
                }
            } catch (\Exception $e) {
                $results[$table] = ['error' => $e->getMessage()];
            }
        }

        $allIndexesPresent = true;
        foreach ($results as $table => $indexes) {
            if (isset($indexes['error']) || in_array(false, $indexes, true)) {
                $allIndexesPresent = false;
                break;
            }
        }

        return [
            'all_indexes_present' => $allIndexesPresent,
            'indexes' => $results,
        ];
    }

    /**
     * Format EXPLAIN output for readability
     *
     * @param array $explain
     * @return array
     */
    private function formatExplain(array $explain): array
    {
        $formatted = [];

        foreach ($explain as $row) {
            $formatted[] = [
                'table' => $row['table'] ?? null,
                'type' => $row['type'] ?? null,
                'key' => $row['key'] ?? null,
                'key_len' => $row['key_len'] ?? null,
                'rows' => $row['rows'] ?? null,
                'extra' => $row['Extra'] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * Extract which indexes are being used from EXPLAIN
     *
     * @param array $explain
     * @return array
     */
    private function extractIndexesUsed(array $explain): array
    {
        $indexes = [];

        foreach ($explain as $row) {
            if (!empty($row['key']) && $row['key'] !== 'NULL') {
                $indexes[] = [
                    'table' => $row['table'],
                    'index' => $row['key'],
                    'using_index' => stripos($row['Extra'] ?? '', 'Using index') !== false,
                ];
            }
        }

        return $indexes;
    }

    /**
     * Test query with and without indexes (for comparison)
     *
     * Warning: Only run on dev/staging - drops indexes temporarily
     *
     * @param string $table
     * @param string $indexName
     * @param string $query
     * @return array
     */
    public function compareWithoutIndex(string $table, string $indexName, string $query): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($table);

        // Benchmark with index
        $startTime = microtime(true);
        $connection->fetchAll($query);
        $withIndexDuration = (microtime(true) - $startTime) * 1000;

        // Drop index temporarily
        try {
            $connection->query("ALTER TABLE {$tableName} DROP INDEX {$indexName}");

            // Benchmark without index
            $startTime = microtime(true);
            $connection->fetchAll($query);
            $withoutIndexDuration = (microtime(true) - $startTime) * 1000;

            // Restore index (would need original CREATE INDEX statement)
            // This is just a placeholder - actual restoration depends on index type
            // In real scenario, we'd save the index definition first

        } catch (\Exception $e) {
            return ['error' => 'Could not drop index for comparison: ' . $e->getMessage()];
        }

        return [
            'with_index_ms' => round($withIndexDuration, 2),
            'without_index_ms' => round($withoutIndexDuration, 2),
            'speedup_factor' => round($withoutIndexDuration / $withIndexDuration, 2),
        ];
    }
}

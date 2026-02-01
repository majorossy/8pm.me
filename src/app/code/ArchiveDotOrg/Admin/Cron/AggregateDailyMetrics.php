<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Cron;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Daily Metrics Aggregation Cron Job
 * Runs at 4 AM daily to aggregate import statistics into daily_metrics table
 */
class AggregateDailyMetrics
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->logger->info('Starting daily metrics aggregation');

            $connection = $this->resourceConnection->getConnection();
            $importRunTable = $this->resourceConnection->getTableName('archivedotorg_import_run');
            $artistStatusTable = $this->resourceConnection->getTableName('archivedotorg_artist_status');
            $dailyMetricsTable = $this->resourceConnection->getTableName('archivedotorg_daily_metrics');

            // Get yesterday's date
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            // Check if metrics for yesterday already exist
            $select = $connection->select()
                ->from($dailyMetricsTable, ['COUNT(*)'])
                ->where('date = ?', $yesterday);

            if ($connection->fetchOne($select) > 0) {
                $this->logger->info("Daily metrics for {$yesterday} already exist, skipping aggregation");
                return;
            }

            // Aggregate metrics from import_run table
            $metrics = $this->aggregateImportMetrics($connection, $importRunTable, $yesterday);

            // Aggregate match rate from artist_status table
            $matchMetrics = $this->aggregateMatchMetrics($connection, $artistStatusTable);

            // Combine metrics
            $dailyMetrics = array_merge($metrics, $matchMetrics);
            $dailyMetrics['date'] = $yesterday;

            // Insert into daily_metrics table
            $connection->insert($dailyMetricsTable, $dailyMetrics);

            $this->logger->info(
                "Daily metrics aggregated successfully for {$yesterday}",
                $dailyMetrics
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error aggregating daily metrics: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Aggregate import run metrics for the given date
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $tableName
     * @param string $date
     * @return array
     */
    private function aggregateImportMetrics($connection, string $tableName, string $date): array
    {
        // Count completed imports
        $select = $connection->select()
            ->from($tableName, [
                'imports_count' => 'COUNT(*)',
                'shows_imported' => 'SUM(shows_processed)',
                'tracks_imported' => 'SUM(tracks_processed)',
                'avg_duration' => 'AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at))'
            ])
            ->where('DATE(completed_at) = ?', $date)
            ->where('status = ?', 'completed');

        $result = $connection->fetchRow($select);

        return [
            'imports_count' => (int) ($result['imports_count'] ?? 0),
            'shows_imported' => (int) ($result['shows_imported'] ?? 0),
            'tracks_imported' => (int) ($result['tracks_imported'] ?? 0),
            'avg_import_duration' => $result['avg_duration'] ? round((float) $result['avg_duration']) : 0
        ];
    }

    /**
     * Aggregate match rate metrics
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $tableName
     * @return array
     */
    private function aggregateMatchMetrics($connection, string $tableName): array
    {
        $select = $connection->select()
            ->from($tableName, [
                'total_matched' => 'SUM(matched_tracks)',
                'total_unmatched' => 'SUM(unmatched_tracks)',
                'avg_match_rate' => 'AVG(match_rate_percent)'
            ]);

        $result = $connection->fetchRow($select);

        $totalMatched = (int) ($result['total_matched'] ?? 0);
        $totalUnmatched = (int) ($result['total_unmatched'] ?? 0);
        $total = $totalMatched + $totalUnmatched;

        return [
            'tracks_matched' => $totalMatched,
            'tracks_unmatched' => $totalUnmatched,
            'match_rate' => $total > 0 ? round(($totalMatched / $total) * 100, 2) : 0
        ];
    }
}

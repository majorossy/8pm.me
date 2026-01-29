<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Create Daily Metrics Table
 *
 * Pre-aggregated time-series metrics for fast dashboard chart queries.
 * Enables "Imports This Week" chart queries in <10ms.
 */
class CreateDailyMetricsTable implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [\ArchiveDotOrg\Core\Setup\Patch\Schema\CreateArtistTable::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('archivedotorg_daily_metrics');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                // Primary key
                ->addColumn(
                    'metric_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Metric ID'
                )
                ->addColumn(
                    'metric_date',
                    Table::TYPE_DATE,
                    null,
                    ['nullable' => false],
                    'Metric Date'
                )
                // Artist reference (NULL = all artists aggregate)
                ->addColumn(
                    'artist_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Artist ID (NULL = All Artists)'
                )
                ->addColumn(
                    'artist_name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Artist Name (Backward Compat)'
                )
                // Volume metrics
                ->addColumn(
                    'shows_downloaded',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Shows Downloaded'
                )
                ->addColumn(
                    'tracks_imported',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Tracks Imported'
                )
                ->addColumn(
                    'tracks_matched',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Tracks Matched'
                )
                ->addColumn(
                    'tracks_failed',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Tracks Failed'
                )
                // Performance metrics
                ->addColumn(
                    'avg_throughput_per_sec',
                    Table::TYPE_DECIMAL,
                    '10,2',
                    ['nullable' => true],
                    'Average Throughput Per Second'
                )
                ->addColumn(
                    'total_processing_time_sec',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Total Processing Time (Seconds)'
                )
                // Quality metrics
                ->addColumn(
                    'match_rate_percent',
                    Table::TYPE_DECIMAL,
                    '5,2',
                    ['nullable' => true],
                    'Match Rate Percentage'
                )
                ->addColumn(
                    'error_rate_percent',
                    Table::TYPE_DECIMAL,
                    '5,2',
                    ['nullable' => true],
                    'Error Rate Percentage'
                )
                // API metrics
                ->addColumn(
                    'api_calls_count',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'API Calls Count'
                )
                ->addColumn(
                    'api_avg_latency_ms',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'API Average Latency (ms)'
                )
                ->addColumn(
                    'api_error_count',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'API Error Count'
                )
                // Timestamps
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                // Unique index
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['metric_date', 'artist_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['metric_date', 'artist_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                // Regular indexes
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['metric_date']),
                    ['metric_date']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id', 'metric_date']),
                    ['artist_id', 'metric_date']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_name', 'metric_date']),
                    ['artist_name', 'metric_date']
                )
                ->setComment('Pre-Aggregated Daily Metrics for Dashboard Charts');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}

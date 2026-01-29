<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Create Import Run Audit Trail Table
 *
 * Logs all import command executions for dashboard history grid and debugging.
 * Enables admin monitoring of import jobs, performance metrics, and troubleshooting.
 */
class CreateImportRunTable implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        // Depends on artist table from Phase 0
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
        $tableName = $this->schemaSetup->getTable('archivedotorg_import_run');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                // Primary key
                ->addColumn(
                    'run_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Run ID'
                )
                // Unique identifiers
                ->addColumn(
                    'uuid',
                    Table::TYPE_TEXT,
                    36,
                    ['nullable' => false],
                    'Unique Run Identifier'
                )
                ->addColumn(
                    'correlation_id',
                    Table::TYPE_TEXT,
                    36,
                    ['nullable' => false],
                    'Correlation ID for Log Tracing'
                )
                // Artist reference
                ->addColumn(
                    'artist_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Artist ID (FK)'
                )
                // Backward compatibility
                ->addColumn(
                    'artist_name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Artist Name (Backward Compat)'
                )
                ->addColumn(
                    'collection_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Collection ID (Backward Compat)'
                )
                // Command details
                ->addColumn(
                    'command_name',
                    Table::TYPE_TEXT,
                    100,
                    ['nullable' => false],
                    'Command Name'
                )
                ->addColumn(
                    'command_args',
                    Table::TYPE_TEXT,
                    '64K',
                    ['nullable' => true],
                    'Command Arguments (JSON)'
                )
                // Execution context
                ->addColumn(
                    'started_by',
                    Table::TYPE_TEXT,
                    100,
                    ['nullable' => false, 'default' => 'cli'],
                    'Started By (cli, cron, admin:user)'
                )
                ->addColumn(
                    'started_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false],
                    'Started At'
                )
                ->addColumn(
                    'completed_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Completed At'
                )
                // Status
                ->addColumn(
                    'status',
                    Table::TYPE_TEXT,
                    20,
                    ['nullable' => false, 'default' => 'queued'],
                    'Status (queued, running, completed, partial, failed, cancelled)'
                )
                ->addColumn(
                    'exit_code',
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => true],
                    'Process Exit Code'
                )
                // Metrics
                ->addColumn(
                    'total_items',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Total Items to Process'
                )
                ->addColumn(
                    'items_processed',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Items Processed'
                )
                ->addColumn(
                    'items_successful',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Items Successful'
                )
                ->addColumn(
                    'items_failed',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Items Failed'
                )
                ->addColumn(
                    'items_skipped',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Items Skipped'
                )
                // Performance
                ->addColumn(
                    'duration_seconds',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Duration in Seconds'
                )
                ->addColumn(
                    'throughput_per_sec',
                    Table::TYPE_DECIMAL,
                    '10,2',
                    ['nullable' => true],
                    'Throughput Per Second'
                )
                ->addColumn(
                    'avg_item_time_ms',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Average Item Time (ms)'
                )
                ->addColumn(
                    'memory_peak_mb',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Peak Memory Usage (MB)'
                )
                // Logs
                ->addColumn(
                    'log_output',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    ['nullable' => true],
                    'Full CLI Output'
                )
                ->addColumn(
                    'error_message',
                    Table::TYPE_TEXT,
                    '64K',
                    ['nullable' => true],
                    'Error Message'
                )
                ->addColumn(
                    'error_stacktrace',
                    Table::TYPE_TEXT,
                    '64K',
                    ['nullable' => true],
                    'Error Stack Trace'
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
                    $this->schemaSetup->getIdxName($tableName, ['uuid'], AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['uuid'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                // Regular indexes
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id']),
                    ['artist_id']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_name']),
                    ['artist_name']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['status']),
                    ['status']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['started_at']),
                    ['started_at']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['correlation_id']),
                    ['correlation_id']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['command_name']),
                    ['command_name']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['started_by']),
                    ['started_by']
                )
                // Composite indexes for dashboard queries
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id', 'status', 'started_at']),
                    ['artist_id', 'status', 'started_at']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id', 'command_name', 'started_at']),
                    ['artist_id', 'command_name', 'started_at']
                )
                ->setComment('Audit Trail of Archive.org Import Command Executions');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Create Artist Status Summary Table
 *
 * Pre-aggregated statistics per artist for dashboard performance.
 * Enables artist grid queries in <10ms instead of seconds.
 */
class CreateArtistStatusTable implements SchemaPatchInterface
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
        $tableName = $this->schemaSetup->getTable('archivedotorg_artist_status');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                // Primary key
                ->addColumn(
                    'status_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Status ID'
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
                // Configuration
                ->addColumn(
                    'has_yaml_config',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => false],
                    'Has YAML Configuration'
                )
                ->addColumn(
                    'yaml_album_count',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'YAML Album Count'
                )
                ->addColumn(
                    'yaml_track_count',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'YAML Track Count'
                )
                // Archive.org totals
                ->addColumn(
                    'archive_total_shows',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Total Shows on Archive.org'
                )
                ->addColumn(
                    'archive_total_recordings',
                    Table::TYPE_BIGINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Total Recordings (Multiple Per Show)'
                )
                // Downloaded (JSON files)
                ->addColumn(
                    'downloaded_shows',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Downloaded Shows Count'
                )
                ->addColumn(
                    'cache_size_bytes',
                    Table::TYPE_BIGINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Total JSON Cache Size (Bytes)'
                )
                // Imported (Magento products)
                ->addColumn(
                    'imported_tracks',
                    Table::TYPE_BIGINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Imported Tracks Count'
                )
                ->addColumn(
                    'matched_tracks',
                    Table::TYPE_BIGINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Matched Tracks Count'
                )
                ->addColumn(
                    'unmatched_tracks',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Unmatched Tracks Count'
                )
                // Quality metrics
                ->addColumn(
                    'match_rate_percent',
                    Table::TYPE_DECIMAL,
                    '5,2',
                    ['nullable' => false, 'default' => '0.00'],
                    'Match Rate Percentage'
                )
                ->addColumn(
                    'artwork_coverage_percent',
                    Table::TYPE_DECIMAL,
                    '5,2',
                    ['nullable' => false, 'default' => '0.00'],
                    'Artwork Coverage Percentage'
                )
                // Timestamps
                ->addColumn(
                    'last_download_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Last Download At'
                )
                ->addColumn(
                    'last_populate_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Last Populate At'
                )
                ->addColumn(
                    'last_full_sync_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Last Full Sync At'
                )
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
                // Unique indexes
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['artist_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_name'], AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['artist_name'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                // Regular indexes
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['collection_id']),
                    ['collection_id']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['match_rate_percent']),
                    ['match_rate_percent']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['last_download_at']),
                    ['last_download_at']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['last_populate_at']),
                    ['last_populate_at']
                )
                ->setComment('Pre-Aggregated Statistics Per Artist for Dashboard');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}

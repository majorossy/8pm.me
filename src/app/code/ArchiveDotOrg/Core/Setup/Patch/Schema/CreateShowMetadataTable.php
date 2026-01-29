<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Create Show Metadata Table
 * 
 * Addresses Fix #34-37: Extract large JSON blobs from EAV to improve performance
 * Moves show_reviews_json and show_workable_servers from EAV to dedicated table
 */
class CreateShowMetadataTable implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [CreateArtistTable::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('archivedotorg_show_metadata');
        $artistTableName = $this->schemaSetup->getTable('archivedotorg_artist');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'metadata_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Metadata ID'
                )
                ->addColumn(
                    'show_identifier',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Archive.org Show Identifier'
                )
                ->addColumn(
                    'artist_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Artist ID'
                )
                ->addColumn(
                    'reviews_json',
                    Table::TYPE_TEXT,
                    '2M',
                    ['nullable' => true],
                    'Reviews JSON'
                )
                ->addColumn(
                    'workable_servers',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'Workable Servers JSON'
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
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['show_identifier'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['show_identifier'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id']),
                    ['artist_id']
                )
                ->addForeignKey(
                    $this->schemaSetup->getFkName($tableName, 'artist_id', $artistTableName, 'artist_id'),
                    'artist_id',
                    $artistTableName,
                    'artist_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Archive.org Show Metadata Storage');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}

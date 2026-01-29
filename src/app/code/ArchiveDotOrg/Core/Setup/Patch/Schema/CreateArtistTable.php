<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Create Artist Normalization Table
 * 
 * Addresses Fix #1: Single source of truth for artist data
 * Prevents duplicate artist names with inconsistent casing/spelling
 */
class CreateArtistTable implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('archivedotorg_artist');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'artist_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Artist ID'
                )
                ->addColumn(
                    'artist_name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Artist Name'
                )
                ->addColumn(
                    'collection_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Archive.org Collection ID'
                )
                ->addColumn(
                    'url_key',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'URL Key'
                )
                ->addColumn(
                    'yaml_file_path',
                    Table::TYPE_TEXT,
                    500,
                    ['nullable' => true],
                    'Path to YAML Config File'
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
                    $this->schemaSetup->getIdxName($tableName, ['artist_name'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['artist_name'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['collection_id'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['collection_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['url_key']),
                    ['url_key']
                )
                ->setComment('Archive.org Artist Master Table');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}

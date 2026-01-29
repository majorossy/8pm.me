<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Add Extended Stats Columns to Artist Status Table
 *
 * Adds total_hours and total_venues columns for comprehensive artist statistics.
 */
class UpdateArtistStatusTableAddStats implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [CreateArtistStatusTable::class];
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

        // Add total_hours column
        if (!$connection->tableColumnExists($tableName, 'total_hours')) {
            $connection->addColumn(
                $tableName,
                'total_hours',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Total Hours of Audio Content',
                    'after' => 'imported_tracks'
                ]
            );
        }

        // Add total_venues column
        if (!$connection->tableColumnExists($tableName, 'total_venues')) {
            $connection->addColumn(
                $tableName,
                'total_venues',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Total Unique Venues',
                    'after' => 'total_hours'
                ]
            );
        }

        $this->schemaSetup->endSetup();
    }
}

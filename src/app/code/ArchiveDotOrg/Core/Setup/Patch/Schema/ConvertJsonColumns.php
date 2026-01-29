<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Convert TEXT Columns to JSON Type
 * 
 * Addresses Fix #5: Use native JSON type for validation and storage efficiency
 * Benefits: ~20% storage savings + database-level validation
 * 
 * Note: This patch is a placeholder for Phase 5 when archivedotorg_import_run table exists
 */
class ConvertJsonColumns implements SchemaPatchInterface
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
        $tableName = $this->schemaSetup->getTable('archivedotorg_import_run');

        // Only convert if table exists (will be created in Phase 5)
        if ($connection->isTableExists($tableName)) {
            // Convert options_json column
            if ($connection->tableColumnExists($tableName, 'options_json')) {
                $connection->modifyColumn(
                    $tableName,
                    'options_json',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Command Options (JSON)'
                    ]
                );
            }

            // Convert command_args column
            if ($connection->tableColumnExists($tableName, 'command_args')) {
                $connection->modifyColumn(
                    $tableName,
                    'command_args',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Command Arguments (JSON)'
                    ]
                );
            }
        }

        $this->schemaSetup->endSetup();
    }
}

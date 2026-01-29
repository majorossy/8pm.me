<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Add Dashboard Performance Indexes
 * 
 * Addresses Fix #7, #18, #19: Improve query performance for dashboard
 * Target: Dashboard loads in <100ms with 186k products
 */
class AddDashboardIndexes implements SchemaPatchInterface
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
        
        // Add index to catalog_product_entity for recently created products
        $catalogProductTable = $this->schemaSetup->getTable('catalog_product_entity');
        $indexName = $this->schemaSetup->getIdxName($catalogProductTable, ['created_at']);

        $indexes = $connection->getIndexList($catalogProductTable);
        if (!isset($indexes[$indexName])) {
            $connection->addIndex(
                $catalogProductTable,
                $indexName,
                ['created_at']
            );
        }

        // Note: Additional indexes for archivedotorg_import_run and other tables
        // will be created when those tables are added in Phase 5

        $this->schemaSetup->endSetup();
    }
}

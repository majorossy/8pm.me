<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Migrate From Legacy Module Data Patch
 *
 * Handles data migration from the legacy ArchiveDotOrg modules.
 * This patch runs after the attribute patches and verifies data integrity.
 */
class MigrateFromLegacyModule implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private LoggerInterface $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();

        // Log migration start
        $this->logger->info('ArchiveDotOrg Core: Starting migration from legacy modules');

        // Verify product attributes exist
        $this->verifyProductAttributes($connection);

        // Verify category attributes exist
        $this->verifyCategoryAttributes($connection);

        // Log product count with Archive.org data
        $this->logMigrationStats($connection);

        $this->logger->info('ArchiveDotOrg Core: Migration verification completed');

        return $this;
    }

    /**
     * Verify that product attributes exist
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return void
     */
    private function verifyProductAttributes($connection): void
    {
        $requiredAttributes = [
            'title', 'length', 'identifier', 'song_url', 'dir',
            'show_name', 'server_one', 'show_year', 'show_venue',
            'archive_collection'
        ];

        $select = $connection->select()
            ->from(
                $this->moduleDataSetup->getTable('eav_attribute'),
                ['attribute_code']
            )
            ->where('entity_type_id = ?', 4) // Product entity type
            ->where('attribute_code IN (?)', $requiredAttributes);

        $existing = $connection->fetchCol($select);
        $missing = array_diff($requiredAttributes, $existing);

        if (!empty($missing)) {
            $this->logger->warning(
                'ArchiveDotOrg Core: Missing product attributes',
                ['missing' => $missing]
            );
        } else {
            $this->logger->info(
                'ArchiveDotOrg Core: All required product attributes exist',
                ['count' => count($existing)]
            );
        }
    }

    /**
     * Verify that category attributes exist
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return void
     */
    private function verifyCategoryAttributes($connection): void
    {
        $requiredAttributes = [
            'artist_collection_rss', 'is_artist', 'is_album', 'is_song', 'artist'
        ];

        $select = $connection->select()
            ->from(
                $this->moduleDataSetup->getTable('eav_attribute'),
                ['attribute_code']
            )
            ->where('entity_type_id = ?', 3) // Category entity type
            ->where('attribute_code IN (?)', $requiredAttributes);

        $existing = $connection->fetchCol($select);
        $missing = array_diff($requiredAttributes, $existing);

        if (!empty($missing)) {
            $this->logger->warning(
                'ArchiveDotOrg Core: Missing category attributes',
                ['missing' => $missing]
            );
        } else {
            $this->logger->info(
                'ArchiveDotOrg Core: All required category attributes exist',
                ['count' => count($existing)]
            );
        }
    }

    /**
     * Log migration statistics
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return void
     */
    private function logMigrationStats($connection): void
    {
        // Count products with identifier (Archive.org products)
        $productTable = $this->moduleDataSetup->getTable('catalog_product_entity');
        $varcharTable = $this->moduleDataSetup->getTable('catalog_product_entity_varchar');
        $attributeTable = $this->moduleDataSetup->getTable('eav_attribute');

        $select = $connection->select()
            ->from(
                ['v' => $varcharTable],
                ['count' => new \Zend_Db_Expr('COUNT(DISTINCT v.entity_id)')]
            )
            ->join(
                ['a' => $attributeTable],
                'v.attribute_id = a.attribute_id',
                []
            )
            ->where('a.attribute_code = ?', 'identifier')
            ->where('v.value IS NOT NULL')
            ->where('v.value != ?', '');

        $productCount = (int) $connection->fetchOne($select);

        $this->logger->info(
            'ArchiveDotOrg Core: Found products with Archive.org identifiers',
            ['count' => $productCount]
        );

        // Count artist categories
        $categoryIntTable = $this->moduleDataSetup->getTable('catalog_category_entity_int');

        $select = $connection->select()
            ->from(
                ['c' => $categoryIntTable],
                ['count' => new \Zend_Db_Expr('COUNT(DISTINCT c.entity_id)')]
            )
            ->join(
                ['a' => $attributeTable],
                'c.attribute_id = a.attribute_id',
                []
            )
            ->where('a.attribute_code = ?', 'is_artist')
            ->where('c.value = ?', 1);

        $artistCount = (int) $connection->fetchOne($select);

        $this->logger->info(
            'ArchiveDotOrg Core: Found artist categories',
            ['count' => $artistCount]
        );
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateProductAttributes::class,
            CreateCategoryAttributes::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

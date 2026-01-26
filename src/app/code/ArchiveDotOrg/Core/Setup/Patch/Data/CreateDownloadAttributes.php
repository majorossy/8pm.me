<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Create Download Statistics Attributes Data Patch
 *
 * Creates product attributes for Archive.org download statistics:
 * - archive_downloads: Total download count
 * - archive_downloads_week: Downloads in the past week
 * - archive_downloads_month: Downloads in the past month
 */
class CreateDownloadAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Total Downloads
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'archive_downloads')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'archive_downloads',
                [
                    'type' => 'int',
                    'label' => 'Archive.org Downloads',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 51,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Downloads This Week
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'archive_downloads_week')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'archive_downloads_week',
                [
                    'type' => 'int',
                    'label' => 'Archive.org Downloads (Week)',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 52,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => true,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Downloads This Month
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'archive_downloads_month')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'archive_downloads_month',
                [
                    'type' => 'int',
                    'label' => 'Archive.org Downloads (Month)',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 53,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => true,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function revert(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->removeAttribute(Product::ENTITY, 'archive_downloads');
        $eavSetup->removeAttribute(Product::ENTITY, 'archive_downloads_week');
        $eavSetup->removeAttribute(Product::ENTITY, 'archive_downloads_month');
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [CreateProductAttributes::class];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

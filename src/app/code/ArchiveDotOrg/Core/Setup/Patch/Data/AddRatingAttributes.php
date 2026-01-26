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
 * Add Archive.org Rating Attributes Data Patch
 *
 * Creates product attributes for Archive.org review ratings.
 */
class AddRatingAttributes implements DataPatchInterface, PatchRevertableInterface
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

        // Average rating (decimal, nullable)
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'archive_avg_rating')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'archive_avg_rating',
                [
                    'type' => 'decimal',
                    'label' => 'Archive.org Average Rating',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 40,
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
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Number of reviews (int, default 0)
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'archive_num_reviews')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'archive_num_reviews',
                [
                    'type' => 'int',
                    'label' => 'Archive.org Review Count',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 41,
                    'default' => 0,
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
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
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

        $eavSetup->removeAttribute(Product::ENTITY, 'archive_avg_rating');
        $eavSetup->removeAttribute(Product::ENTITY, 'archive_num_reviews');
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

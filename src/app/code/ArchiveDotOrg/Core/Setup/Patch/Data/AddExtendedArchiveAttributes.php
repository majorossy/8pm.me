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
 * Add Extended Archive.org Attributes Data Patch
 *
 * Creates additional product attributes for Archive.org metadata:
 *
 * Track-level attributes:
 * - track_md5: MD5 hash
 * - track_acoustid: AcoustID fingerprint
 * - track_bitrate: Bitrate in kbps
 *
 * Show-level attributes:
 * - show_files_count: Number of files in show
 * - show_total_size: Total size in bytes
 * - show_uploader: Archive.org uploader
 * - show_created_date: Archive.org creation date
 * - show_last_updated: Archive.org last update date
 */
class AddExtendedArchiveAttributes implements DataPatchInterface, PatchRevertableInterface
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

        // Track MD5 Hash
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'track_md5')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'track_md5',
                [
                    'type' => 'varchar',
                    'label' => 'Track MD5 Hash',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 61,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => false
                ]
            );
        }

        // Track AcoustID Fingerprint
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'track_acoustid')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'track_acoustid',
                [
                    'type' => 'varchar',
                    'label' => 'Track AcoustID Fingerprint',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 62,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => false
                ]
            );
        }

        // Track Bitrate
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'track_bitrate')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'track_bitrate',
                [
                    'type' => 'int',
                    'label' => 'Track Bitrate (kbps)',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 63,
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

        // Show Files Count
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'show_files_count')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'show_files_count',
                [
                    'type' => 'int',
                    'label' => 'Show Files Count',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 71,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Show Total Size
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'show_total_size')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'show_total_size',
                [
                    'type' => 'int',
                    'label' => 'Show Total Size (bytes)',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 72,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Show Uploader
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'show_uploader')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'show_uploader',
                [
                    'type' => 'varchar',
                    'label' => 'Show Uploader',
                    'input' => 'text',
                    'group' => 'Product Details',
                    'sort_order' => 73,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Show Created Date
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'show_created_date')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'show_created_date',
                [
                    'type' => 'datetime',
                    'label' => 'Show Created Date',
                    'input' => 'date',
                    'group' => 'Product Details',
                    'sort_order' => 74,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'is_html_allowed_on_frontend' => false,
                    'visible_on_front' => true
                ]
            );
        }

        // Show Last Updated Date
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'show_last_updated')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'show_last_updated',
                [
                    'type' => 'datetime',
                    'label' => 'Show Last Updated',
                    'input' => 'date',
                    'group' => 'Product Details',
                    'sort_order' => 75,
                    'default' => null,
                    'source' => '',
                    'backend' => '',
                    'frontend' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'required' => false,
                    'unique' => false,
                    'used_in_product_listing' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'is_used_in_grid' => true,
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

        $eavSetup->removeAttribute(Product::ENTITY, 'track_md5');
        $eavSetup->removeAttribute(Product::ENTITY, 'track_acoustid');
        $eavSetup->removeAttribute(Product::ENTITY, 'track_bitrate');
        $eavSetup->removeAttribute(Product::ENTITY, 'show_files_count');
        $eavSetup->removeAttribute(Product::ENTITY, 'show_total_size');
        $eavSetup->removeAttribute(Product::ENTITY, 'show_uploader');
        $eavSetup->removeAttribute(Product::ENTITY, 'show_created_date');
        $eavSetup->removeAttribute(Product::ENTITY, 'show_last_updated');
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

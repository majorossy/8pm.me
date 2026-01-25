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
 * Create Product Attributes Data Patch
 *
 * Creates all product attributes needed for Archive.org track data.
 * Replaces the deprecated InstallData from ArchiveDotOrg_ProductAttributes module.
 */
class CreateProductAttributes implements DataPatchInterface, PatchRevertableInterface
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

        // Text attributes
        $textAttributes = [
            'title' => 'Title',
            'length' => 'Length',
            'identifier' => 'Identifier',
            'song_url' => 'Song URL',
            'dir' => 'Directory',
            'notes' => 'Notes',
            'show_name' => 'Show Name',
            'server_one' => 'Server 1',
            'server_two' => 'Server 2',
            'lineage' => 'Lineage',
            'guid' => 'GUID'
        ];

        foreach ($textAttributes as $code => $label) {
            $this->createTextAttribute($eavSetup, $code, $label);
        }

        // Date attributes
        $this->createDateAttribute($eavSetup, 'show_pub_date', 'Publication Date');
        $this->createDateAttribute($eavSetup, 'show_date', 'Show Date');

        // Dropdown attributes (select with dynamic options)
        $dropdownAttributes = [
            'show_year' => 'Show Year',
            'show_venue' => 'Show Venue',
            'show_taper' => 'Show Taper',
            'show_transferer' => 'Show Transferer',
            'archive_collection' => 'Archive Collection',
            'collection' => 'Collection',
            'album' => 'Album',
            'album_track' => 'Album Track',
            'artist' => 'Artist'
        ];

        foreach ($dropdownAttributes as $code => $label) {
            $this->createDropdownAttribute($eavSetup, $code, $label);
        }

        return $this;
    }

    /**
     * Create a text attribute
     *
     * @param EavSetup $eavSetup
     * @param string $code
     * @param string $label
     * @return void
     */
    private function createTextAttribute(EavSetup $eavSetup, string $code, string $label): void
    {
        // Check if attribute already exists
        if ($eavSetup->getAttributeId(Product::ENTITY, $code)) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'type' => 'varchar',
                'label' => $label,
                'input' => 'text',
                'group' => 'Product Details',
                'sort_order' => 10,
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
                'is_html_allowed_on_frontend' => true,
                'visible_on_front' => true
            ]
        );
    }

    /**
     * Create a date attribute
     *
     * @param EavSetup $eavSetup
     * @param string $code
     * @param string $label
     * @return void
     */
    private function createDateAttribute(EavSetup $eavSetup, string $code, string $label): void
    {
        if ($eavSetup->getAttributeId(Product::ENTITY, $code)) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'type' => 'datetime',
                'label' => $label,
                'input' => 'date',
                'group' => 'Product Details',
                'sort_order' => 20,
                'default' => null,
                'source' => '',
                'backend' => '',
                'frontend' => \Magento\Eav\Model\Entity\Attribute\Frontend\Datetime::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'required' => false,
                'unique' => false,
                'used_in_product_listing' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible' => true,
                'is_html_allowed_on_frontend' => true,
                'visible_on_front' => true
            ]
        );
    }

    /**
     * Create a dropdown (select) attribute
     *
     * @param EavSetup $eavSetup
     * @param string $code
     * @param string $label
     * @return void
     */
    private function createDropdownAttribute(EavSetup $eavSetup, string $code, string $label): void
    {
        if ($eavSetup->getAttributeId(Product::ENTITY, $code)) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'type' => 'int',
                'label' => $label,
                'input' => 'select',
                'group' => 'Product Details',
                'sort_order' => 30,
                'default' => null,
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
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
                'is_html_allowed_on_frontend' => true,
                'visible_on_front' => true
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function revert(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'title', 'length', 'identifier', 'song_url', 'dir', 'notes',
            'show_name', 'server_one', 'server_two', 'lineage', 'guid',
            'show_pub_date', 'show_date', 'show_year', 'show_venue',
            'show_taper', 'show_transferer', 'archive_collection',
            'collection', 'album', 'album_track', 'artist'
        ];

        foreach ($attributes as $code) {
            $eavSetup->removeAttribute(Product::ENTITY, $code);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

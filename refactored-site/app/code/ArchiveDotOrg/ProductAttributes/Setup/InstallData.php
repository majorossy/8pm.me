<?php
namespace ArchiveDotOrg\ProductAttributes\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

class InstallData implements InstallDataInterface
{

    private $eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }

    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'title',
            [
                'type'          => 'varchar',
                'label'         => 'Title',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'length',
            [
                'type'          => 'varchar',
                'label'         => 'Length',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'identifier',
            [
                'type'          => 'varchar',
                'label'         => 'Identifier',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'song_url',
            [
                'type'          => 'varchar',
                'label'         => 'Song Url',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'dir',
            [
                'type'          => 'varchar',
                'label'         => 'Dir',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'notes',
            [
                'type'          => 'varchar',
                'label'         => 'Notes',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_name',
            [
                'type'          => 'varchar',
                'label'         => 'Show Name',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'server_one',
            [
                'type'          => 'varchar',
                'label'         => 'Server 1',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'server_two',
            [
                'type'          => 'varchar',
                'label'         => 'Server 2',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'lineage',
            [
                'type'          => 'varchar',
                'label'         => 'Lineage',
                'input'         => 'text',
                'group'         => 'Product Details',
                'sort_order'    => 1,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );





        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_pub_date',
            [
                'type'          => 'datetime',
                'label'         => 'Publication Date',
                'input'         => 'date',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_date',
            [
                'type'          => 'datetime',
                'label'         => 'Show Date',
                'input'         => 'date',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => '',
                'backend'       => '',
                'frontend'      => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_year',
            [
                'type'          => 'int',
                'label'         => 'Show Date',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_venue',
            [
                'type'          => 'int',
                'label'         => 'Show Venue',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_taper',
            [
                'type'          => 'int',
                'label'         => 'Show Taper',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => '',
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'show_transferer',
            [
                'type'          => 'int',
                'label'         => 'Show Transferer',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'archive_collection',
            [
                'type'          => 'int',
                'label'         => 'Archive Collection',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'collection',
            [
                'type'          => 'int',
                'label'         => 'Collection',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'album',
            [
                'type'          => 'int',
                'label'         => 'Album',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'album_track',
            [
                'type'          => 'int',
                'label'         => 'Album Track',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'artist',
            [
                'type'          => 'int',
                'label'         => 'Artist',
                'input'         => 'select',
                'group'         => 'Product Details',
                'sort_order'    => 10,
                'default'       => null,
                'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'backend'       => '',
                'frontend'      => '',
                'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'                      => false,
                'unique'                        => false,
                'used_in_product_listing'       => true,
                'searchable'                    => false,
                'filterable'                    => false,
                'comparable'                    => false,
                'is_used_in_gird'               => false,
                'is_visible_in_gird'            => false,
                'is_filterable_in_gird'         => false,
                'visible'                       => true,
                'is_html_allowed_on_frontend'   => true,
                'visible_on_frontend'           => true
            ]
        );
    }
}
<?php
namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddExtendedStatsAttributes implements DataPatchInterface
{
    private $eavSetupFactory;
    private $moduleDataSetup;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'band_total_recordings' => [
                'type' => 'int',
                'label' => 'Total Recordings',
                'input' => 'text',
                'sort_order' => 311,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Statistics',
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
            ],
            'band_total_hours' => [
                'type' => 'int',
                'label' => 'Total Hours of Audio',
                'input' => 'text',
                'sort_order' => 312,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Statistics',
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
            ],
            'band_total_venues' => [
                'type' => 'int',
                'label' => 'Total Unique Venues',
                'input' => 'text',
                'sort_order' => 313,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Statistics',
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
            ],
        ];

        foreach ($attributes as $code => $config) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $code,
                $config
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [AddArtistBandDataAttributes::class];
    }

    public function getAliases()
    {
        return [];
    }
}

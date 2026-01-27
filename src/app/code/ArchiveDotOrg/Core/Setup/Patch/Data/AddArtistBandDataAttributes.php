<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Add Artist/Band Data Category Attributes
 *
 * Adds attributes for artist/band biographical and social data.
 */
class AddArtistBandDataAttributes implements DataPatchInterface
{
    /**
     * Artist/band category attributes to create
     */
    private const ATTRIBUTES = [
        'band_formation_date' => [
            'type' => 'varchar',
            'label' => 'Band Formation Date',
            'input' => 'text',
            'required' => false,
            'sort_order' => 100,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Band Information',
        ],
        'band_origin_location' => [
            'type' => 'text',
            'label' => 'Band Origin Location',
            'input' => 'textarea',
            'required' => false,
            'sort_order' => 110,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Band Information',
        ],
        'band_years_active' => [
            'type' => 'varchar',
            'label' => 'Band Years Active',
            'input' => 'text',
            'required' => false,
            'sort_order' => 120,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Band Information',
            'note' => 'Example: 1965-1995, 2002-present',
        ],
        'band_extended_bio' => [
            'type' => 'text',
            'label' => 'Band Extended Biography',
            'input' => 'textarea',
            'required' => false,
            'sort_order' => 130,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Band Information',
        ],
        'band_genres' => [
            'type' => 'text',
            'label' => 'Band Genres',
            'input' => 'textarea',
            'required' => false,
            'sort_order' => 140,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Band Information',
            'note' => 'Comma-separated list of genres',
        ],
        'band_official_website' => [
            'type' => 'varchar',
            'label' => 'Band Official Website',
            'input' => 'text',
            'required' => false,
            'sort_order' => 200,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Social Media',
        ],
        'band_youtube_channel' => [
            'type' => 'varchar',
            'label' => 'YouTube Channel',
            'input' => 'text',
            'required' => false,
            'sort_order' => 210,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Social Media',
        ],
        'band_facebook' => [
            'type' => 'varchar',
            'label' => 'Facebook Page',
            'input' => 'text',
            'required' => false,
            'sort_order' => 220,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Social Media',
        ],
        'band_instagram' => [
            'type' => 'varchar',
            'label' => 'Instagram Handle',
            'input' => 'text',
            'required' => false,
            'sort_order' => 230,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Social Media',
        ],
        'band_twitter' => [
            'type' => 'varchar',
            'label' => 'Twitter/X Handle',
            'input' => 'text',
            'required' => false,
            'sort_order' => 240,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Social Media',
        ],
        'band_total_shows' => [
            'type' => 'int',
            'label' => 'Total Shows on Archive.org',
            'input' => 'text',
            'required' => false,
            'sort_order' => 300,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Statistics',
        ],
        'band_most_played_track' => [
            'type' => 'varchar',
            'label' => 'Most Played Track',
            'input' => 'text',
            'required' => false,
            'sort_order' => 310,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'Statistics',
        ],
    ];

    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

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
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->moduleDataSetup->getConnection()->startSetup();

        // Add all attributes to category entity
        foreach (self::ATTRIBUTES as $attributeCode => $config) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                $attributeCode,
                [
                    'type' => $config['type'],
                    'label' => $config['label'],
                    'input' => $config['input'],
                    'required' => $config['required'],
                    'sort_order' => $config['sort_order'],
                    'global' => $config['global'],
                    'group' => $config['group'],
                    'note' => $config['note'] ?? '',
                    'visible' => true,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => false,
                    'unique' => false,
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
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

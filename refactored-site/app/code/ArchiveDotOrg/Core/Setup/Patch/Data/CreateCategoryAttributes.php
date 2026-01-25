<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Create Category Attributes Data Patch
 *
 * Creates category attributes for artist/album/song classification.
 * Replaces the deprecated InstallData from ArchiveDotOrg_CategoryWork module.
 */
class CreateCategoryAttributes implements DataPatchInterface, PatchRevertableInterface
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

        // Artist Collection RSS Feed URL
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'artist_collection_rss')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'artist_collection_rss',
                [
                    'type' => 'varchar',
                    'label' => 'Artist Feed',
                    'input' => 'text',
                    'sort_order' => 130,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'General Information',
                    'backend' => ''
                ]
            );
        }

        // Is Artist flag
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'is_artist')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'is_artist',
                [
                    'type' => 'int',
                    'label' => 'Is an Artist?',
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 120,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'group' => 'General Information',
                    'backend' => ''
                ]
            );
        }

        // Is Album flag
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'is_album')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'is_album',
                [
                    'type' => 'int',
                    'label' => 'Is an Album?',
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 140,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'group' => 'General Information',
                    'backend' => ''
                ]
            );
        }

        // Is Song flag
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'is_song')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'is_song',
                [
                    'type' => 'int',
                    'label' => 'Is a Song?',
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 150,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'group' => 'General Information',
                    'backend' => ''
                ]
            );
        }

        // Song Track Number
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'song_track_number')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'song_track_number',
                [
                    'type' => 'int',
                    'label' => 'Track #',
                    'input' => 'select',
                    'sort_order' => 160,
                    'source' => \ArchiveDotOrg\Core\Model\Category\Attribute\Source\TrackNumber::class,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'General Information',
                    'backend' => ''
                ]
            );
        }

        // Artist name (text field on category)
        if (!$eavSetup->getAttributeId(Category::ENTITY, 'artist')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'artist',
                [
                    'type' => 'varchar',
                    'label' => 'Artist',
                    'input' => 'text',
                    'sort_order' => 101,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'General Information',
                    'backend' => ''
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

        $attributes = [
            'artist_collection_rss',
            'is_artist',
            'is_album',
            'is_song',
            'song_track_number',
            'artist'
        ];

        foreach ($attributes as $code) {
            $eavSetup->removeAttribute(Category::ENTITY, $code);
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

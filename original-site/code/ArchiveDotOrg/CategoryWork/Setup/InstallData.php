<?php
namespace ArchiveDotOrg\CategoryWork\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

class InstallData implements InstallDataInterface
{

	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}

	public function install(
		ModuleDataSetupInterface $setup,
		ModuleContextInterface $context
	)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'artist_collection_rss',
			[
				'type'         => 'varchar',
				'label'        => 'Artist Feed',
				'input'        => 'text',
				'sort_order'   => 130,
				'source'       => '',
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
			]
		);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'is_artist',
			[
				'type'         => 'int',
				'label'        => 'Is an Artist?',
				'input'        => 'select',
				'source' 	   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'sort_order'   => 120,
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
			]
		);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'is_album',
			[
				'type'         => 'int',
				'label'        => 'Is an Album?',
				'input'        => 'select',
				'source' 	   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'sort_order'   => 140,
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
			]
		);		
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'is_song',
			[
				'type'         => 'int',
				'label'        => 'Is a Song?',
				'input'        => 'select',
				'source'	   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'sort_order'   => 150,				
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
			]
		);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'song_track_number',
			[
				'type'         => 'int',
				'label'        => 'Track #',
				'input'        => 'select',
				'sort_order'   => 160,
				'source'	   => 'ArchiveDotOrg\CategoryWork\Model\Config\Source\Options',
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
    		]
		);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Category::ENTITY,
			'artist',
			[
				'type'         => 'varchar',
				'label'        => 'Artist',
				'input'        => 'text',
				'sort_order'   => 101,
				'source'       => '',
				'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'      => true,
				'required'     => false,
				'user_defined' => false,
				'default'      => null,
				'group'        => '',
				'backend'      => ''
			]
		);
		
	}
}
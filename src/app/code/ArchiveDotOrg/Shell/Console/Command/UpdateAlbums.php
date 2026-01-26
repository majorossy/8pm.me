<?php
namespace ArchiveDotOrg\Shell\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use ArchiveDotOrg\Shell\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\{ObjectManager, State};
use Magento\Catalog\Model\ProductRepository;


class UpdateAlbums extends Command
{
	
	/**
	* @var \VendorName\ExtensionName\Service\ImportImageService
	*/
	protected $importimageservice;
	
	 /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $attributeValues;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\Source\TableFactory
     */
    protected $tableFactory;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory
     */
    protected $optionLabelFactory;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory
     */
    protected $optionFactory;
	
	
	protected $categoryLinkManagement;
	
	
	/**
	* Constructor
	*
	* @param State $state A Magento app State instance
	*
	* @return void
	*/
	protected $_productFactory;
	
	public function __construct(
		State $state, 
		ProductRepositoryInterface $prepo,
		\Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Eav\Model\Entity\Attribute\Source\TableFactory $tableFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory,
        \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagementInterface,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \ArchiveDotOrg\Shell\Service\ImportImageService $importimageservice
	)
	{
		// We cannot use core functions (like saving a product) unless the area
		// code is explicitly set.
		try {
			$state->setAreaCode('adminhtml');
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			// Intentionally left empty.
		}
		parent::__construct();
		$this->importimageservice = $importimageservice;
        $this->attributeRepository = $attributeRepository;
        $this->tableFactory = $tableFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionLabelFactory = $optionLabelFactory;
		$this->categoryLinkManagement = $categoryLinkManagementInterface;;
        $this->optionFactory = $optionFactory;		
		$this->_productFactory = $productFactory;
	}
	
	
	
   protected function configure()
   {
       $this->setName('ArchiveDotOrg:UpdateAlbums');
       $this->setDescription('Demo command line');
       
       parent::configure();
   }
   

   protected function execute(InputInterface $input, OutputInterface $output)
   {
   	
		try{
		    $output->writeln("<info>Starting...</info>");
		   	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
			$subcategories = $categoryFactory->create()->addAttributeToSelect('*');
			//$subcategories->addAttributeToFilter('entity_id', array('gt' => 468))->addAttributeToFilter('entity_id', array('lt' => 487));
			//$subcategories->addAttributeToFilter('entity_id', array('gt' => 2537))->addAttributeToFilter('entity_id', array('lt' => 2620));
			//$subcategories->addAttributeToFilter('entity_id', array('gt' => 705));
			//$subcategories->addAttributeToFilter('entity_id', array('gt' => 199));
			//$subcategories->addAttributeToFilter('entity_id','488');
			$subcategories->addOrder('entity_id','DESC');
			//$subcategories = $categoryObj->getChildrenCategories();
			$__count_cats = 0;
			$__count_shows = 0;
			$__count_songs = 0;
			$output->writeln("<info>   Looping Categories ".count($subcategories)." |</info>");
			$___acount = 0;
			$___bcount = 0;
			$categoryFactory = null;
			unset($categoryFactory);
			$objectManager = null;
			unset($objectManager);			
			foreach($subcategories as $_cat) {
				$___acount = $___acount + 1;
				//$XXXobjectManager = \Magento\Framework\App\ObjectManager::getInstance();
				//$_cat = $XXXobjectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->load($_cat->getId());
				//echo '|'.$_cat->getData('song_track_number');
				if($_cat->getData('is_song') == 1){
					$output->writeln("<info>      Found Song Categories</info>");				
					$X_objectManager = \Magento\Framework\App\ObjectManager::getInstance();					
					$X_productCollection = $X_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');					
					//$X_collection = $X_productCollection->addAttributeToSelect('title')->addAttributeToFilter('archive_collection', $_cat->getData('artist'))
					//echo '|||'. $_cat->getData('artist').'|||';
                    //print_r($_cat->getData());
                    //exit;
					$_archive_collection_id = $this->createOrGetId('archive_collection', $_cat->getData('artist'));
					$X_collection = $X_productCollection->addAttributeToSelect(array('title','sku','name'))->addAttributeToFilter('archive_collection', $_archive_collection_id)->load();
					$output->writeln("<info>          ".$_cat->getData('name')."---".$_archive_collection_id."---------".$_cat->getData('artist')." Starting Track Hunt</info>");
					//echo count($X_collection).'+++';
					//exit;
					foreach($X_collection as $a_song){
                        //print_r($a_song->getData());
					    //exit;
						$sim = 0;
						$perc = 0;
						$sim = similar_text(trim(strtolower($a_song->getTitle())),strtolower($_cat->getData('name')),$perc);							
						$_perc_hold = 0;
						$_perc_hold = number_format($perc,3);							
						if(
							(
								(strpos(trim(metaphone(strtolower($a_song->getTitle()))),metaphone(strtolower($_cat->getData('name'))))!== false)
								&&
								(strlen(metaphone(strtolower($_cat->getData('name')))) >= 4)									
							)
							||
							($_perc_hold >= 75)
							||
							(metaphone(trim(strtolower($a_song->getTitle()))) == metaphone(strtolower($_cat->getData('name'))))
						){
							$___bcount = $___bcount + 1;							
							$product = null;
							unset($product);
							$objectManager = null;
							unset($objectManager);
							
							$product = $this->_productFactory->create()->load($a_song->getId());
														
							$categoryIds = null;
							unset($categoryIds);
							$categoryIds = array($_cat->getId());
							$categoryIds = array_merge($categoryIds, $product->getCategoryIds());
							//print_r($categoryIds);
							
							
							//print_r($categoryIds);
							$this->categoryLinkManagement->assignProductToCategories(
							    $product->getSku(),
							    $categoryIds
							);							
							// save Pretty Title name as drop down   | pretty_ttile
							// save albumn name as drop down   | group_album
							// save artist name as drop down   | group_artist
							// save track_number dropdown like album_trackNumber   | group_track_number
							
							//$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
							//$product = $objectManager->get('Magento\Catalog\Model\Product')->load($a_song->getId());
							
							//$product = $this->_productFactory->create()->load($a_song->getId());
							//$product->setStoreId(0);
							//$product->setWebsiteIds([1]);
							$_da_id = '';
							//$_da_id = $this->createOrGetId('pretty_title', $_cat->getName());
							//echo $_cat->getName().'|'.$_da_id.'|'.$_cat->getParentCategory()->getName();
//							$product->setPrettytitle($_da_id);
//							$product->setAlbum($this->createOrGetId('album', $_cat->getParentCategory()->getName()));
//							$product->setArtist($this->createOrGetId('artist', $_cat->getData('artist')));
//							$product->setTrack($this->createOrGetId('track', str_replace(' ','-',($_cat->getData('artist').'_'.$_cat->getParentCategory()->getName().'_'.$_cat->getData('song_track_number')))));
//							$product->save();
							//$output->writeln("<info>  XXX ".$product->getSku()." Category Track ".$product->getName()."  Show Track ".$product->getTitle()." URL ".$product->getUrlKey()."</info>");
							//$output->writeln("<info>  XXX ".$product->getSku()." Category Track ".$product->getName()."  Show Track ".$product->getTitle()." URL ".$product->getUrlKey()."</info>");
							//$output->writeln("<info>   Album: ".$___acount." Song: ".$___bcount."             Found SKU - ".$a_song->getSku()."</info>");
							$output->writeln("<info>  --- ".$a_song->getSku()." Category Track ".$a_song->getName()."  Show Track ".$a_song->getTitle()."</info>");
														
						}else{
							//echo '.';
							//$output->writeln("<info>  --- ".$a_song->getSku()." Category Track ".$a_song->getName()."  Show Track ".$a_song->getTitle()."</info>");
						}
						$product = null;
						unset($product);
						
					}
					$_archive_collection_id = null;
					unset($_archive_collection_id);
					$X_objectManager = null;
					unset($X_objectManager);
					$X_productCollection = null;
					unset($X_productCollection);
					$X_collection = null;
					unset($X_collection);					
					//sleep(5);				
				}
				//			
				$XXXobjectManager = null;
				unset($XXXobjectManager);				
				$_cat = null;
				unset($_cat);
			}
		 }catch (exception $e){
	   		$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
	   		print_r($product);
	   		$output->writeln('<error>'.$e->getMessage().'</error>');
			$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
	    }
    }

	
	
	protected function startsWith($haystack, $needle)
	{
	     $length = strlen($needle);
	     return (substr($haystack, 0, $length) === $needle);
	}
	
	protected function endsWith($haystack, $needle)
	{
	    $length = strlen($needle);
	    if ($length == 0) {
	        return true;
	    }
	
	    return (substr($haystack, -$length) === $needle);
	}


 	 /**
     * Get attribute by code.
     *
     * @param string $attributeCode
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributeRepository->get($attributeCode);
    }


    /**
     * Find or create a matching attribute option
     *
     * @param string $attributeCode Attribute the option should exist in
     * @param string $label Label to find or add
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
 	public function createOrGetId($attributeCode, $label)
    {
		try{
	        if (strlen($label) < 1) {
	            throw new \Magento\Framework\Exception\LocalizedException(
	                __('Label for %1 must not be empty. |%2|', $attributeCode,$label)
	            );
	        }
	        // Does it already exist?
	        $optionId = $this->getOptionId($attributeCode, $label);
	        if (!$optionId) {
	            // If no, add it.	
	            /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $optionLabel */
	            $optionLabel = $this->optionLabelFactory->create();
	            $optionLabel->setStoreId(0);
	            $optionLabel->setLabel($label);
	            $option = $this->optionFactory->create();
	            $option->setLabel($label);
	            $option->setStoreLabels([$optionLabel]);
	            $option->setSortOrder(0);
	            $option->setIsDefault(false);
	            $this->attributeOptionManagement->add(
	                \Magento\Catalog\Model\Product::ENTITY,
	                $this->getAttribute($attributeCode)->getAttributeId(),
	                $option
	            );
	            // Get the inserted ID. Should be returned from the installer, but it isn't.
	            $optionId = $this->getOptionId($attributeCode, $label, true);
	        }
	
	        return $optionId;
		}catch(exception $e){
        	$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
//	   		print_r($product);
			
	   		$output->writeln('<error> code::'.$attributeCode.' || label::'.$label.'</error>');
	   		$output->writeln('<error>'.$e->getMessage().'</error>');
			$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
		}
    }


    /**
     * Find the ID of an option matching $label, if any.
     *
     * @param string $attributeCode Attribute code
     * @param string $label Label to find
     * @param bool $force If true, will fetch the options even if they're already cached.
     * @return int|false
     */
    public function getOptionId($attributeCode, $label, $force = false)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->getAttribute($attributeCode);

        // Build option array if necessary
        if ($force === true || !isset($this->attributeValues[ $attribute->getAttributeId() ])) {
            $this->attributeValues[ $attribute->getAttributeId() ] = [];

            // We have to generate a new sourceModel instance each time through to prevent it from
            // referencing its _options cache. No other way to get it to pick up newly-added values.

            /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceModel */
            $sourceModel = $this->tableFactory->create();
            $sourceModel->setAttribute($attribute);

            foreach ($sourceModel->getAllOptions() as $option) {
                $this->attributeValues[ $attribute->getAttributeId() ][ $option['label'] ] = $option['value'];
            }
        }

        // Return option ID if exists
        if (isset($this->attributeValues[ $attribute->getAttributeId() ][ $label ])) {
            return $this->attributeValues[ $attribute->getAttributeId() ][ $label ];
        }

        // Return false if does not exist
        return false;
    }




}

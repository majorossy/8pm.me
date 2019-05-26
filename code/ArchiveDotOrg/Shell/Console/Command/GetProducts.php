<?php
//Comleted:
// setup ec2 with code and hosting
// setup rds
// Custom Product Attribute (25)
// Custom Category Attribute (4)
// Custpm Category Admin Tab (1)
// Custo SKU length, from 64 to 255
// Custom Shell Command to import products
// create new dropdown attribute values on the fly
// Import product image from 3rd party URL


//ToDo:
// add reviews
// add to artist category
// add to album and track categories
// add project to github
// proper security & permissions & user setup

 
namespace ArchiveDotOrg\Shell\Console\Command;

// https://www.thirdandgrove.com/create-magento-module-command-import-products
// https://stackoverflow.com/questions/34740555/how-to-get-model-and-product-collection-in-magento-2
// https://www.thirdandgrove.com/create-magento-module-command-import-products

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use ArchiveDotOrg\Shell\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\{ObjectManager, State};
use Magento\Catalog\Model\ProductRepository;


class GetProducts extends Command
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
	/**
	* Constructor
	*
	* @param State $state A Magento app State instance
	*
	* @return void
	*/
	public function __construct(
		State $state, 
		ProductRepositoryInterface $prepo,
		\Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Eav\Model\Entity\Attribute\Source\TableFactory $tableFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory,
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
        $this->optionFactory = $optionFactory;		
	}
	
	
	
   protected function configure()
   {
       $this->setName('ArchiveDotOrg:GetProducts');
       $this->setDescription('Demo command line');
       
       parent::configure();
   }
   

   protected function execute(InputInterface $input, OutputInterface $output)
   {   	
	try{
	    $output->writeln("<info>Starting...</info>");
	    $parent_category_id = 708; //Default -> Artist | Root category ID 
	   	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$categoryObj = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->load($parent_category_id);
		$subcategories = $categoryObj->getChildrenCategories();
		$categoryObj = null;
		unset($categoryObj);
		$__count_cats = 0;
		$__count_shows = 0;
		$__count_songs = 0;		
		foreach($subcategories as $subcategorie) {
			$output->writeln('<error>     on cat::'.$subcategorie->getId().' '.memory_get_peak_usage().'</error>');

//if($subcategorie->getId() ==675){ //Ween
//if($subcategorie->getId() ==156){ ///RRE
//if($subcategorie->getId() ==100){ //TDB
//if($subcategorie->getId() ==237){	//SCI	
//if($subcategorie->getId() ==398){	// TLG
//if($subcategorie->getId() ==436){	// OAR
//if($subcategorie->getId() ==697){	// TAUK
//if($subcategorie->getId() ==694){	// GSW
//if($subcategorie->getId() ==663){	// Marco
//if($subcategorie->getId() ==662){	// GreenSky
//if($subcategorie->getId() ==705){	// Twiddle
if($subcategorie->getId() ==668){	// Blues Travler
//if($subcategorie->getId() ==664){	// moe
//if($subcategorie->getId() ==693){	// Cabinet   305
//if($subcategorie->getId() ==705){	// Twiddle   429
//if($subcategorie->getId() ==2640){ // FGH 
//if($subcategorie->getId() ==2641){ // Todd Sheaffer
//if($subcategorie->getId() ==2641){ // Todd Sheaffer

			$__count_cats = $__count_cats + 1;
			$_subcategory_info = array();
			$subcategorie = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->load($subcategorie->getId());
			$_subcategory_info['name'] = $subcategorie->getData('name');
			$_the_rss_url = 'https://archive.org/advancedsearch.php?q=Collection%3A'.$subcategorie->getData('artist_collection_rss').'&fl[]=identifier&sort[]=&sort[]=&sort[]=&rows=999999&page=1&callback=callback&save=yes&output=rss';		
		    $output->writeln('<question>     On Artist #'.$__count_cats.' '.$_subcategory_info['name'].' Reading RSS --> '.$_the_rss_url.'</question>');			
			$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
			$collection_feed = file_get_contents($_the_rss_url,false,$context);		
			$collection_rss = simplexml_load_string($collection_feed);	
			$_sub_item_count_per_artist= 0;	
			$subcategorie = null;
			unset($subcategorie);
if($__count_cats >= 0){
			$_how_many_feeds = count($collection_rss->channel->item);	
			foreach($collection_rss->channel->item as $_item){			
			if($_sub_item_count_per_artist >=544){
				//sleep(5);
				//echo 'ssssa';exit;
				if ($__count_shows % 25 == 0){sleep(33);echo'SLEEP';}
				//if ($__count_shows % 100 == 0){sleep(200);echo'SLEEP';}				
				//if($_sub_item_count_per_artist >= 700){unset($collection_feed);break;}else{$_sub_item_count_per_artist = $_sub_item_count_per_artist +1;}
				$_sub_item_count_per_artist = $_sub_item_count_per_artist +1;
				$__count_shows = $__count_shows + 1;
				$_show_vars = array();	
				$_show_vars['title'] = $_item->title;
				$_show_vars['description'] = $_item->description;
				$_show_vars['pubDate'] = $_item->pubDate;
				$_show_vars['guid'] = $_item->guid;
				$_show_vars['guid_clone'] = $_item->guid;	
				$_show_vars['category'] = $_item->category;				
				$_show_vars['feed'] = file_get_contents(str_replace('/details/','/metadata/',$_show_vars['guid']),false,$context);
				$_show_vars['feed_url'] = str_replace('/details/','/metadata/',$_show_vars['guid']);
				$_show_vars['json'] = json_decode($_show_vars['feed']);
				$_show_vars['dir'] = $_show_vars['json']->dir;
				$_show_vars['date'] = $_show_vars['json']->metadata->date;
				$_show_vars['creator'] = $_show_vars['json']->metadata->creator;
				$_show_vars['uniq'] = $_show_vars['json']->uniq;				
				
				if(isset($_show_vars['json']->d1) && trim($_show_vars['json']->d1) != ''){$_show_vars['server_one'] = $_show_vars['json']->d1;}else{$_show_vars['server_one'] = 'not stored';}
				if(isset($_show_vars['json']->d2) && trim($_show_vars['json']->d2) != ''){$_show_vars['server_two'] = $_show_vars['json']->d2;}else{$_show_vars['server_two'] = 'not stored';}
				if (is_array($_show_vars['json']->metadata->year)){
					$_show_vars['year'] = $_show_vars['json']->metadata->year[0];
				}else{
					$_show_vars['year'] = $_show_vars['json']->metadata->year;
				}
				if(isset($_show_vars['json']->metadata->venue) && trim($_show_vars['json']->metadata->venue) != ''){$_show_vars['venue'] = $_show_vars['json']->metadata->venue;}else{$_show_vars['venue'] = 'not stored';}
				if(isset($_show_vars['json']->metadata->notes) && trim($_show_vars['json']->metadata->notes) != ''){$_show_vars['notes'] = $_show_vars['json']->metadata->notes;}else{$_show_vars['notes'] = 'not stored';}
				if(isset($_show_vars['json']->metadata->taper) && trim($_show_vars['json']->metadata->taper) != ''){$_show_vars['taper'] = $_show_vars['json']->metadata->taper;}else{$_show_vars['taper'] = 'not stored';}
				$_show_vars['collection'] = $_show_vars['json']->metadata->collection;				
				if(isset($_show_vars['json']->metadata->transferer) && trim($_show_vars['json']->metadata->transferer) != ''){$_show_vars['transferer'] = $_show_vars['json']->metadata->transferer;}else{$_show_vars['transferer'] = 'not stored';}
				//$_show_vars['location']  = $_show_vars['json']->metadata->location;
				$_show_vars['title'] = $_show_vars['json']->metadata->title;
				if(isset($_show_vars['json']->metadata->lineage) && trim($_show_vars['json']->metadata->lineage) != ''){$_show_vars['lineage'] = $_show_vars['json']->metadata->lineage;}else{$_show_vars['lineage'] = 'not stored';}			
				$_show_vars['identifier'] = $_show_vars['json']->metadata->identifier;				
				$output->writeln('<info>     On Artist #'.$__count_cats.' of '.$_how_many_feeds.' found in feed  '.$_subcategory_info['name'].' || On Show #'.$__count_shows.'    --> '.$_show_vars['title'].' | '. $_show_vars['feed_url'].' '.memory_get_peak_usage().'</info>');
				$output->writeln('<info>               venue->'.$_show_vars['venue'].' || taper->'.$_show_vars['taper'].' || transferer->'.$_show_vars['transferer'].' || year->'.$_show_vars['year'].'</info>');
				foreach($_show_vars['json']->files as $_song){					
					$_song_var = array();											
					if($this->endsWith($_song->name, '.flac') && isset($_song->title)){
						$__count_songs = $__count_songs + 1;
						//$_song_var['sku'] = rtrim(mb_strimwidth(substr($_song->name, 0, -5), 0, 64, '', 'UTF-8'));
						$_song_var['title'] = $_song->title;
						if(isset($_song->track)){$_song_var['show_track'] = $_song->track.'_';}else{$_song_var['show_track']='';};
						$_song_var['length'] = $_song->length;
						$_song_var['sha1'] = $_song->sha1;
						//$_song_var['sku'] = strtolower(str_replace(' ','-',$_show_vars['identifier'].'_'.$_show_vars['creator'].'_'.$_show_vars['date'].'_'.$_song_var['show_track'].$_song_var['title']));
						$_song_var['sku'] = $_song_var['sha1'];
							
						//$_song_var['prod_url'] = strtolower(str_replace(' ','-',$_song->name.$_song_var['show_track'].$_song_var['title'].$_show_vars['identifier']));
						$_song_var['prod_url'] = $_song_var['sha1'];
						
							
						$_song_var['name'] = $_subcategory_info['name'].' '.$_song_var['title'].' '.$_show_vars['year'].' '.$_show_vars['venue'];													
						$productMod = $objectManager->get('Magento\Catalog\Model\Product');
						$_update_flag = 0;												
						if($productMod->getIdBySku($_song_var['sku'])){
						    $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productMod->getIdBySku($_song_var['sku']));
							$_update_flag = 1;
						} else {							
						    $product = $objectManager->create('Magento\Catalog\Model\Product');
							//$output->writeln('<info>               On Song #'.$__count_songs.' Adding --> sku::'.$_song_var['sku'].' || title::'.$_song_var['title'].' || name::'.$_song_var['name'].' || source::'.$_song->source.'</info>');
							//$output->writeln('<info>               On Song #'.$__count_songs.' Adding --> sku::'.$_song_var['sku'].' '.memory_get_peak_usage().'</info>');
						}
						$productMod = null;
						unset($productMod);												
						//$product->setUniq($_show_vars['uniq']);
						$product->setDescription($_show_vars['description']);
						$product->setName($_song_var['name']);
						$product->setSku($_song_var['sku']);
						//$datetime = new \DateTime($_show_pubDate, new \DateTimeZone('GMT'));
						//$datetime->setTimezone(new \DateTimeZone('America/New_York'));
						//$_show_pubDate = $datetime->format('Y-m-d H:i:s (e)');
						$product->setPubDate($_show_vars['pubDate']);
						$product->setShowDate($_show_vars['date']);						
						$product->setGuid($_show_vars['guid']);
						$product->setServerOne($_show_vars['server_one']);
						$product->setServerTwo($_show_vars['server_two']);
						$atr_year = $this->createOrGetId('year', $_show_vars['year']);
						$product->setYear($atr_year);
						$atr_venue = $this->createOrGetId('venue', $_show_vars['venue']);
						$product->setVenue($atr_venue);											
						$atr_taper = $this->createOrGetId('taper', $_show_vars['taper']);
						$product->setTaper($atr_taper);						
						$atr_transfer = $this->createOrGetId('transfer', $_show_vars['transferer']);
						$product->setTransfer($atr_transfer);
						$product->setTitle($_song_var['title']);																		
						$atr_archive_collection = $this->createOrGetId('archive_collection', $_subcategory_info['name']);												
						$product->setArchiveCollection($atr_archive_collection);
						$product->setShowName($_show_vars['title']);
						$product->setLength($_song_var['length']);
						$product->setCollection($_show_vars['collection']);	
						$product->setNotes($_show_vars['notes']);						
						if(isset($_show_vars['json']->metadata->lineage)){$product->setLineage($_show_vars['lineage']);}						
						$product->setUrlKey(strtolower(preg_replace('#[^0-9a-z]+#i', '-', rtrim(mb_strimwidth($_song_var['sku'], 0, 64, '', 'UTF-8')))));
						$product->setIdentifier($_show_vars['identifier']);
						$product->setDir($_show_vars['dir']);
						$product->setSongUrl($_show_vars['server_one'].'/'.$_show_vars['dir'].'/'.rtrim(mb_strimwidth(substr($_song->name, 0, -5), 0, 64, '', 'UTF-8')).'.flac');							
						//												
						//$output->writeln('<info>               Saving...</info>');						
//						if(!$product->getData('image')){
//							$_imagePath = 'https://'.$_show_vars['server_one'].$_show_vars['dir'].'/'.$_song_var['sku'].'.png'; // path of the image
//							$_imagePath2 = 'https://'.$_show_vars['server_one'].$_show_vars['dir'].'/'.$_song_var['sku'].'_spectrogram.png'; // path of the image						
//							if((preg_match('/\s/',$_imagePath2) == 0)&&(preg_match('/\'/',$_imagePath2) == 0)){ 
//								$this->importimageservice->execute($product, $_imagePath2, $__visible = true, $__imageType = ['image', 'small_image', 'thumbnail']);
								//$this->importimageservice->execute($product, $_imagePath, $__visible = true);
//								$output->writeln('<info>               Adding Image</info>');
//							}else{
//								$output->writeln('<error>              Image Skipped has space or single quote in _spectrogram.png name</error>');
//							}						
//						}	
						if($_update_flag == 1){
							$output->writeln('<comment>               On Song #'.$product->getId().' '.$__count_songs.' Updating --> sku::'.$_song_var['sku'].'  ||  UrlKey:: '.$product->getUrlKey().'  title::'.$_song_var['title'].' || name::'.$_song_var['name'].' || source::'.$_song->source.'</comment>');
						} else {							
							$output->writeln('<info>               On Song #'.$product->getId().' '.$__count_songs.' Adding --> sku::'.$_song_var['sku'].'  ||  UrlKey:: '.$product->getUrlKey().'  url::'.$_song_var['prod_url'].'</info>');									
						}
						$product->setWebsiteIds([1]);
						$product->setStoreId(0);
						if($_update_flag == 0){
							$product->setPrice(0);
							$product->setTypeId('virtual');
							$product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
							$product->setAttributeSetId(9);
							$product->setVisibility(4);
							$product->setStockData(array(
					            'use_config_manage_stock' => 0, //'Use config settings' checkbox
					            'manage_stock' => 0, //manage stock
					            'is_in_stock' => 1
					            )
					        );
							$product->save();
							//echo $product->getUrlKey();
							
						}else{
							// echo '|||'.$product->getUrlKey().'|||';
							//$product->save();
						}
					
						//$output->writeln('<info>               .........OK</info>');
						$product = null;
						unset($product);		
																				
					}
					$_song_var=null;
					unset($_song_var);
				}
			}else{
					$_sub_item_count_per_artist = $_sub_item_count_per_artist +1;
					$__count_shows = $__count_shows + 1;
				}
			$_show_vars=null;
			unset($_show_vars);
			clearstatcache();
		}

	$collection_rss=null;
	$collection_feed=null;
	$_the_rss_url=null;
	$_subcategory_info=null;	
	unset($collection_rss);
	unset($collection_feed);
	unset($_the_rss_url);
	unset($_subcategory_info);
}
}else{
	echo $subcategorie->getId().' ';
}
		}
	   $output->writeln("<info>Done.</info>");
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
	                __('Label for %1 must not be empty.', $attributeCode)
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

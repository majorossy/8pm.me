<?php
namespace ArchiveDotOrg\CategoryWork\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Psr\Log\LoggerInterface;
use ArchiveDotOrg\CategoryWork\Helper\CategoryData;

class UpgradeData implements UpgradeDataInterface
{
    /**
    * @var CategoryFactory
    */
    protected $categoryFactory;
    /**
    * @var CategoryRepository
    */
    protected $categoryRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CategoryData
     */
    private $helper;
    /**
    * UpgradeData constructor.
    *
    * @param CategoryFactory    $categoryFactory
    * @param CategoryRepository $categoryRepository
    * @param LoggerInterface $logger
    * @param CategoryData $helper
    */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository,
        LoggerInterface $logger,
        CategoryData $helper
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        $this->helper = $helper;
    }
    /**
    * {@inheritdoc}
    */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        if(version_compare($context->getVersion(), '0.2.9', '<')) {
            $this->createCategory();
        };
        $installer->endSetup();
    }
    /**
    * Create category
    */
    protected function createCategory()
    {
        $_magento_root_category_id = 1;
        try{
            $_all_tpo_make = $this->helper->getAllTheCatsToMake_STS();
            $_song_track_number = 0;
            $_a_n_holder = '';
            foreach($_all_tpo_make as $_a_cat){
                $category = $this->categoryFactory->create();
                $category->setName($_a_cat['name']);
                $_path = $_magento_root_category_id;
                $_parent_id = null;
                $_getpath = explode('/',$_a_cat['path']);
                $_depth_count = 0;
                $_loop_count = count($_getpath);
                foreach($_getpath as $__path){
                    $_depth_count+=1;
                    if($_depth_count == 3){$_a_n_holder = $__path;}
                    if($_depth_count == 5){break;}
                    $_category_checker = $this->categoryFactory->create();
                    $_category_checker = $_category_checker->loadByAttribute('name', $__path);
                    if ($_category_checker) {
                        $_parent_id  = $_category_checker->getId();
                        $_path .= '/'.$_category_checker->getId();
                    }

                }
                switch($_depth_count){
                    case 2:
                        // All Artists Category
                        break;
                    case 3:
                        // An Artist
                        $_song_track_number = 0;
                        $category->setIsArtist(1);
                        $category->setArtist($__path);
                        break;
                    case 4:
                        // An Album
                        $_song_track_number = 0;
                        $category->setIsAlbum(1);
                        $category->setArtist($_a_n_holder);
                        break;
                    case 5:
                        // A Track | Song
                        $_song_track_number += 1;
                        $category->setIsSong(1);
                        $category->setSongTrackNumber($_song_track_number);
                        $category->setArtist($_a_n_holder);
                        break;
                    default:
                        break;
                }
                $_url = $_a_cat['name'];
                $_url = trim(preg_replace('/ +/', '', preg_replace('/[^A-Za-z0-9 ]/', '', urldecode(html_entity_decode(strip_tags($_url))))));
                $_url_checker = $this->categoryFactory->create();
                $_url_checker = $_url_checker->loadByAttribute('url_key',$_url);
                if($_url_checker){
                    $_url = $_url.rand(10000,99999);
                }
                $category->setArtistCollectionRss($_a_cat['artist_collection_rss']);
                $category->setUrlKey($_url);
                $category->setPath($_path);
                $category->setParentId($_parent_id);
                $category->setIsActive(1);
                $category->setIsAnchor($_a_cat['is_anchor']);
                $category->setIncludeInMenu(1);
                //echo '{'.$category->getName().' '.$category->getSongTrackNumber().'}';
                $category->save();
            }
        } catch (Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }
}
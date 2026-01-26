<?php
namespace ArchiveDotOrg\CategoryWork\Model\Product;

class Image extends \Magento\Catalog\Model\Product\Image {


    // change quality of PNG comppression on Category Images
    protected function _construct() {
        $this->_quality = 100;
        parent::_construct();
    }
}

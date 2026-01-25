<?php
 
namespace ArchiveDotOrg\CategoryWork\Block\Adminhtml\Custom;
 
class Tab extends \Magento\Backend\Block\Template
{
    protected $_template = 'catalog/category/tab.phtml';
     
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
}
?>
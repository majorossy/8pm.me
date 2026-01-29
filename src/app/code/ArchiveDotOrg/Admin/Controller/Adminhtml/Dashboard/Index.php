<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Archive.org Import Dashboard
 * 
 * Displays overview stats, active imports, and quick access to grids.
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Admin::dashboard';
    
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }
    
    /**
     * Execute dashboard page
     */
    public function execute(): ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArchiveDotOrg_Admin::dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('Archive.org Import Dashboard'));
        
        return $resultPage;
    }
}

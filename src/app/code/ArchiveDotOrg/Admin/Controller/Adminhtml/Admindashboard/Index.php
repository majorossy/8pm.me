<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Controller\Adminhtml\Admindashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Admin::dashboard';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArchiveDotOrg_Admin::dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('Archive.org Stats Dashboard'));
        return $resultPage;
    }
}

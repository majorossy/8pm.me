<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Controller\Adminhtml\Unmatched;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Admin::unmatched';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArchiveDotOrg_Admin::unmatched');
        $resultPage->getConfig()->getTitle()->prepend(__('Unmatched Tracks'));

        return $resultPage;
    }
}

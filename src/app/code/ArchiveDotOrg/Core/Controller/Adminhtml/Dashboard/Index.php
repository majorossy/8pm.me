<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Dashboard Index Controller
 *
 * Main dashboard page for Archive.org module control center
 */
class Index extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::dashboard';

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
     * Execute action
     *
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArchiveDotOrg_Core::dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('Archive.org Control Center'));

        return $resultPage;
    }
}

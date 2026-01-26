<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Import Jobs Grid Controller (placeholder for future implementation)
 */
class Index extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::import';

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
        $resultPage->setActiveMenu('ArchiveDotOrg_Core::import_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Archive.org Import Jobs'));

        return $resultPage;
    }
}

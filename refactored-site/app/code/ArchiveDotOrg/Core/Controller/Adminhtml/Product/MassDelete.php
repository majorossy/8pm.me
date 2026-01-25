<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Product;

use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass Delete Products Controller
 */
class MassDelete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::products_delete_admin';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private ProductRepositoryInterface $productRepository;
    private Logger $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            // Filter to only Archive.org products
            $collection->addAttributeToFilter('identifier', ['notnull' => true]);
            $collection->addAttributeToFilter('identifier', ['neq' => '']);

            $deletedCount = 0;
            $errorCount = 0;
            $skippedCount = 0;

            foreach ($collection as $product) {
                try {
                    // Double-check it's an Archive.org product
                    if (empty($product->getData('identifier'))) {
                        $skippedCount++;
                        continue;
                    }

                    $this->productRepository->delete($product);
                    $deletedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->error('Mass delete product failed', [
                        'product_id' => $product->getId(),
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('Successfully deleted %1 product(s).', $deletedCount)
                );
            }

            if ($skippedCount > 0) {
                $this->messageManager->addWarningMessage(
                    __('Skipped %1 product(s) that are not Archive.org imports.', $skippedCount)
                );
            }

            if ($errorCount > 0) {
                $this->messageManager->addErrorMessage(
                    __('Failed to delete %1 product(s). Check logs for details.', $errorCount)
                );
            }

            $this->logger->info('Admin mass deleted products', [
                'deleted' => $deletedCount,
                'errors' => $errorCount,
                'skipped' => $skippedCount,
                'admin_user' => $this->_auth->getUser()->getUserName()
            ]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred during mass delete: %1', $e->getMessage())
            );
        }

        return $resultRedirect;
    }
}

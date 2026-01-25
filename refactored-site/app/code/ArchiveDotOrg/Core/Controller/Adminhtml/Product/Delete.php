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
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Delete Single Product Controller
 */
class Delete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::products_delete_admin';

    private ProductRepositoryInterface $productRepository;
    private Logger $logger;

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Logger $logger
    ) {
        parent::__construct($context);
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

        $productId = (int) $this->getRequest()->getParam('id');

        if (!$productId) {
            $this->messageManager->addErrorMessage(__('Product ID is required.'));
            return $resultRedirect;
        }

        try {
            $product = $this->productRepository->getById($productId);
            $sku = $product->getSku();
            $name = $product->getName();

            // Verify this is an Archive.org product
            $identifier = $product->getData('identifier');
            if (empty($identifier)) {
                $this->messageManager->addErrorMessage(
                    __('Product "%1" is not an Archive.org imported product.', $name)
                );
                return $resultRedirect;
            }

            $this->productRepository->delete($product);

            $this->messageManager->addSuccessMessage(
                __('Product "%1" (SKU: %2) has been deleted.', $name, $sku)
            );

            $this->logger->info('Admin deleted product', [
                'product_id' => $productId,
                'sku' => $sku,
                'admin_user' => $this->_auth->getUser()->getUserName()
            ]);

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Product with ID %1 does not exist.', $productId));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting the product: %1', $e->getMessage())
            );
            $this->logger->error('Admin product deletion failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }

        return $resultRedirect;
    }
}

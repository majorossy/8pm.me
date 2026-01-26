<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Product;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Api\TrackImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Re-import Product Controller
 *
 * Refreshes product data from Archive.org
 */
class Reimport extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::products_reimport';

    private ProductRepositoryInterface $productRepository;
    private ArchiveApiClientInterface $apiClient;
    private TrackImporterInterface $trackImporter;
    private Logger $logger;

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param ArchiveApiClientInterface $apiClient
     * @param TrackImporterInterface $trackImporter
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        ArchiveApiClientInterface $apiClient,
        TrackImporterInterface $trackImporter,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->apiClient = $apiClient;
        $this->trackImporter = $trackImporter;
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

            // Get Archive.org identifier
            $identifier = $product->getData('identifier');
            if (empty($identifier)) {
                $this->messageManager->addErrorMessage(
                    __('Product "%1" does not have an Archive.org identifier.', $product->getName())
                );
                return $resultRedirect;
            }

            // Get artist/collection from product
            $artistName = $this->getArtistNameFromProduct($product);

            // Fetch fresh metadata from Archive.org
            $show = $this->apiClient->fetchShowMetadata($identifier);

            // Find the matching track by SHA1/SKU
            $sku = $product->getSku();
            $matchingTrack = null;

            foreach ($show->getTracks() as $track) {
                if ($track->generateSku() === $sku) {
                    $matchingTrack = $track;
                    break;
                }
            }

            if ($matchingTrack === null) {
                $this->messageManager->addWarningMessage(
                    __('Could not find matching track in Archive.org metadata. The show may have been updated.')
                );
                return $resultRedirect;
            }

            // Re-import the track (will update existing product)
            $this->trackImporter->importTrack($matchingTrack, $show, $artistName);

            $this->messageManager->addSuccessMessage(
                __('Product "%1" has been re-imported from Archive.org.', $product->getName())
            );

            $this->logger->info('Admin re-imported product', [
                'product_id' => $productId,
                'sku' => $sku,
                'identifier' => $identifier,
                'admin_user' => $this->_auth->getUser()->getUserName()
            ]);

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Product with ID %1 does not exist.', $productId));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while re-importing: %1', $e->getMessage())
            );
            $this->logger->error('Admin product re-import failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }

        return $resultRedirect;
    }

    /**
     * Get artist name from product's archive_collection attribute
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return string
     */
    private function getArtistNameFromProduct($product): string
    {
        $attribute = $product->getResource()->getAttribute('archive_collection');

        if ($attribute && $attribute->usesSource()) {
            $optionId = $product->getData('archive_collection');
            if ($optionId) {
                $label = $attribute->getSource()->getOptionText($optionId);
                if ($label) {
                    return (string) $label;
                }
            }
        }

        return 'Unknown Artist';
    }
}

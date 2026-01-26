<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\ActivityLogFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard CleanupProducts Controller
 *
 * AJAX endpoint to cleanup/delete Archive.org products
 */
class CleanupProducts extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::products_delete_admin';

    private JsonFactory $resultJsonFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private ProductRepositoryInterface $productRepository;
    private Logger $logger;
    private ActivityLogFactory $activityLogFactory;
    private AuthSession $authSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param ActivityLogFactory $activityLogFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        Logger $logger,
        ActivityLogFactory $activityLogFactory,
        AuthSession $authSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->activityLogFactory = $activityLogFactory;
        $this->authSession = $authSession;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $request = $this->getRequest();
            $collectionFilter = $request->getParam('collection');
            $olderThanDays = $request->getParam('older_than');
            $dryRun = (bool) $request->getParam('dry_run', false);
            $batchSize = (int) $request->getParam('batch_size', 100);

            // Validate
            if (empty($collectionFilter) && empty($olderThanDays)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Either collection or older_than parameter is required.'
                ]);
            }

            if (!empty($olderThanDays) && (!ctype_digit((string) $olderThanDays) || (int) $olderThanDays <= 0)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'older_than must be a positive integer (days).'
                ]);
            }

            if ($batchSize <= 0 || $batchSize > 1000) {
                $batchSize = 100;
            }

            // Build collection
            $collection = $this->buildProductCollection(
                $collectionFilter,
                $olderThanDays ? (int) $olderThanDays : null
            );

            $totalProducts = $collection->getSize();

            if ($totalProducts === 0) {
                return $result->setData([
                    'success' => true,
                    'message' => 'No products found matching the criteria.',
                    'stats' => ['found' => 0, 'deleted' => 0, 'errors' => 0]
                ]);
            }

            if ($dryRun) {
                return $result->setData([
                    'success' => true,
                    'message' => sprintf('%d products would be deleted.', $totalProducts),
                    'stats' => ['found' => $totalProducts, 'deleted' => 0, 'errors' => 0],
                    'dry_run' => true
                ]);
            }

            // Execute deletion
            $deleted = 0;
            $errors = 0;

            $collection->setPageSize($batchSize);

            foreach ($collection as $product) {
                try {
                    $this->productRepository->delete($product);
                    $deleted++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->logImportError('Product deletion failed', [
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log activity
            $this->logActivity('cleanup_products', sprintf(
                'Cleanup completed: %d deleted, %d errors. Filters: %s',
                $deleted,
                $errors,
                $this->formatFilters($collectionFilter, $olderThanDays)
            ), $errors === 0 ? 'success' : 'warning');

            return $result->setData([
                'success' => true,
                'message' => sprintf('Deleted %d products. %d errors.', $deleted, $errors),
                'stats' => ['found' => $totalProducts, 'deleted' => $deleted, 'errors' => $errors]
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build the product collection with filters
     *
     * @param string|null $collectionFilter
     * @param int|null $olderThanDays
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function buildProductCollection(?string $collectionFilter, ?int $olderThanDays)
    {
        $collection = $this->productCollectionFactory->create();

        // Only include Archive.org products
        $collection->addAttributeToFilter('identifier', ['notnull' => true]);
        $collection->addAttributeToFilter('identifier', ['neq' => '']);

        // Filter by collection/artist
        if (!empty($collectionFilter)) {
            $collection->addAttributeToSelect('archive_collection');

            $attribute = $collection->getResource()->getAttribute('archive_collection');
            if ($attribute && $attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions();
                $optionId = null;

                foreach ($options as $option) {
                    if (strcasecmp($option['label'], $collectionFilter) === 0) {
                        $optionId = $option['value'];
                        break;
                    }
                }

                if ($optionId !== null) {
                    $collection->addAttributeToFilter('archive_collection', $optionId);
                }
            }
        }

        // Filter by age
        if ($olderThanDays !== null) {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
            $collection->addAttributeToFilter('created_at', ['lt' => $cutoffDate]);
        }

        return $collection;
    }

    /**
     * Format filters for logging
     *
     * @param string|null $collection
     * @param string|null $olderThan
     * @return string
     */
    private function formatFilters(?string $collection, ?string $olderThan): string
    {
        $parts = [];
        if ($collection) {
            $parts[] = 'collection=' . $collection;
        }
        if ($olderThan) {
            $parts[] = 'older_than=' . $olderThan . ' days';
        }
        return implode(', ', $parts);
    }

    /**
     * Log activity to database
     *
     * @param string $actionType
     * @param string $details
     * @param string $status
     * @return void
     */
    private function logActivity(string $actionType, string $details, string $status = 'success'): void
    {
        try {
            $user = $this->authSession->getUser();
            $activityLog = $this->activityLogFactory->create();
            $activityLog->setData([
                'action_type' => $actionType,
                'details' => $details,
                'admin_user_id' => $user ? $user->getId() : null,
                'admin_username' => $user ? $user->getUserName() : 'system',
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $activityLog->save();
        } catch (\Exception $e) {
            // Don't fail if logging fails
        }
    }
}

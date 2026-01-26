<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\ActivityLogFactory;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard SyncAlbums Controller
 *
 * AJAX endpoint to trigger album sync
 */
class SyncAlbums extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::dashboard';

    private JsonFactory $resultJsonFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private AttributeOptionManagerInterface $attributeOptionManager;
    private Config $config;
    private Logger $logger;
    private ActivityLogFactory $activityLogFactory;
    private AuthSession $authSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param AttributeOptionManagerInterface $attributeOptionManager
     * @param Config $config
     * @param Logger $logger
     * @param ActivityLogFactory $activityLogFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        AttributeOptionManagerInterface $attributeOptionManager,
        Config $config,
        Logger $logger,
        ActivityLogFactory $activityLogFactory,
        AuthSession $authSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->attributeOptionManager = $attributeOptionManager;
        $this->config = $config;
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
            $threshold = (float) $this->getRequest()->getParam('threshold', 75);
            $dryRun = (bool) $this->getRequest()->getParam('dry_run', false);

            // Get song categories
            $songCategories = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('is_song', 1);

            $totalCategories = $songCategories->getSize();

            if ($totalCategories === 0) {
                return $result->setData([
                    'success' => true,
                    'message' => 'No song categories found.',
                    'stats' => ['categories' => 0, 'matched' => 0, 'assigned' => 0]
                ]);
            }

            $stats = [
                'categories' => 0,
                'matched' => 0,
                'assigned' => 0,
                'errors' => 0
            ];

            foreach ($songCategories as $category) {
                try {
                    $matched = $this->processSongCategory($category, $threshold, $dryRun);
                    $stats['categories']++;
                    $stats['matched'] += $matched['matched'];
                    $stats['assigned'] += $matched['assigned'];
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->logger->logImportError('Album sync error', [
                        'category_id' => $category->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log activity
            $this->logActivity('sync_albums', sprintf(
                'Album sync completed: %d categories, %d products matched, %d assigned%s',
                $stats['categories'],
                $stats['matched'],
                $stats['assigned'],
                $dryRun ? ' [DRY RUN]' : ''
            ), $stats['errors'] === 0 ? 'success' : 'warning');

            return $result->setData([
                'success' => true,
                'message' => sprintf(
                    'Synced %d categories. Matched %d products, assigned %d.',
                    $stats['categories'],
                    $stats['matched'],
                    $stats['assigned']
                ),
                'stats' => $stats,
                'dry_run' => $dryRun
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process a single song category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param float $threshold
     * @param bool $dryRun
     * @return array
     */
    private function processSongCategory($category, float $threshold, bool $dryRun): array
    {
        $result = ['matched' => 0, 'assigned' => 0];

        $artistName = $category->getData('artist');
        if (empty($artistName)) {
            return $result;
        }

        $collectionOptionId = $this->attributeOptionManager->getOptionId('archive_collection', $artistName);
        if ($collectionOptionId === null) {
            return $result;
        }

        $products = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['title', 'sku', 'name'])
            ->addAttributeToFilter('archive_collection', $collectionOptionId);

        $categoryName = strtolower(trim($category->getName()));

        foreach ($products as $product) {
            $productTitle = strtolower(trim($product->getData('title') ?? ''));
            if (empty($productTitle)) {
                continue;
            }

            if ($this->isMatch($productTitle, $categoryName, $threshold)) {
                $result['matched']++;

                if (!$dryRun) {
                    try {
                        $categoryIds = array_merge(
                            [$category->getId()],
                            $product->getCategoryIds() ?? []
                        );

                        $this->categoryLinkManagement->assignProductToCategories(
                            $product->getSku(),
                            array_unique($categoryIds)
                        );

                        $result['assigned']++;
                    } catch (\Exception $e) {
                        // Skip assignment errors
                    }
                } else {
                    $result['assigned']++;
                }
            }
        }

        return $result;
    }

    /**
     * Check if product title matches category name
     *
     * @param string $productTitle
     * @param string $categoryName
     * @param float $threshold
     * @return bool
     */
    private function isMatch(string $productTitle, string $categoryName, float $threshold): bool
    {
        $productMetaphone = metaphone($productTitle);
        $categoryMetaphone = metaphone($categoryName);

        if ($productMetaphone === $categoryMetaphone) {
            return true;
        }

        if (strlen($categoryMetaphone) >= 4 && strpos($productMetaphone, $categoryMetaphone) !== false) {
            return true;
        }

        $similarity = 0;
        similar_text($productTitle, $categoryName, $similarity);

        return $similarity >= $threshold;
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

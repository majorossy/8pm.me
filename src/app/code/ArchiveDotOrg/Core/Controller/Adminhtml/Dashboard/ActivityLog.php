<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Model\ResourceModel\ActivityLog\CollectionFactory as ActivityLogCollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard ActivityLog Controller
 *
 * AJAX endpoint to get recent activity log entries
 */
class ActivityLog extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::dashboard';

    private JsonFactory $resultJsonFactory;
    private ActivityLogCollectionFactory $activityLogCollectionFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ActivityLogCollectionFactory $activityLogCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ActivityLogCollectionFactory $activityLogCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->activityLogCollectionFactory = $activityLogCollectionFactory;
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
            $limit = (int) $this->getRequest()->getParam('limit', 50);
            $actionType = $this->getRequest()->getParam('action_type');

            if ($limit <= 0 || $limit > 100) {
                $limit = 50;
            }

            $collection = $this->activityLogCollectionFactory->create();
            $collection->setOrder('created_at', 'DESC');
            $collection->setPageSize($limit);

            if (!empty($actionType)) {
                $collection->addFieldToFilter('action_type', $actionType);
            }

            $activities = [];
            foreach ($collection as $activity) {
                $activities[] = [
                    'id' => $activity->getId(),
                    'action_type' => $activity->getData('action_type'),
                    'details' => $activity->getData('details'),
                    'job_id' => $activity->getData('job_id'),
                    'admin_username' => $activity->getData('admin_username'),
                    'status' => $activity->getData('status'),
                    'created_at' => $activity->getData('created_at')
                ];
            }

            return $result->setData([
                'success' => true,
                'activities' => $activities,
                'total' => $collection->getSize()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

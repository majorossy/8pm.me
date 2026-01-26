<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Api\ImportPublisherInterface;
use ArchiveDotOrg\Core\Model\ActivityLogFactory;
use ArchiveDotOrg\Core\Model\Queue\JobStatusManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard CancelJob Controller
 *
 * AJAX endpoint to cancel a running import job
 */
class CancelJob extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::import_cancel';

    private JsonFactory $resultJsonFactory;
    private ImportPublisherInterface $importPublisher;
    private JobStatusManager $jobStatusManager;
    private ActivityLogFactory $activityLogFactory;
    private AuthSession $authSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ImportPublisherInterface $importPublisher
     * @param JobStatusManager $jobStatusManager
     * @param ActivityLogFactory $activityLogFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ImportPublisherInterface $importPublisher,
        JobStatusManager $jobStatusManager,
        ActivityLogFactory $activityLogFactory,
        AuthSession $authSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->importPublisher = $importPublisher;
        $this->jobStatusManager = $jobStatusManager;
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
            $jobId = $this->getRequest()->getParam('job_id');

            if (empty($jobId)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Job ID is required.'
                ]);
            }

            // Get job info for logging before cancellation
            $job = $this->jobStatusManager->getJob($jobId);

            if ($job === null) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Job not found.'
                ]);
            }

            $artistName = $job->getArtistName();
            $currentStatus = $job->getStatus();

            // Attempt cancellation
            $cancelled = $this->importPublisher->cancel($jobId);

            if (!$cancelled) {
                return $result->setData([
                    'success' => false,
                    'error' => sprintf(
                        'Cannot cancel job. Current status: %s. Only queued or running jobs can be cancelled.',
                        $currentStatus
                    )
                ]);
            }

            // Log activity
            $this->logActivity('import_cancelled', sprintf(
                'Import job cancelled: %s (Job ID: %s)',
                $artistName,
                $jobId
            ), $jobId);

            return $result->setData([
                'success' => true,
                'message' => sprintf('Job %s has been cancelled.', $jobId)
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log activity to database
     *
     * @param string $actionType
     * @param string $details
     * @param string|null $jobId
     * @return void
     */
    private function logActivity(string $actionType, string $details, ?string $jobId = null): void
    {
        try {
            $user = $this->authSession->getUser();
            $activityLog = $this->activityLogFactory->create();
            $activityLog->setData([
                'action_type' => $actionType,
                'details' => $details,
                'job_id' => $jobId,
                'admin_user_id' => $user ? $user->getId() : null,
                'admin_username' => $user ? $user->getUserName() : 'system',
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $activityLog->save();
        } catch (\Exception $e) {
            // Don't fail if logging fails
        }
    }
}

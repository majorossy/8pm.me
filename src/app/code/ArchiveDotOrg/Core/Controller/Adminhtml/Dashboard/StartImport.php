<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Api\ImportPublisherInterface;
use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Model\ActivityLogFactory;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard StartImport Controller
 *
 * AJAX endpoint to start an import job via message queue
 */
class StartImport extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::import_start';

    private JsonFactory $resultJsonFactory;
    private ImportPublisherInterface $importPublisher;
    private LockServiceInterface $lockService;
    private Config $config;
    private ActivityLogFactory $activityLogFactory;
    private AuthSession $authSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ImportPublisherInterface $importPublisher
     * @param LockServiceInterface $lockService
     * @param Config $config
     * @param ActivityLogFactory $activityLogFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ImportPublisherInterface $importPublisher,
        LockServiceInterface $lockService,
        Config $config,
        ActivityLogFactory $activityLogFactory,
        AuthSession $authSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->importPublisher = $importPublisher;
        $this->lockService = $lockService;
        $this->config = $config;
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

            // Get and validate parameters
            $artistName = trim((string) $request->getParam('artist_name', ''));
            $collectionId = trim((string) $request->getParam('collection_id', ''));
            $limit = (int) $request->getParam('limit', 100);
            $offset = (int) $request->getParam('offset', 0);
            $dryRun = (bool) $request->getParam('dry_run', false);

            // Validation
            if (empty($artistName)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Artist name is required.'
                ]);
            }

            if (empty($collectionId)) {
                // Try to get from artist mappings
                $collectionId = $this->config->getCollectionIdForArtist($artistName);
                if (empty($collectionId)) {
                    return $result->setData([
                        'success' => false,
                        'error' => 'Collection ID is required.'
                    ]);
                }
            }

            // Validate collection ID format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $collectionId)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Invalid collection ID format. Only alphanumeric, underscores, and hyphens allowed.'
                ]);
            }

            if ($limit <= 0) {
                $limit = 100;
            }

            if ($offset < 0) {
                $offset = 0;
            }

            // Check if import is already running for this artist
            if ($this->lockService->isLocked('import', $collectionId)) {
                $lockInfo = $this->lockService->getLockInfo('import', $collectionId);
                return $result->setData([
                    'success' => false,
                    'error' => sprintf(
                        'Import already in progress for %s (started by PID %d at %s)',
                        $artistName,
                        $lockInfo['pid'] ?? 0,
                        $lockInfo['acquired_at'] ?? 'unknown time'
                    )
                ]);
            }

            // Publish to queue
            $job = $this->importPublisher->publish(
                $artistName,
                $collectionId,
                $limit,
                $offset,
                $dryRun
            );

            // Log activity
            $this->logActivity('import_started', sprintf(
                'Import started: %s (%s) - Limit: %d, Offset: %d%s',
                $artistName,
                $collectionId,
                $limit,
                $offset,
                $dryRun ? ' [DRY RUN]' : ''
            ), $job->getJobId());

            return $result->setData([
                'success' => true,
                'job_id' => $job->getJobId(),
                'message' => sprintf(
                    'Import job queued for %s. Job ID: %s',
                    $artistName,
                    $job->getJobId()
                )
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
            // Don't fail the main operation if logging fails
        }
    }
}

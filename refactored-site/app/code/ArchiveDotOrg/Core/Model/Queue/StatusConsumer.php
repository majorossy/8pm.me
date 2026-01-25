<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Queue;

use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Consumer for import status updates
 *
 * Processes status update messages (for future dashboard/notification features)
 */
class StatusConsumer
{
    private JobStatusManager $jobStatusManager;
    private Json $json;
    private Logger $logger;

    /**
     * @param JobStatusManager $jobStatusManager
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        JobStatusManager $jobStatusManager,
        Json $json,
        Logger $logger
    ) {
        $this->jobStatusManager = $jobStatusManager;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Process a status update message
     *
     * @param string $statusJson
     * @return void
     */
    public function processStatus(string $statusJson): void
    {
        try {
            $data = $this->json->unserialize($statusJson);

            $jobId = $data['job_id'] ?? null;
            $status = $data['status'] ?? null;
            $message = $data['message'] ?? null;
            $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

            if (!$jobId || !$status) {
                $this->logger->warning('Invalid status update message', ['data' => $data]);
                return;
            }

            $this->logger->debug('Processing status update', [
                'job_id' => $jobId,
                'status' => $status,
                'message' => $message,
                'timestamp' => $timestamp
            ]);

            // Future: Send email notifications, update dashboard, trigger webhooks, etc.
            // For now, just log the status update

            // Example future features:
            // - $this->notificationService->sendStatusEmail($jobId, $status, $message);
            // - $this->dashboardCache->invalidate($jobId);
            // - $this->webhookDispatcher->dispatch('import_status_changed', $data);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process status update', [
                'error' => $e->getMessage(),
                'message' => $statusJson
            ]);
        }
    }
}

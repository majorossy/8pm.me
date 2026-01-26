<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Model\Queue\JobStatusManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard JobStatus Controller
 *
 * AJAX endpoint for polling job progress
 */
class JobStatus extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::import_status';

    private JsonFactory $resultJsonFactory;
    private JobStatusManager $jobStatusManager;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param JobStatusManager $jobStatusManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        JobStatusManager $jobStatusManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jobStatusManager = $jobStatusManager;
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
                // Return all active jobs
                $activeJobs = [];
                $runningJobs = $this->jobStatusManager->getJobs('running', 10);
                $queuedJobs = $this->jobStatusManager->getJobs('queued', 10);

                foreach (array_merge($runningJobs, $queuedJobs) as $job) {
                    $activeJobs[] = $this->formatJobData($job);
                }

                return $result->setData([
                    'success' => true,
                    'jobs' => $activeJobs
                ]);
            }

            // Get specific job
            $job = $this->jobStatusManager->getJob($jobId);

            if ($job === null) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Job not found.'
                ]);
            }

            return $result->setData([
                'success' => true,
                'job' => $this->formatJobData($job)
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format job data for JSON response
     *
     * @param \ArchiveDotOrg\Core\Api\Data\ImportJobInterface $job
     * @return array
     */
    private function formatJobData($job): array
    {
        $data = [
            'job_id' => $job->getJobId(),
            'status' => $job->getStatus(),
            'artist_name' => $job->getArtistName(),
            'collection_id' => $job->getCollectionId(),
            'total_shows' => $job->getTotalShows(),
            'processed_shows' => $job->getProcessedShows(),
            'tracks_created' => $job->getTracksCreated(),
            'tracks_updated' => $job->getTracksUpdated(),
            'error_count' => $job->getErrorCount(),
            'progress' => $job->getProgress()
        ];

        // Include additional data if available
        if (method_exists($job, 'getData')) {
            $extraFields = ['limit', 'offset', 'dry_run', 'queued_at', 'started_at', 'completed_at', 'cancelled_at', 'error', 'errors'];
            foreach ($extraFields as $field) {
                $value = $job->getData($field);
                if ($value !== null) {
                    $data[$field] = $value;
                }
            }
        }

        return $data;
    }
}

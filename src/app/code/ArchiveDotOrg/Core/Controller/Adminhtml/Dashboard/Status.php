<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\Queue\JobStatusManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard Status Controller
 *
 * AJAX endpoint returning dashboard status data
 */
class Status extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::dashboard';

    private JsonFactory $resultJsonFactory;
    private Config $config;
    private ArchiveApiClientInterface $apiClient;
    private ProductCollectionFactory $productCollectionFactory;
    private JobStatusManager $jobStatusManager;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param ArchiveApiClientInterface $apiClient
     * @param ProductCollectionFactory $productCollectionFactory
     * @param JobStatusManager $jobStatusManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        ArchiveApiClientInterface $apiClient,
        ProductCollectionFactory $productCollectionFactory,
        JobStatusManager $jobStatusManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->apiClient = $apiClient;
        $this->productCollectionFactory = $productCollectionFactory;
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
            // Get product count
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter('identifier', ['notnull' => true]);
            $productCollection->addAttributeToFilter('identifier', ['neq' => '']);
            $totalProducts = $productCollection->getSize();

            // Test API connectivity
            $apiConnected = false;
            try {
                $apiConnected = $this->apiClient->testConnection();
            } catch (\Exception $e) {
                // API not connected
            }

            // Get active jobs
            $activeJobs = [];
            $runningJobs = $this->jobStatusManager->getJobs('running', 10);
            $queuedJobs = $this->jobStatusManager->getJobs('queued', 10);

            foreach (array_merge($runningJobs, $queuedJobs) as $job) {
                $activeJobs[] = [
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
            }

            // Get recent completed jobs for "last import" timestamp
            $recentJobs = $this->jobStatusManager->getJobs('completed', 1);
            $lastImport = null;
            if (!empty($recentJobs)) {
                $lastJob = $recentJobs[0];
                if (method_exists($lastJob, 'getData')) {
                    $lastImport = $lastJob->getData('completed_at');
                }
            }

            // Get artist mappings
            $artistMappings = $this->config->getArtistMappings();

            $data = [
                'success' => true,
                'stats' => [
                    'total_products' => $totalProducts,
                    'api_connected' => $apiConnected,
                    'last_import' => $lastImport,
                    'module_enabled' => $this->config->isEnabled(),
                    'cron_enabled' => $this->config->isCronEnabled()
                ],
                'active_jobs' => $activeJobs,
                'artist_mappings' => $artistMappings,
                'config' => [
                    'batch_size' => $this->config->getBatchSize(),
                    'audio_format' => $this->config->getAudioFormat(),
                    'timeout' => $this->config->getTimeout()
                ]
            ];

            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

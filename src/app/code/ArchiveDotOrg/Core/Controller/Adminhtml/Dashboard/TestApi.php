<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Controller\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Dashboard TestApi Controller
 *
 * AJAX endpoint to test Archive.org API connectivity
 */
class TestApi extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Core::dashboard';

    private JsonFactory $resultJsonFactory;
    private ArchiveApiClientInterface $apiClient;
    private Config $config;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ArchiveApiClientInterface $apiClient
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ArchiveApiClientInterface $apiClient,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiClient = $apiClient;
        $this->config = $config;
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
            $testCollection = $this->getRequest()->getParam('collection');

            // Basic connectivity test
            $startTime = microtime(true);
            $connected = $this->apiClient->testConnection();
            $responseTime = round((microtime(true) - $startTime) * 1000);

            if (!$connected) {
                return $result->setData([
                    'success' => false,
                    'connected' => false,
                    'message' => 'Cannot connect to Archive.org API.',
                    'base_url' => $this->config->getBaseUrl(),
                    'response_time_ms' => $responseTime
                ]);
            }

            $data = [
                'success' => true,
                'connected' => true,
                'message' => 'Successfully connected to Archive.org API.',
                'base_url' => $this->config->getBaseUrl(),
                'response_time_ms' => $responseTime
            ];

            // Test specific collection if requested
            if (!empty($testCollection)) {
                try {
                    $collectionStartTime = microtime(true);
                    $count = $this->apiClient->getCollectionCount($testCollection);
                    $collectionResponseTime = round((microtime(true) - $collectionStartTime) * 1000);

                    $data['collection_test'] = [
                        'collection_id' => $testCollection,
                        'item_count' => $count,
                        'response_time_ms' => $collectionResponseTime,
                        'success' => true
                    ];
                } catch (\Exception $e) {
                    $data['collection_test'] = [
                        'collection_id' => $testCollection,
                        'error' => $e->getMessage(),
                        'success' => false
                    ];
                }
            }

            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

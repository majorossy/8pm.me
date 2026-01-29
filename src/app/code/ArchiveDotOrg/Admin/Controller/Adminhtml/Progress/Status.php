<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Controller\Adminhtml\Progress;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use ArchiveDotOrg\Admin\Model\Redis\ProgressTracker;

/**
 * AJAX endpoint for real-time import progress
 * 
 * Polls Redis for current progress and returns JSON for dashboard updates.
 */
class Status extends Action
{
    public const ADMIN_RESOURCE = 'ArchiveDotOrg_Admin::dashboard';
    
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ProgressTracker $progressTracker
    ) {
        parent::__construct($context);
    }
    
    /**
     * Get progress status for an artist
     * 
     * Returns JSON:
     * {
     *   "artist": "lettuce",
     *   "status": "running|completed|failed",
     *   "current": 150,
     *   "total": 523,
     *   "processed": 145,
     *   "eta": "2026-01-28T15:30:00Z",
     *   "correlation_id": "abc-123",
     *   "error": ""
     * }
     */
    public function execute(): ResultInterface
    {
        $artist = $this->getRequest()->getParam('artist');
        
        if (!$artist) {
            return $this->resultJsonFactory->create()->setData([
                'error' => 'Missing artist parameter'
            ]);
        }
        
        $progress = $this->progressTracker->getProgress($artist);
        
        if (!$progress) {
            return $this->resultJsonFactory->create()->setData([
                'artist' => $artist,
                'status' => 'idle',
                'current' => 0,
                'total' => 0,
                'processed' => 0,
                'eta' => '',
                'correlation_id' => '',
                'error' => ''
            ]);
        }
        
        return $this->resultJsonFactory->create()->setData($progress);
    }
}

<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Daily Metrics Resource Model
 */
class DailyMetrics extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init('archivedotorg_daily_metrics', 'metric_id');
    }
}

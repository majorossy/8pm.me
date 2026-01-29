<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel\DailyMetrics;

use ArchiveDotOrg\Admin\Model\DailyMetrics;
use ArchiveDotOrg\Admin\Model\ResourceModel\DailyMetrics as DailyMetricsResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Daily Metrics Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'metric_id';

    /**
     * Initialize collection
     */
    protected function _construct(): void
    {
        $this->_init(DailyMetrics::class, DailyMetricsResource::class);
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\ResourceModel\ActivityLog;

use ArchiveDotOrg\Core\Model\ActivityLog;
use ArchiveDotOrg\Core\Model\ResourceModel\ActivityLog as ActivityLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Activity Log Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'archivedotorg_activity_log_collection';

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ActivityLog::class, ActivityLogResource::class);
    }
}

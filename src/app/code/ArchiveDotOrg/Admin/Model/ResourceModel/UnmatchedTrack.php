<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Unmatched Track Resource Model
 */
class UnmatchedTrack extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init('archivedotorg_unmatched_track', 'unmatched_id');
    }
}

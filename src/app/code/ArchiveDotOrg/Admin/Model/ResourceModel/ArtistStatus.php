<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Artist Status Resource Model
 */
class ArtistStatus extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init('archivedotorg_artist_status', 'status_id');
    }
}

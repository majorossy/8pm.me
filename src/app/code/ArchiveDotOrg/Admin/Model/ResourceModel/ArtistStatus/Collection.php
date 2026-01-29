<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel\ArtistStatus;

use ArchiveDotOrg\Admin\Model\ArtistStatus;
use ArchiveDotOrg\Admin\Model\ResourceModel\ArtistStatus as ArtistStatusResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Artist Status Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'status_id';

    /**
     * Initialize collection
     */
    protected function _construct(): void
    {
        $this->_init(ArtistStatus::class, ArtistStatusResource::class);
    }
}

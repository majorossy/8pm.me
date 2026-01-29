<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel\UnmatchedTrack;

use ArchiveDotOrg\Admin\Model\UnmatchedTrack;
use ArchiveDotOrg\Admin\Model\ResourceModel\UnmatchedTrack as UnmatchedTrackResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Unmatched Track Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'unmatched_id';

    /**
     * Initialize collection
     */
    protected function _construct(): void
    {
        $this->_init(UnmatchedTrack::class, UnmatchedTrackResource::class);
    }
}

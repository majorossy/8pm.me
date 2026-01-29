<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\ResourceModel\ImportRun;

use ArchiveDotOrg\Admin\Model\ImportRun;
use ArchiveDotOrg\Admin\Model\ResourceModel\ImportRun as ImportRunResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Import Run Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'run_id';

    /**
     * Initialize collection
     */
    protected function _construct(): void
    {
        $this->_init(ImportRun::class, ImportRunResource::class);
    }
}

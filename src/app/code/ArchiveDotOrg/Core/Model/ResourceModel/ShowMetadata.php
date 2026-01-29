<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Show Metadata Resource Model
 *
 * Handles database operations for archivedotorg_show_metadata table
 */
class ShowMetadata extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('archivedotorg_show_metadata', 'metadata_id');
    }

    /**
     * Load show metadata by show identifier
     *
     * @param \ArchiveDotOrg\Core\Model\ShowMetadata $object
     * @param string $showIdentifier
     * @return $this
     */
    public function loadByShowIdentifier(
        \ArchiveDotOrg\Core\Model\ShowMetadata $object,
        string $showIdentifier
    ): self {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('show_identifier = ?', $showIdentifier);

        $data = $connection->fetchRow($select);

        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $this;
    }
}

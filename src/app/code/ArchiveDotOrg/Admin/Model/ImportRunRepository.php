<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model;

use ArchiveDotOrg\Admin\Api\ImportRunRepositoryInterface;
use ArchiveDotOrg\Admin\Model\ResourceModel\ImportRun as ImportRunResource;
use ArchiveDotOrg\Admin\Model\ResourceModel\ImportRun\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Import Run Repository
 */
class ImportRunRepository implements ImportRunRepositoryInterface
{
    public function __construct(
        private readonly ImportRunFactory $importRunFactory,
        private readonly ImportRunResource $importRunResource,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ImportRun $importRun): ImportRun
    {
        try {
            $this->importRunResource->save($importRun);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save import run: %1', $e->getMessage()), $e);
        }

        return $importRun;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $runId): ImportRun
    {
        $importRun = $this->importRunFactory->create();
        $this->importRunResource->load($importRun, $runId);

        if (!$importRun->getId()) {
            throw new NoSuchEntityException(__('Import run with ID "%1" does not exist.', $runId));
        }

        return $importRun;
    }

    /**
     * @inheritdoc
     */
    public function getByUuid(string $uuid): ImportRun
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('uuid', $uuid);

        $importRun = $collection->getFirstItem();

        if (!$importRun->getId()) {
            throw new NoSuchEntityException(__('Import run with UUID "%1" does not exist.', $uuid));
        }

        return $importRun;
    }

    /**
     * @inheritdoc
     */
    public function getByCorrelationId(string $correlationId): ImportRun
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('correlation_id', $correlationId);

        $importRun = $collection->getFirstItem();

        if (!$importRun->getId()) {
            throw new NoSuchEntityException(__('Import run with correlation ID "%1" does not exist.', $correlationId));
        }

        return $importRun;
    }

    /**
     * @inheritdoc
     */
    public function delete(ImportRun $importRun): bool
    {
        try {
            $this->importRunResource->delete($importRun);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete import run: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $runId): bool
    {
        return $this->delete($this->getById($runId));
    }
}

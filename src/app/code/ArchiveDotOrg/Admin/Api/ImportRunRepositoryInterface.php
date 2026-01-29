<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Api;

use ArchiveDotOrg\Admin\Model\ImportRun;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Import Run Repository Interface
 */
interface ImportRunRepositoryInterface
{
    /**
     * Save import run
     *
     * @param ImportRun $importRun
     * @return ImportRun
     */
    public function save(ImportRun $importRun): ImportRun;

    /**
     * Get import run by ID
     *
     * @param int $runId
     * @return ImportRun
     * @throws NoSuchEntityException
     */
    public function getById(int $runId): ImportRun;

    /**
     * Get import run by UUID
     *
     * @param string $uuid
     * @return ImportRun
     * @throws NoSuchEntityException
     */
    public function getByUuid(string $uuid): ImportRun;

    /**
     * Get import run by correlation ID
     *
     * @param string $correlationId
     * @return ImportRun
     * @throws NoSuchEntityException
     */
    public function getByCorrelationId(string $correlationId): ImportRun;

    /**
     * Delete import run
     *
     * @param ImportRun $importRun
     * @return bool
     */
    public function delete(ImportRun $importRun): bool;

    /**
     * Delete import run by ID
     *
     * @param int $runId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function deleteById(int $runId): bool;
}

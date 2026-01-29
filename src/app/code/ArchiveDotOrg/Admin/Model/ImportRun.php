<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Import Run Model
 *
 * Represents a single import command execution with all its metrics and logs.
 */
class ImportRun extends AbstractModel
{
    /**
     * Status constants
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\ImportRun::class);
    }

    /**
     * Get run ID
     */
    public function getRunId(): ?int
    {
        return $this->getData('run_id') ? (int) $this->getData('run_id') : null;
    }

    /**
     * Get UUID
     */
    public function getUuid(): ?string
    {
        return $this->getData('uuid');
    }

    /**
     * Set UUID
     */
    public function setUuid(string $uuid): self
    {
        return $this->setData('uuid', $uuid);
    }

    /**
     * Get correlation ID
     */
    public function getCorrelationId(): ?string
    {
        return $this->getData('correlation_id');
    }

    /**
     * Set correlation ID
     */
    public function setCorrelationId(string $correlationId): self
    {
        return $this->setData('correlation_id', $correlationId);
    }

    /**
     * Get artist ID
     */
    public function getArtistId(): ?int
    {
        return $this->getData('artist_id') ? (int) $this->getData('artist_id') : null;
    }

    /**
     * Set artist ID
     */
    public function setArtistId(?int $artistId): self
    {
        return $this->setData('artist_id', $artistId);
    }

    /**
     * Get command name
     */
    public function getCommandName(): ?string
    {
        return $this->getData('command_name');
    }

    /**
     * Set command name
     */
    public function setCommandName(string $commandName): self
    {
        return $this->setData('command_name', $commandName);
    }

    /**
     * Get status
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * Set status
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * Get started at timestamp
     */
    public function getStartedAt(): ?string
    {
        return $this->getData('started_at');
    }

    /**
     * Set started at timestamp
     */
    public function setStartedAt(string $startedAt): self
    {
        return $this->setData('started_at', $startedAt);
    }

    /**
     * Get completed at timestamp
     */
    public function getCompletedAt(): ?string
    {
        return $this->getData('completed_at');
    }

    /**
     * Set completed at timestamp
     */
    public function setCompletedAt(?string $completedAt): self
    {
        return $this->setData('completed_at', $completedAt);
    }

    /**
     * Get items processed count
     */
    public function getItemsProcessed(): int
    {
        return (int) ($this->getData('items_processed') ?? 0);
    }

    /**
     * Set items processed count
     */
    public function setItemsProcessed(int $count): self
    {
        return $this->setData('items_processed', $count);
    }

    /**
     * Get items successful count
     */
    public function getItemsSuccessful(): int
    {
        return (int) ($this->getData('items_successful') ?? 0);
    }

    /**
     * Set items successful count
     */
    public function setItemsSuccessful(int $count): self
    {
        return $this->setData('items_successful', $count);
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->getData('error_message');
    }

    /**
     * Set error message
     */
    public function setErrorMessage(?string $message): self
    {
        return $this->setData('error_message', $message);
    }

    /**
     * Check if run is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_COMPLETED, self::STATUS_PARTIAL, self::STATUS_FAILED, self::STATUS_CANCELLED], true);
    }

    /**
     * Check if run is successful
     */
    public function isSuccessful(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }
}

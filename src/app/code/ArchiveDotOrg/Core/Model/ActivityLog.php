<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\Model\AbstractModel;
use ArchiveDotOrg\Core\Model\ResourceModel\ActivityLog as ActivityLogResource;

/**
 * Activity Log Model
 *
 * Stores activity log entries for Archive.org dashboard operations
 */
class ActivityLog extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'archivedotorg_activity_log';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ActivityLogResource::class);
    }

    /**
     * Get action type
     *
     * @return string|null
     */
    public function getActionType(): ?string
    {
        return $this->getData('action_type');
    }

    /**
     * Set action type
     *
     * @param string $actionType
     * @return $this
     */
    public function setActionType(string $actionType): self
    {
        return $this->setData('action_type', $actionType);
    }

    /**
     * Get details
     *
     * @return string|null
     */
    public function getDetails(): ?string
    {
        return $this->getData('details');
    }

    /**
     * Set details
     *
     * @param string $details
     * @return $this
     */
    public function setDetails(string $details): self
    {
        return $this->setData('details', $details);
    }

    /**
     * Get job ID
     *
     * @return string|null
     */
    public function getJobId(): ?string
    {
        return $this->getData('job_id');
    }

    /**
     * Set job ID
     *
     * @param string|null $jobId
     * @return $this
     */
    public function setJobId(?string $jobId): self
    {
        return $this->setData('job_id', $jobId);
    }

    /**
     * Get admin user ID
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int
    {
        return $this->getData('admin_user_id') !== null ? (int) $this->getData('admin_user_id') : null;
    }

    /**
     * Set admin user ID
     *
     * @param int|null $userId
     * @return $this
     */
    public function setAdminUserId(?int $userId): self
    {
        return $this->setData('admin_user_id', $userId);
    }

    /**
     * Get admin username
     *
     * @return string|null
     */
    public function getAdminUsername(): ?string
    {
        return $this->getData('admin_username');
    }

    /**
     * Set admin username
     *
     * @param string $username
     * @return $this
     */
    public function setAdminUsername(string $username): self
    {
        return $this->setData('admin_username', $username);
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }
}

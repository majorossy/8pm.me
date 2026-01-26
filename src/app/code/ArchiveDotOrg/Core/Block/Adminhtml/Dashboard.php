<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Block\Adminhtml;

use ArchiveDotOrg\Core\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Dashboard Main Block
 */
class Dashboard extends Template
{
    private Config $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Get AJAX URLs for JavaScript
     *
     * @return array
     */
    public function getAjaxUrls(): array
    {
        return [
            'status' => $this->getUrl('archivedotorg/dashboard/status'),
            'start_import' => $this->getUrl('archivedotorg/dashboard/startImport'),
            'job_status' => $this->getUrl('archivedotorg/dashboard/jobStatus'),
            'sync_albums' => $this->getUrl('archivedotorg/dashboard/syncAlbums'),
            'cleanup_products' => $this->getUrl('archivedotorg/dashboard/cleanupProducts'),
            'test_api' => $this->getUrl('archivedotorg/dashboard/testApi'),
            'cancel_job' => $this->getUrl('archivedotorg/dashboard/cancelJob'),
            'activity_log' => $this->getUrl('archivedotorg/dashboard/activityLog'),
            'products_grid' => $this->getUrl('archivedotorg/product/index')
        ];
    }

    /**
     * Get AJAX URLs as JSON for JavaScript
     *
     * @return string
     */
    public function getAjaxUrlsJson(): string
    {
        return json_encode($this->getAjaxUrls());
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}

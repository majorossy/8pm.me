<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Block\Adminhtml\Dashboard;

use ArchiveDotOrg\Core\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Dashboard Import Form Block
 */
class ImportForm extends Template
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
     * Get artist mappings for dropdown
     *
     * @return array
     */
    public function getArtistMappings(): array
    {
        return $this->config->getArtistMappings();
    }

    /**
     * Get default batch size
     *
     * @return int
     */
    public function getDefaultBatchSize(): int
    {
        return $this->config->getBatchSize();
    }
}

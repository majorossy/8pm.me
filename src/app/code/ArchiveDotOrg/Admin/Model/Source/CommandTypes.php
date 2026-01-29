<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CommandTypes implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'download', 'label' => __('Download')],
            ['value' => 'populate', 'label' => __('Populate')],
            ['value' => 'download-metadata', 'label' => __('Download Metadata (deprecated)')],
            ['value' => 'populate-tracks', 'label' => __('Populate Tracks (deprecated)')],
            ['value' => 'import-shows', 'label' => __('Import Shows (deprecated)')],
            ['value' => 'sync-albums', 'label' => __('Sync Albums')],
            ['value' => 'cleanup', 'label' => __('Cleanup')],
        ];
    }
}

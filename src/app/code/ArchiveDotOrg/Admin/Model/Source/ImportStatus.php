<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImportStatus implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'running', 'label' => __('Running')],
            ['value' => 'completed', 'label' => __('Completed')],
            ['value' => 'failed', 'label' => __('Failed')],
        ];
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Category\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Track Number Source Model
 *
 * Provides options for song track numbers (1-99)
 */
class TrackNumber extends AbstractSource
{
    /**
     * @inheritDoc
     */
    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('-- Please Select --')]
            ];

            for ($i = 1; $i <= 99; $i++) {
                $this->_options[] = [
                    'value' => $i,
                    'label' => (string) $i
                ];
            }
        }

        return $this->_options;
    }
}

<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Audio Format Source Model
 *
 * Provides options for audio format selection in admin config
 */
class AudioFormat implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'flac', 'label' => __('FLAC (Lossless)')],
            ['value' => 'mp3', 'label' => __('MP3')],
            ['value' => 'ogg', 'label' => __('OGG Vorbis')],
            ['value' => 'shn', 'label' => __('Shorten (SHN)')]
        ];
    }
}

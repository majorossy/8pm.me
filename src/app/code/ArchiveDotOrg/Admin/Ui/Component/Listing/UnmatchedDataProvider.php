<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class UnmatchedDataProvider extends DataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();

        // Add custom processing if needed
        // For example, highlight high-occurrence unmatched tracks
        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                // Mark high-occurrence tracks (appears in many shows)
                if (isset($item['occurrence_count']) && $item['occurrence_count'] > 10) {
                    $item['priority'] = 'high';
                } elseif (isset($item['occurrence_count']) && $item['occurrence_count'] > 5) {
                    $item['priority'] = 'medium';
                } else {
                    $item['priority'] = 'low';
                }
            }
        }

        return $data;
    }
}

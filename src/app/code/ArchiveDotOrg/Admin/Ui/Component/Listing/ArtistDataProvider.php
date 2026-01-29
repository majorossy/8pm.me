<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class ArtistDataProvider extends DataProvider
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
        // For example, format match_rate as percentage
        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['match_rate'])) {
                    $item['match_rate_formatted'] = number_format($item['match_rate'], 1) . '%';
                }
            }
        }

        return $data;
    }
}

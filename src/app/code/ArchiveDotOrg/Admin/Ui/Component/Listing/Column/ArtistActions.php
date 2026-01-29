<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class ArtistActions extends Column
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['artist_id'])) {
                    $item[$name]['download'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'archivedotorg/artist/download',
                            ['artist_id' => $item['artist_id']]
                        ),
                        'label' => __('Download'),
                        'confirm' => [
                            'title' => __('Download Shows'),
                            'message' => __('Download metadata for %1?', $item['artist_name'] ?? 'this artist')
                        ]
                    ];
                    $item[$name]['populate'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'archivedotorg/artist/populate',
                            ['artist_id' => $item['artist_id']]
                        ),
                        'label' => __('Populate'),
                        'confirm' => [
                            'title' => __('Populate Tracks'),
                            'message' => __('Populate tracks for %1?', $item['artist_name'] ?? 'this artist')
                        ]
                    ];
                    $item[$name]['view_unmatched'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'archivedotorg/unmatched/index',
                            ['artist_id' => $item['artist_id']]
                        ),
                        'label' => __('View Unmatched')
                    ];
                }
            }
        }

        return $dataSource;
    }
}

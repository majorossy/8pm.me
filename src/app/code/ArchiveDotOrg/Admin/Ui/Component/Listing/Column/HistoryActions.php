<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class HistoryActions extends Column
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
                if (isset($item['run_id'])) {
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'archivedotorg/history/view',
                            ['run_id' => $item['run_id']]
                        ),
                        'label' => __('View Details')
                    ];

                    // Only show retry for failed imports
                    if (isset($item['status']) && $item['status'] === 'failed') {
                        $item[$name]['retry'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'archivedotorg/history/retry',
                                ['run_id' => $item['run_id']]
                            ),
                            'label' => __('Retry'),
                            'confirm' => [
                                'title' => __('Retry Import'),
                                'message' => __('Retry this import with the same parameters?')
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}

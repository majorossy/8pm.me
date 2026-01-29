<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class UnmatchedActions extends Column
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
                if (isset($item['unmatched_id'])) {
                    // Toggle resolved status
                    if (isset($item['resolved']) && $item['resolved']) {
                        $item[$name]['mark_unresolved'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'archivedotorg/unmatched/unresolve',
                                ['id' => $item['unmatched_id']]
                            ),
                            'label' => __('Mark as Unresolved')
                        ];
                    } else {
                        $item[$name]['mark_resolved'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'archivedotorg/unmatched/resolve',
                                ['id' => $item['unmatched_id']]
                            ),
                            'label' => __('Mark as Resolved')
                        ];
                    }

                    // Add to YAML aliases
                    if (isset($item['suggested_match']) && !empty($item['suggested_match'])) {
                        $item[$name]['add_alias'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'archivedotorg/unmatched/addAlias',
                                [
                                    'id' => $item['unmatched_id'],
                                    'artist_id' => $item['artist_id'] ?? null
                                ]
                            ),
                            'label' => __('Add as Alias'),
                            'confirm' => [
                                'title' => __('Add Alias'),
                                'message' => __('Add "%1" as alias for "%2" in YAML config?',
                                    $item['track_name'] ?? '',
                                    $item['suggested_match'])
                            ]
                        ];
                    }

                    // View shows with this track
                    $item[$name]['view_shows'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'archivedotorg/unmatched/viewShows',
                            ['id' => $item['unmatched_id']]
                        ),
                        'label' => __('View Shows')
                    ];
                }
            }
        }

        return $dataSource;
    }
}

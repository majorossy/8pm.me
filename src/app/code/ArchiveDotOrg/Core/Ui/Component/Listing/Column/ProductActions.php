<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Actions column for Archive.org Product grid
 *
 * Provides Edit, Delete, Re-import, and View on Archive.org actions
 */
class ProductActions extends Column
{
    private const URL_PATH_EDIT = 'catalog/product/edit';
    private const URL_PATH_DELETE = 'archivedotorg/product/delete';
    private const URL_PATH_REIMPORT = 'archivedotorg/product/reimport';
    private const ARCHIVE_ORG_BASE_URL = 'https://archive.org/details/';

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
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $name = $this->getData('name');

            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(
                    self::URL_PATH_EDIT,
                    ['id' => $item['entity_id']]
                ),
                'label' => __('Edit'),
            ];

            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(
                    self::URL_PATH_DELETE,
                    ['id' => $item['entity_id']]
                ),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete Product'),
                    'message' => __('Are you sure you want to delete this product? This action cannot be undone.'),
                ],
            ];

            $item[$name]['reimport'] = [
                'href' => $this->urlBuilder->getUrl(
                    self::URL_PATH_REIMPORT,
                    ['id' => $item['entity_id']]
                ),
                'label' => __('Re-import'),
                'confirm' => [
                    'title' => __('Re-import Product'),
                    'message' => __('Are you sure you want to re-import this product from Archive.org? This will refresh all metadata.'),
                ],
            ];

            // Add View on Archive.org link if identifier exists
            if (!empty($item['identifier'])) {
                $item[$name]['view_archive'] = [
                    'href' => self::ARCHIVE_ORG_BASE_URL . $item['identifier'],
                    'label' => __('View on Archive.org'),
                    'target' => '_blank',
                ];
            }
        }

        return $dataSource;
    }
}
